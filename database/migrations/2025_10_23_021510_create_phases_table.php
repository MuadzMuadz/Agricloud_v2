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
        Schema::create('phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained('cycles')->cascadeOnDelete();
            $table->foreignId('stage_id')->constrained('stages')->cascadeOnDelete();
            $table->foreignId('status_id')->nullable()->constrained('statuses')->nullOnDelete();
            $table->date('started_at')->nullable();
            $table->date('ended_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phases');
    }
};
