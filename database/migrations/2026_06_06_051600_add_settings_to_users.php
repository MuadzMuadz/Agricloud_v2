<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menambah kolom preferensi user `settings` (Settings-Backend §7).
     * Menyimpan `{ theme, language, notifications }`; aditif & nullable
     * (null -> FE pakai default) — tidak mengubah/menghapus kolom lama.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('settings')->nullable()->after('email_verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('settings');
        });
    }
};
