<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained('periods')->cascadeOnDelete();
            $table->tinyInteger('round_number');
            $table->tinyInteger('season_round_number');
            $table->date('date');
            $table->enum('status', ['scheduled', 'registration_closed', 'paired', 'completed']);
            $table->dateTime('registration_deadline');
            $table->timestamps();

            $table->unique(['period_id', 'round_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rounds');
    }
};
