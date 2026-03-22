<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pairings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('round_id')->constrained('rounds')->cascadeOnDelete();
            $table->integer('board_number');
            $table->foreignId('white_user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('black_user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->enum('result', ['1-0', '0-1', 'remise'])->nullable();
            $table->boolean('is_bye')->default(false);
            $table->foreignId('bye_user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pairings');
    }
};
