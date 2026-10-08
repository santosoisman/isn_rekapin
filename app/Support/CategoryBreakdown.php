<?php

namespace App\Support;

use App\Models\Book;
use Carbon\Carbon;

/**
 * Rangkuman pengeluaran per Jenis Pengeluaran untuk satu bulan (buat donut chart),
 * lengkap dengan perbandingan terhadap bulan sebelumnya. Semua dihitung langsung
 * dari DB, tidak ada total yang disimpan.
 */
class CategoryBreakdown
{
    // Warna irisan (urut dari yang terbesar); abu-abu untuk "Lainnya"
    private const COLORS = ['#6366f1', '#f59e0b', '#10b981', '#f43f5e', '#0ea5e9', '#a855f7'];
    private const OTHER_COLOR = '#94a3b8';
    private const MAX_SLICES = 5;

    public static function make(Book $book, string $from, string $to, bool $all = false): array
    {
        $fromDate = $all ? now()->startOfMonth() : Carbon::parse($from)->startOfMonth();
        $prevFrom = $fromDate->copy()->subMonthNoOverflow()->startOfMonth();
        $prevTo = $prevFrom->copy()->endOfMonth();

        $names = $book->categories()->pluck('name', 'id');

        $expenses = $book->expenses()
            ->whereBetween('date', [$from, $to])
            ->with('item')
            ->get();

        // Mode "Semua" tidak punya pembanding bulan lalu
        $prevTotals = $all
            ? collect()
            : $book->expenses()
                ->whereBetween('date', [$prevFrom->toDateString(), $prevTo->toDateString()])
                ->selectRaw('category_id, SUM(amount) as total')
                ->groupBy('category_id')
                ->pluck('total', 'category_id')
                ->map(fn ($v) => (float) $v);

        $total = (float) $expenses->sum('amount');
        $prevTotal = (float) $prevTotals->sum();

        // Satu entri per kategori, terbesar di atas
        $cats = $expenses->groupBy('category_id')->map(function ($rows, $catId) use ($names, $prevTotals) {
            $sum = (float) $rows->sum('amount');
            return [
                'name' => $names[$catId] ?? 'Tanpa kategori',
                'total' => $sum,
                'prev' => (float) ($prevTotals[$catId] ?? 0),
                'items' => $rows->groupBy(fn ($e) => $e->item_id ?? 0)
                    ->map(fn ($g) => [
                        'name' => $g->first()->item->name ?? 'Tanpa item',
                        'total' => (float) $g->sum('amount'),
                    ])
                    ->sortByDesc('total')
                    ->values()
                    ->all(),
            ];
        })->filter(fn ($c) => $c['total'] > 0)->sortByDesc('total')->values();

        // Kalau kategori lebih dari batas: sisanya digabung jadi "Lainnya"
        $top = $cats->take(self::MAX_SLICES)->all();
        $rest = $cats->slice(self::MAX_SLICES);
        if ($rest->isNotEmpty()) {
            $top[] = [
                'name' => 'Lainnya',
                'total' => (float) $rest->sum('total'),
                'prev' => (float) $rest->sum('prev'),
                // rincian "Lainnya" = daftar kategori yang digabung
                'items' => $rest->map(fn ($c) => ['name' => $c['name'], 'total' => $c['total']])->values()->all(),
                'other' => true,
            ];
        }

        $slices = [];
        foreach ($top as $i => $c) {
            $slices[] = $c + [
                'pct' => $total > 0 ? round($c['total'] / $total * 100, 1) : 0,
                'color' => ! empty($c['other']) ? self::OTHER_COLOR : self::COLORS[$i % count(self::COLORS)],
                'change' => self::change($c['total'], $c['prev']),
            ];
        }

        // Statistik per bulan untuk mode Semua (hanya bulan yang ada pengeluarannya)
        $stats = [];
        if ($all) {
            $perMonth = $expenses->groupBy(fn ($e) => $e->date->format('Y-m'))
                ->map(fn ($rows) => (float) $rows->sum('amount'))
                ->filter(fn ($v) => $v > 0)
                ->sortKeys();
            $label = fn ($key) => Carbon::createFromFormat('!Y-m', $key)->locale('id')->translatedFormat('M y');
            $maxKey = $perMonth->sortDesc()->keys()->first();
            $minKey = $perMonth->sort()->keys()->first();
            $stats = [
                'all' => true,
                'monthCount' => $perMonth->count(),
                'avgMonth' => $perMonth->count() ? round($perMonth->sum() / $perMonth->count()) : 0,
                'maxMonth' => $maxKey ? ['label' => $label($maxKey), 'total' => $perMonth[$maxKey]] : null,
                'minMonth' => $minKey ? ['label' => $label($minKey), 'total' => $perMonth[$minKey]] : null,
            ];
        }

        return $stats + [
            'total' => $total,
            'prevTotal' => $prevTotal,
            'change' => self::change($total, $prevTotal),
            'prevLabel' => $prevFrom->locale('id')->translatedFormat('M y'),
            'slices' => $slices,
        ];
    }

    // Persen perubahan vs bulan lalu; null kalau bulan lalu kosong (tidak ada pembanding)
    private static function change(float $now, float $prev): ?float
    {
        if ($prev <= 0) {
            return null;
        }
        return round(($now - $prev) / $prev * 100, 0);
    }
}
