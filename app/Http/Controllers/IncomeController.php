<?php

namespace App\Http\Controllers;

use App\Models\Income;
use Illuminate\Http\Request;

class IncomeController extends Controller
{
    public function store(Request $request)
    {
        $book = $request->attributes->get('currentBook');

        // Form dibuka di buku lain (mis. tab lama) lalu buku aktif berpindah => jangan simpan ke buku yang salah
        if ($request->filled('book_id') && (int) $request->input('book_id') !== (int) $book->id) {
            return back()->withErrors(['book' => 'Buku aktif sudah berubah (mungkin kamu membuka tab lain). Transaksi TIDAK disimpan — muat ulang halaman lalu coba lagi.']);
        }

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'note' => 'nullable|string|max:255',
        ]);

        $book->incomes()->create($data + ['user_id' => $request->user()->id]);

        return back()->with('success', 'Pemasukan berhasil dicatat.');
    }

    public function update(Request $request, Income $income)
    {
        $this->authorize('update', $income);

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'note' => 'nullable|string|max:255',
        ]);

        $income->update($data);

        return back()->with('success', 'Pemasukan berhasil diperbarui.');
    }

    public function destroy(Income $income)
    {
        $this->authorize('delete', $income);

        $income->delete();

        return back()->with('success', 'Pemasukan berhasil dihapus.');
    }
}
