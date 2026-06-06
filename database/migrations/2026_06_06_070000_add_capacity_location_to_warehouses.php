<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menambahkan kapasitas & titik lokasi gudang (dipakai FE: kapasitas unit
     * dan peta). Aditif & nullable — tidak mengubah kolom yang sudah ada.
     */
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->integer('capacity')->nullable()->after('location');
            $table->decimal('latitude', 10, 7)->nullable()->after('capacity');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropColumn(['capacity', 'latitude', 'longitude']);
        });
    }
};
