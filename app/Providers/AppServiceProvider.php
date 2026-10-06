<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Form tambah transaksi (bottom sheet) ada di semua halaman, jadi daftar
        // kategori & item-nya disuplai di sini, bukan dari tiap controller.
        View::composer('partials.add-sheet', function ($view) {
            $book = request()->attributes->get('currentBook');

            $categories = $book
                ? $book->categories()->with(['items' => fn ($q) => $q->orderBy('name')])->get()
                : collect();

            $view->with('sheetCategories', $categories)
                ->with('sheetItemsByCategory', $categories->mapWithKeys(
                    fn ($cat) => [$cat->id => $cat->items->pluck('name')]
                ));
        });
    }
}
