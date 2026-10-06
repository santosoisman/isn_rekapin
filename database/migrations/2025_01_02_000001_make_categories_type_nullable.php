<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom 'type' semula enum wajib (dasar/tanpa_rencana/jajan) untuk 3 kategori
     * bawaan. Sekarang dilonggarkan jadi nullable string biar user bisa bikin
     * kategori custom sebebas-bebasnya tanpa terikat enum tersebut.
     *
     * Pakai raw SQL (bukan ->change()) supaya tidak butuh paket doctrine/dbal.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE categories MODIFY type VARCHAR(50) NULL DEFAULT NULL");
        } else {
            // SQLite/lainnya: enum sudah disimpan sebagai varchar, cukup pastikan nullable lewat query builder
            Schema::table('categories', function ($table) {
                $table->string('type')->nullable()->default(null)->change();
            });
        }
    }

    public function down(): void
    {
        // Sengaja tidak dikembalikan ke enum ketat — custom category sudah jadi fitur permanen.
    }
};
