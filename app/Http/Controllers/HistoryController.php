<?php

namespace App\Http\Controllers;

use App\Support\CategoryBreakdown;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    // Halaman kalender
    public function calendar(Request $request)
    {
        $book = $request->attributes->get('currentBook');

        $month = (string) $request->query('month', now()->format('Y-m'));
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            $month = now()->format('Y-m');
        }
        [$year, $mon] = explode('-', $month);

        $start = Carbon::createFromFormat('!Y-m', $month)->startOfMonth();
        $breakdown = CategoryBreakdown::make($book, $start->toDateString(), $start->copy()->endOfMonth()->toDateString());
        $monthLabel = $start->copy()->locale('id')->translatedFormat('F Y');

        $expenses = $book->expenses()
            ->whereYear('date', $year)
            ->whereMonth('date', $mon)
            ->selectRaw('date, SUM(amount) as total')
            ->groupBy('date')
            ->get()
            ->keyBy(fn ($row) => $row->date->format('Y-m-d'));

        return view('history.calendar', compact('book', 'month', 'expenses', 'breakdown', 'monthLabel'));
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
