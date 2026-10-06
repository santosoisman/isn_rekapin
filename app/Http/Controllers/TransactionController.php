<?php

namespace App\Http\Controllers;

use App\Support\CategoryBreakdown;
use App\Support\MonthTabs;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    // Daftar transaksi per bulan, dikelompokkan per hari — tempat untuk edit/hapus
    public function index(Request $request)
    {
        $book = $request->attributes->get('currentBook');

        ['from' => $from, 'to' => $to, 'months' => $months, 'monthLabel' => $monthLabel] = MonthTabs::make($book, $request);

        $jenis = in_array($request->query('jenis'), ['keluar', 'masuk'], true) ? $request->query('jenis') : 'semua';

        $expenses = $book->expenses()
            ->whereBetween('date', [$from, $to])
            ->with('category', 'item', 'user')
            ->get()
            ->each->setAttribute('tx_type', 'expense');

        $incomes = $book->incomes()
            ->whereBetween('date', [$from, $to])
            ->with('user')
            ->get()
            ->each->setAttribute('tx_type', 'income');

        $monthIncome = (float) $incomes->sum('amount');
        $monthExpense = (float) $expenses->sum('amount');

        $rows = collect();
        if ($jenis !== 'masuk') {
            $rows = $rows->concat($expenses);
        }
        if ($jenis !== 'keluar') {
            $rows = $rows->concat($incomes);
        }

        // Kelompokkan per tanggal (terbaru di atas) + selisih per hari
        $days = $rows
            ->sortByDesc(fn ($tx) => $tx->date->timestamp * 1000000 + $tx->id)
            ->groupBy(fn ($tx) => $tx->date->format('Y-m-d'))
            ->map(fn ($group, $date) => [
                'date' => $group->first()->date,
                'rows' => $group,
                'net' => $group->sum(fn ($tx) => $tx->tx_type === 'income' ? $tx->amount : -$tx->amount),
            ]);

        $categories = $book->categories()->with(['items' => fn ($q) => $q->orderBy('name')])->get();
        $itemsByCategory = $categories->mapWithKeys(fn ($cat) => [$cat->id => $cat->items->pluck('name')]);

        $breakdown = CategoryBreakdown::make($book, $from, $to);

        return view('transactions.index', compact(
            'book', 'months', 'monthLabel', 'jenis', 'days',
            'monthIncome', 'monthExpense', 'categories', 'itemsByCategory', 'breakdown'
        ));
    }
}
