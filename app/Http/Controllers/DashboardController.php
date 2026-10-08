<?php

namespace App\Http\Controllers;

use App\Support\CategoryBreakdown;
use App\Support\MonthTabs;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $book = $request->attributes->get('currentBook');

        // Total keseluruhan (semua bulan)
        $totalIncome = $book->incomes()->sum('amount');
        $totalExpense = $book->expenses()->sum('amount');
        $saldo = $totalIncome - $totalExpense;

        // Bulan yang dipilih (?bulan=2026-10), default bulan berjalan
        ['from' => $from, 'to' => $to, 'months' => $months, 'monthLabel' => $monthLabel, 'isAll' => $isAll] = MonthTabs::make($book, $request);

        // Data khusus bulan terpilih
        $monthIncomes = $book->incomes()->whereBetween('date', [$from, $to])->get();
        $monthExpenses = $book->expenses()
            ->whereBetween('date', [$from, $to])
            ->with('category', 'item')
            ->get();

        $monthIncome = (float) $monthIncomes->sum('amount');
        $monthExpense = (float) $monthExpenses->sum('amount');

        // Total per kategori & rincian per item, hanya untuk bulan terpilih
        $categories = $book->categories()->get();
        $categoryTotals = $monthExpenses->groupBy('category_id')->map(fn ($rows) => (float) $rows->sum('amount'));

        $itemTotalsByCategory = $monthExpenses->groupBy('category_id')->map(
            fn ($rows) => $rows->groupBy(fn ($e) => $e->item_id ?? 0)
                ->map(fn ($g) => [
                    'name' => $g->first()->item->name ?? 'Tanpa item',
                    'total' => (float) $g->sum('amount'),
                ])
                ->sortByDesc('total')
                ->values()
        );

        // Feed transaksi bulan terpilih (pemasukan + pengeluaran), terbaru di atas
        $recentExpenses = $monthExpenses->map(fn ($e) => (object) [
            'type' => 'expense',
            'date' => $e->date,
            'id' => $e->id,
            'amount' => $e->amount,
            'label' => ($e->item->name ?? '-') . ' (' . ($e->category->name ?? '-') . ')',
            'note' => $e->note,
            'receipt' => $e->receipt,
        ]);

        $recentIncomes = $monthIncomes->map(fn ($i) => (object) [
            'type' => 'income',
            'date' => $i->date,
            'id' => $i->id,
            'amount' => $i->amount,
            'label' => 'Pemasukan',
            'note' => $i->note,
            'receipt' => null,
        ]);

        $allMonthTx = $recentExpenses->concat($recentIncomes)
            ->sortByDesc(fn ($tx) => $tx->date->timestamp * 1000000 + $tx->id)
            ->values();

        $breakdown = CategoryBreakdown::make($book, $from, $to, $isAll);

        $recent = $allMonthTx->take(10);
        $monthTxCount = $allMonthTx->count();

        return view('dashboard', compact(
            'book', 'saldo', 'totalIncome', 'totalExpense',
            'months', 'monthLabel', 'isAll',
            'monthIncome', 'monthExpense',
            'categories', 'categoryTotals', 'itemTotalsByCategory',
            'recent', 'monthTxCount', 'breakdown'
        ));
    }
}
