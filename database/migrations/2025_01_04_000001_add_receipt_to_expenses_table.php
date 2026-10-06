<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Rincian barang hasil scan struk — hanya untuk dilihat, tidak ikut perhitungan.
    // Bentuk: {"merchant": "...", "items": [{"name": "...", "qty": 1, "price": 18000}]}
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->json('receipt')->nullable()->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('receipt');
        });
    }
};
