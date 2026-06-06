<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menambah dukungan login sosial (Integration-GoogleOAuth §4). Hanya
     * menambah kolom & melonggarkan kolom jadi nullable — tidak menghapus
     * apa pun. `email` tetap unique sehingga akun Google dengan email sama
     * ditautkan ke akun lama, bukan diduplikasi.
     *
     * Catatan: `phone_number` ikut dilonggarkan jadi nullable karena user yang
     * mendaftar via Google tidak memiliki nomor telepon (di luar §4 plan, tapi
     * prasyarat agar pembuatan user sosial di §6 bisa berjalan). Tetap unique
     * sehingga banyak user sosial (phone NULL) tidak bentrok.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique();   // sub dari Google
            $table->string('provider')->nullable();              // mis. 'google'
            $table->string('password')->nullable()->change();    // user sosial tak punya password
            $table->string('phone_number')->nullable()->change(); // user sosial tak punya nomor
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_id', 'provider']);
            $table->string('password')->nullable(false)->change();
            $table->string('phone_number')->nullable(false)->change();
        });
    }
};
