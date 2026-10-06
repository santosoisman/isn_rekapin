<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    // Tambah kategori pengeluaran custom
    public function store(Request $request)
    {
        $book = $request->attributes->get('currentBook');
        abort_unless($book->isMember($request->user()), 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $book->categories()->create(['name' => $data['name'], 'type' => null]);

        return back()->with('success', 'Kategori "' . $data['name'] . '" berhasil ditambahkan.');
    }

    // Ganti nama kategori
    public function update(Request $request, Category $category)
    {
        abort_unless($category->book->isMember($request->user()), 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category->update(['name' => $data['name']]);

        return back()->with('success', 'Kategori berhasil diperbarui.');
    }

    // Hapus kategori — ditolak kalau masih dipakai transaksi, biar data lama tidak hilang tanpa sadar
    public function destroy(Request $request, Category $category)
    {
        abort_unless($category->book->isMember($request->user()), 403);

        if ($category->expenses()->exists()) {
            return back()->with('info', 'Kategori "' . $category->name . '" masih punya transaksi, tidak bisa dihapus. Pindahkan/hapus transaksinya dulu.');
        }

        $category->delete();

        return back()->with('success', 'Kategori "' . $category->name . '" dihapus.');
    }
}
