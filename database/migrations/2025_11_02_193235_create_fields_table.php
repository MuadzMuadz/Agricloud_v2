<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fields', function (Blueprint $table) {
            $table->id();

            // Relasi ke tabel users (petani/pemilik lahan)
            $table->foreignId('farmer_id')
                ->constrained('users')
                ->onDelete('cascade');

            // Data utama lahan
            $table->string('name'); // nama lahan
            $table->decimal('area', 8, 2); // luas lahan
            $table->string('address'); // alamat
            $table->decimal('latitude', 10, 7)->nullable(); // koordinat
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('description')->nullable(); // keterangan tambahan

            // Status aktif, nonaktif, atau dalam perawatan
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fields');
    }
};
