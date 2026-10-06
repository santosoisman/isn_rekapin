<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    public function store(Request $request)
    {
        $book = $request->attributes->get('currentBook');

        // Form dibuka di buku lain (mis. tab lama) lalu buku aktif berpindah => jangan simpan ke buku yang salah
        if ($request->filled('book_id') && (int) $request->input('book_id') !== (int) $book->id) {
            return back()->withErrors(['book' => 'Buku aktif sudah berubah (mungkin kamu membuka tab lain). Transaksi TIDAK disimpan — muat ulang halaman lalu coba lagi.']);
        }

        $data = $request->validate([
            'category_id' => ['required', Rule::exists('categories', 'id')->where('book_id', $book->id)],
            'item_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'note' => 'nullable|string|max:255',
            'receipt' => 'nullable|string|max:30000',
        ]);

        $item = $this->resolveItem($book->id, $data['category_id'], $data['item_name']);

        $book->expenses()->create([
            'category_id' => $data['category_id'],
            'item_id' => $item->id,
            'amount' => $data['amount'],
            'date' => $data['date'],
            'note' => $data['note'] ?? null,
            'receipt' => $this->cleanReceipt($data['receipt'] ?? null),
            'user_id' => $request->user()->id,
        ]);

        return back()->with('success', 'Pengeluaran berhasil dicatat.');
    }

    public function update(Request $request, Expense $expense)
    {
        $this->authorize('update', $expense);

        $data = $request->validate([
            'category_id' => ['required', Rule::exists('categories', 'id')->where('book_id', $expense->book_id)],
            'item_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'note' => 'nullable|string|max:255',
        ]);

        $item = $this->resolveItem($expense->book_id, $data['category_id'], $data['item_name']);

        $expense->update([
            'category_id' => $data['category_id'],
            'item_id' => $item->id,
            'amount' => $data['amount'],
            'date' => $data['date'],
            'note' => $data['note'] ?? null,
        ]);

        return back()->with('success', 'Pengeluaran berhasil diperbarui.');
    }

    public function destroy(Expense $expense)
    {
        $this->authorize('delete', $expense);

        $expense->delete();

        return back()->with('success', 'Pengeluaran berhasil dihapus.');
    }

    // Rincian struk datang dari browser (JSON), jadi dibersihkan dulu sebelum disimpan.
    protected function cleanReceipt(?string $json): ?array
    {
        if ($json === null || $json === '') {
            return null;
        }

        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            return null;
        }

        $items = [];
        foreach (array_slice((array) ($decoded['items'] ?? []), 0, 60) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $name = trim(strip_tags((string) ($row['name'] ?? '')));
            if ($name === '') {
                continue;
            }

            $qty = is_numeric($row['qty'] ?? null) ? (float) $row['qty'] : 1;
            $qty = ($qty >= 1 && $qty <= 999) ? $qty : 1;

            $items[] = [
                'name' => mb_substr($name, 0, 100),
                'qty' => floor($qty) == $qty ? (int) $qty : $qty,
                'price' => is_numeric($row['price'] ?? null) ? round((float) $row['price'], 2) : null,
            ];
        }

        if (empty($items)) {
            return null;
        }

        $merchant = trim(strip_tags((string) ($decoded['merchant'] ?? '')));

        return [
            'merchant' => $merchant !== '' ? mb_substr($merchant, 0, 80) : null,
            'items' => $items,
        ];
    }

    // Cari item yang namanya sama persis di kategori itu (case-insensitive), kalau belum ada, buat baru otomatis.
    protected function resolveItem(int $bookId, int $categoryId, string $name): Item
    {
        $name = trim($name);

        $existing = Item::where('category_id', $categoryId)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        return $existing ?: Item::create([
            'book_id' => $bookId,
            'category_id' => $categoryId,
            'name' => $name,
        ]);
    }
}
