<?php

namespace App\Support;

use App\Models\Book;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Logika tab bulan yang dipakai bersama dashboard & halaman transaksi:
 * baca ?bulan=YYYY-MM, hitung rentang tanggalnya, dan susun daftar chip bulan.
 */
class MonthTabs
{
    public static function make(Book $book, Request $request): array
    {
        $selected = now()->startOfMonth();
        $param = (string) $request->query('bulan', '');
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $param)) {
            $selected = Carbon::createFromFormat('!Y-m', $param)->startOfMonth();
        }

        // ?bulan=semua → rangkuman seluruh transaksi di buku ini
        $isAll = strtolower($param) === 'semua';
        $selectedKey = $isAll ? 'semua' : $selected->format('Y-m');

        // Chip: bulan yang ada datanya + bulan berjalan + bulan terpilih
        $keys = $book->incomes()->distinct()->pluck('date')
            ->merge($book->expenses()->distinct()->pluck('date'))
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m'))
            ->push(now()->format('Y-m'))
            ->push($selectedKey)
            ->unique()
            ->sort()
            ->values();

        $months = $keys->map(fn ($key) => [
            'key' => $key,
            'label' => Carbon::createFromFormat('!Y-m', $key)->locale('id')->translatedFormat('M y'),
            'active' => $key === $selectedKey,
        ]);

        // Chip "Semua" selalu paling kiri
        $months = $months->prepend(['key' => 'semua', 'label' => 'Semua', 'active' => $isAll])->values();

        return [
            'from' => $isAll ? '1900-01-01' : $selected->copy()->startOfMonth()->toDateString(),
            'to' => $isAll ? '2999-12-31' : $selected->copy()->endOfMonth()->toDateString(),
            'months' => $months,
            'monthLabel' => $isAll ? 'Semua Waktu' : $selected->copy()->locale('id')->translatedFormat('F Y'),
            'isAll' => $isAll,
        ];
    }
}
