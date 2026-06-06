<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menambah setelan lokasi cuaca pada user (Integration-Weather §10–11).
     * Semua kolom aditif & nullable — tidak mengubah/menghapus kolom lama.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('weather_mode')->nullable();        // 'manual' | 'device'
            $table->string('weather_district')->nullable();    // nama kecamatan (+ kota) untuk display
            $table->decimal('weather_lat', 10, 7)->nullable(); // titik tengah kecamatan
            $table->decimal('weather_lon', 10, 7)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['weather_mode', 'weather_district', 'weather_lat', 'weather_lon']);
        });
    }
};
