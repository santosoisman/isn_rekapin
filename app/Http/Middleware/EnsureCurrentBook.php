<?php

namespace App\Http\Middleware;

use App\Models\Book;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Memastikan user punya "buku keuangan" aktif (session current_book_id)
 * sebelum mengakses dashboard/transaksi/history.
 */
class EnsureCurrentBook
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $bookId = session('current_book_id');

        $book = $bookId ? Book::find($bookId) : null;

        // Kalau belum ada buku aktif atau user bukan member buku itu,
        // coba pilih otomatis salah satu buku miliknya.
        if (!$book || !$book->isMember($user)) {
            $book = $user->books()->first();

            if ($book) {
                session(['current_book_id' => $book->id]);
            }
        }

        if (!$book) {
            return redirect()->route('books.create')
                ->with('info', 'Buat buku keuangan terlebih dahulu.');
        }

        // Bagikan buku aktif ke semua view & controller lewat request
        $request->attributes->set('currentBook', $book);
        view()->share('currentBook', $book);

        return $next($request);
    }
}
