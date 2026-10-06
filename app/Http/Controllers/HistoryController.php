<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HistoryController extends Controller
{
    // Halaman kalender
    public function calendar(Request $request)
    {
        $book = $request->attributes->get('currentBook');

        $month = $request->query('month', now()->format('Y-m'));
        [$year, $mon] = explode('-', $month);

        $expenses = $book->expenses()
            ->whereYear('date', $year)
            ->whereMonth('date', $mon)
            ->selectRaw('date, SUM(amount) as total')
            ->groupBy('date')
            ->get()
            ->keyBy(fn ($row) => $row->date->format('Y-m-d'));

        return view('history.calendar', compact('book', 'month', 'expenses'));
    }

    // AJAX: detail transaksi pada satu tanggal (dipanggil dari Alpine saat klik hari)
    public function show(Request $request, string $date)
    {
        $book = $request->attributes->get('currentBook');

        $expenses = $book->expenses()->with('category', 'item')->whereDate('date', $date)->get();
        $incomes = $book->incomes()->whereDate('date', $date)->get();

        return response()->json([
            'date' => $date,
            'incomes' => $incomes,
            'expenses' => $expenses,
            'total_income' => $incomes->sum('amount'),
            'total_expense' => $expenses->sum('amount'),
        ]);
    }
}
