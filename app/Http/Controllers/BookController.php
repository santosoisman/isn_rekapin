<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function create()
    {
        return view('books.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $book = Book::create([
            'name' => $data['name'],
            'owner_id' => $request->user()->id,
        ]);

        $book->members()->attach($request->user()->id, ['role' => 'owner']);

        foreach (Category::TYPES as $type => $label) {
            $book->categories()->create(['name' => $label, 'type' => $type]);
        }

        session(['current_book_id' => $book->id]);

        return redirect()->route('dashboard')->with('success', 'Buku keuangan "' . $book->name . '" berhasil dibuat.');
    }

    public function switch(Book $book, Request $request)
    {
        abort_unless($book->isMember($request->user()), 403);

        session(['current_book_id' => $book->id]);

        return redirect()->route('dashboard');
    }

    // Halaman kelola buku: rename, daftar member, daftar undangan pending
    public function edit(Request $request)
    {
        $book = $request->attributes->get('currentBook');
        $this->authorize('view', $book);

        $book->load(['members', 'invitations' => fn ($q) => $q->where('status', 'pending')]);
        $book->load(['categories' => fn ($q) => $q->withCount('expenses')->with(['items' => fn ($qi) => $qi->withCount('expenses')->orderBy('name')])]);

        return view('books.edit', compact('book'));
    }

    public function update(Request $request)
    {
        $book = $request->attributes->get('currentBook');
        $this->authorize('update', $book);

        $data = $request->validate(['name' => 'required|string|max:255']);
        $book->update($data);

        return back()->with('success', 'Nama buku berhasil diperbarui.');
    }

    public function removeMember(Request $request, \App\Models\User $user)
    {
        $book = $request->attributes->get('currentBook');
        $this->authorize('removeMember', $book);

        if ($user->id === $book->owner_id) {
            abort(422, 'Owner tidak bisa dikeluarkan dari buku.');
        }

        $book->members()->detach($user->id);

        return back()->with('success', $user->name . ' dikeluarkan dari buku.');
    }

    // Hapus buku (khusus owner) — wajib ketik nama buku. Data terkait ikut terhapus lewat cascade FK.
    public function destroy(Request $request, Book $book)
    {
        $this->authorize('delete', $book);

        $request->validate(['confirm_name' => 'required|string']);

        if (trim($request->input('confirm_name')) !== $book->name) {
            return back()->withErrors(['confirm_name' => 'Nama buku yang diketik tidak sama, buku tidak dihapus.']);
        }

        $name = $book->name;
        $book->delete();

        if ((int) session('current_book_id') === (int) $book->id) {
            session()->forget('current_book_id');
        }

        return redirect()->route('dashboard')->with('success', 'Buku "' . $name . '" dihapus.');
    }

    // Keluar dari buku milik orang lain (member biasa)
    public function leave(Request $request, Book $book)
    {
        $user = $request->user();

        abort_unless($book->isMember($user), 403);
        abort_if($book->owner_id === $user->id, 422, 'Owner tidak bisa keluar dari bukunya sendiri. Hapus bukunya kalau sudah tidak dipakai.');

        $book->members()->detach($user->id);

        if ((int) session('current_book_id') === (int) $book->id) {
            session()->forget('current_book_id');
        }

        return redirect()->route('dashboard')->with('success', 'Kamu keluar dari buku "' . $book->name . '".');
    }
}
