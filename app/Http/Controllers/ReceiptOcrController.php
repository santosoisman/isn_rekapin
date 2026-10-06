<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReceiptOcrController extends Controller
{
    // Terima upload foto struk, kirim ke OCR.space, lalu tebak nominal & tanggalnya
    public function scan(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:10240', // max 10MB — foto kamera HP modern sering 3-8MB
        ]);

        // Utamakan AI (vision) kalau sudah diset di .env; gagal => lanjut ke OCR.space di bawah
        if (config('vision.base_url') && config('vision.model')) {
            $ai = $this->scanWithAi($request->file('image'));

            if ($ai !== null) {
                return response()->json($ai);
            }
        }

        $apiKey = config('services.ocr_space.key', 'helloworld');

        try {
            $response = Http::asMultipart()
                ->timeout(20)
                ->retry(2, 500)
                ->post('https://api.ocr.space/parse/image', [
                    ['name' => 'apikey', 'contents' => $apiKey],
                    ['name' => 'language', 'contents' => 'eng'],
                    ['name' => 'OCREngine', 'contents' => '2'],
                    ['name' => 'scale', 'contents' => 'true'],
                    [
                        'name' => 'file',
                        'contents' => fopen($request->file('image')->getRealPath(), 'r'),
                        'filename' => $request->file('image')->getClientOriginalName(),
                    ],
                ]);
        } catch (\Throwable $e) {
            Log::error('OCR scan gagal konek ke OCR.space: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Server gagal terhubung ke layanan OCR. Coba lagi sebentar, atau isi manual dulu.',
            ], 502);
        }

        if (! $response->successful()) {
            Log::error('OCR.space merespons error: HTTP ' . $response->status() . ' — ' . $response->body());

            return response()->json([
                'success' => false,
                'message' => 'Layanan OCR sedang bermasalah (kode ' . $response->status() . '). Isi manual dulu ya.',
            ], 502);
        }

        $data = $response->json();
        $text = $data['ParsedResults'][0]['ParsedText'] ?? '';

        if (($data['IsErroredOnProcessing'] ?? false) || trim($text) === '') {
            return response()->json([
                'success' => false,
                'message' => $data['ErrorMessage'][0] ?? 'Gagal membaca struk. Coba foto yang lebih jelas & terang.',
            ], 422);
        }

        $amount = $this->guessAmount($text);
        $date = $this->guessDate($text);

        // Dicatat supaya gampang dicek kalau hasil tebakan meleset
        Log::info('OCR struk', ['amount' => $amount, 'date' => $date, 'text' => mb_substr($text, 0, 600)]);

        return response()->json([
            'success' => true,
            'raw_text' => $text,
            'amount' => $amount,
            'date' => $date,
        ]);
    }

    // Kirim foto ke model vision (endpoint OpenAI-compatible) dan minta hasil JSON terstruktur.
    protected function scanWithAi($file): ?array
    {
        $prompt = 'Ini foto struk/kwitansi belanja (kemungkinan Indonesia). Balas HANYA dengan JSON valid, tanpa teks lain: '
            . '{"total": angka TOTAL AKHIR yang dibayar dalam rupiah tanpa titik/koma, yaitu setelah diskon dan pajak '
            . '(bukan subtotal, bukan uang tunai/kembalian), '
            . '"date": "YYYY-MM-DD" atau null, "merchant": "nama toko" atau null, '
            . '"items": [{"name": "nama barang dirapikan, bukan singkatan struk (mis. Roti krim keju)", '
            . '"qty": jumlah barang (angka, default 1), "price": total harga baris itu dalam rupiah}] (maksimal 40)}. '
            . 'Jika tidak yakin pada suatu nilai, isi null.';

        try {
            $mime = $file->getMimeType() ?: 'image/jpeg';
            $b64 = base64_encode(file_get_contents($file->getRealPath()));

            $response = Http::acceptJson()
                ->when(config('vision.api_key'), fn ($r) => $r->withToken(config('vision.api_key')))
                ->timeout((int) config('vision.timeout', 30))
                ->post(rtrim(config('vision.base_url'), '/') . '/chat/completions', [
                    'model' => config('vision.model'),
                    'temperature' => 0,
                    'messages' => [[
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => $prompt],
                            ['type' => 'image_url', 'image_url' => ['url' => 'data:' . $mime . ';base64,' . $b64]],
                        ],
                    ]],
                ]);
        } catch (\Throwable $e) {
            Log::warning('Scan AI gagal konek: ' . $e->getMessage());

            return null;
        }

        if (! $response->successful()) {
            Log::warning('Scan AI HTTP ' . $response->status() . ': ' . mb_substr($response->body(), 0, 300));

            return null;
        }

        $parsed = $this->parseAiContent((string) data_get($response->json(), 'choices.0.message.content', ''));

        if ($parsed === null) {
            Log::warning('Scan AI: balasan tidak bisa dibaca', ['body' => mb_substr($response->body(), 0, 500)]);

            return null;
        }

        Log::info('Scan struk (AI)', $parsed);

        $lines = array_filter([
            $parsed['merchant'] ? 'Toko: ' . $parsed['merchant'] : null,
            ...array_map(fn ($i) => '- ' . ($i['qty'] > 1 ? $i['qty'] . '× ' : '') . $i['name'] . ($i['price'] ? ' : ' . number_format($i['price'], 0, ',', '.') : ''), $parsed['items']),
        ]);

        return [
            'success' => true,
            'source' => 'ai',
            'raw_text' => implode("\n", $lines),
            'amount' => $parsed['total'],
            'date' => $parsed['date'],
            'merchant' => $parsed['merchant'],
            'items' => $parsed['items'],
        ];
    }

    // Ambil JSON dari balasan model (kadang dibungkus ```json ... ```), rapikan & validasi.
    protected function parseAiContent(string $content): ?array
    {
        $start = strpos($content, '{');
        $end = strrpos($content, '}');

        if ($start === false || $end === false || $end <= $start) {
            return null;
        }

        $json = json_decode(substr($content, $start, $end - $start + 1), true);

        if (! is_array($json)) {
            return null;
        }

        $total = $json['total'] ?? null;
        if (is_string($total)) {
            $total = $this->parseNumber($total);
        }
        $total = is_numeric($total) && $total > 0 ? (float) $total : null;

        $date = null;
        if (! empty($json['date']) && is_string($json['date'])) {
            try {
                $date = Carbon::createFromFormat('!Y-m-d', $json['date'])->format('Y-m-d');
            } catch (\Throwable $e) {
                $date = null;
            }
        }

        $items = [];
        foreach (array_slice((array) ($json['items'] ?? []), 0, 40) as $item) {
            if (is_array($item) && ! empty($item['name']) && is_string($item['name'])) {
                $price = $item['price'] ?? null;
                $qty = $item['qty'] ?? 1;
                $items[] = [
                    'name' => mb_substr($item['name'], 0, 100),
                    'qty' => is_numeric($qty) && $qty >= 1 && $qty <= 999 ? (int) $qty : 1,
                    'price' => is_numeric($price) ? (float) $price : null,
                ];
            }
        }

        $merchant = isset($json['merchant']) && is_string($json['merchant']) ? mb_substr(trim($json['merchant']), 0, 80) : null;

        // Tidak ada nominal & tanggal sama sekali => anggap gagal, biar fallback ke OCR.space
        if ($total === null && $date === null) {
            return null;
        }

        return ['total' => $total, 'date' => $date, 'merchant' => $merchant ?: null, 'items' => $items];
    }

    // Cari nominal total dari teks hasil OCR. Prioritas: baris yang mengandung kata "total"/"jumlah".
    protected function guessAmount(string $text): ?float
    {
        $lines = preg_split('/\r\n|\r|\n/', $text);
        $keywords = ['grand total', 'total bayar', 'total belanja', 'total', 'jumlah'];
        $candidates = [];

        $numberPattern = '/([\d]{1,3}(?:[.,]\d{3})+|\d{4,})/';

        foreach ($lines as $i => $line) {
            $lower = strtolower($line);

            foreach ($keywords as $kw) {
                if (! str_contains($lower, $kw)) {
                    continue;
                }

                // Angka bisa di baris yang sama ("TOTAL 25.000") atau baris berikutnya (OCR sering memisahkan)
                $raw = null;
                if (preg_match($numberPattern, $line, $m)) {
                    $raw = $m[1];
                } elseif (isset($lines[$i + 1]) && preg_match($numberPattern, $lines[$i + 1], $m)) {
                    $raw = $m[1];
                }

                if ($raw !== null) {
                    $candidates[] = $this->parseNumber($raw);
                }

                break;
            }
        }

        if (!empty($candidates)) {
            return max($candidates);
        }

        // Fallback: ambil angka berformat uang terbesar di seluruh teks
        preg_match_all('/(?:Rp\.?\s?)?([\d]{1,3}(?:[.,]\d{3})+)/', $text, $matches);
        if (!empty($matches[1])) {
            return max(array_map([$this, 'parseNumber'], $matches[1]));
        }

        // Fallback terakhir: angka setelah "Rp" tanpa pemisah ribuan (mis. "Rp 25000")
        if (preg_match_all('/Rp\.?\s?(\d{4,})/i', $text, $rp) && ! empty($rp[1])) {
            return max(array_map('floatval', $rp[1]));
        }

        return null;
    }

    // "25.000" atau "25,000" dianggap format Indonesia (titik/koma ribuan) => 25000
    protected function parseNumber(string $raw): float
    {
        $clean = preg_replace('/[.,](?=\d{3}(?:\D|$))/', '', $raw);
        $clean = preg_replace('/[^\d.]/', '', $clean);

        return (float) $clean;
    }

    protected function guessDate(string $text): ?string
    {
        if (preg_match('/(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{2,4})/', $text, $m)) {
            [, $d, $mo, $y] = $m;
            $y = strlen($y) === 2 ? '20' . $y : $y;

            try {
                return Carbon::createFromDate((int) $y, (int) $mo, (int) $d)->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }
}
