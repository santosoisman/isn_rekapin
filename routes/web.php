<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ReceiptOcrController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

// Sudah login => langsung ke dashboard; belum => tampilkan landing page
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : view('landing');
})->name('landing');

// Login Google
Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');

Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    return redirect()->route('landing');
})->middleware('auth')->name('logout');

// Terima undangan (boleh diakses sebelum login, controller akan redirect ke Google)
Route::get('/invitations/{token}/accept', [InvitationController::class, 'accept'])->name('invitations.accept');

Route::middleware('auth')->group(function () {
    Route::get('/books/create', [BookController::class, 'create'])->name('books.create');
    Route::post('/books', [BookController::class, 'store'])->name('books.store');
    Route::post('/books/{book}/switch', [BookController::class, 'switch'])->name('books.switch');
    Route::delete('/books/{book}/leave', [BookController::class, 'leave'])->name('books.leave');
    Route::delete('/books/{book}', [BookController::class, 'destroy'])->name('books.destroy');

    Route::delete('/invitations/{invitation}', [InvitationController::class, 'cancel'])->name('invitations.cancel');

    Route::middleware('current.book')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');

        Route::post('/invitations', [InvitationController::class, 'store'])->name('invitations.store');

        Route::post('/incomes', [IncomeController::class, 'store'])->name('incomes.store');
        Route::put('/incomes/{income}', [IncomeController::class, 'update'])->name('incomes.update');
        Route::delete('/incomes/{income}', [IncomeController::class, 'destroy'])->name('incomes.destroy');

        Route::post('/ocr/receipt', [ReceiptOcrController::class, 'scan'])->name('ocr.receipt');

        Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
        Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');

        Route::get('/history', [HistoryController::class, 'calendar'])->name('history.calendar');
        Route::get('/history/{date}', [HistoryController::class, 'show'])->name('history.show');

        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        Route::put('/items/{item}', [ItemController::class, 'update'])->name('items.update');
        Route::delete('/items/{item}', [ItemController::class, 'destroy'])->name('items.destroy');

        Route::get('/books/manage', [BookController::class, 'edit'])->name('books.edit');
        Route::put('/books/manage', [BookController::class, 'update'])->name('books.update');
        Route::delete('/books/members/{user}', [BookController::class, 'removeMember'])->name('books.members.remove');
    });
});
