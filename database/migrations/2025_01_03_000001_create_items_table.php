<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Item" = tingkat kedua di bawah kategori (mis. kategori "Dasar" -> item "Galon", "Gas", "Beras").
     * Dibuat otomatis (firstOrCreate) tiap kali user ngetik nama item baru saat mencatat pengeluaran.
     */
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['category_id', 'name']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('item_id')->nullable()->after('category_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('item_id');
        });
        Schema::dropIfExists('items');
    }
};
