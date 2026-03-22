<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('round_player_statuses', function (Blueprint $table) {
            $table->index(['round_id', 'status']);
        });

        Schema::table('rounds', function (Blueprint $table) {
            $table->index('season_round_number');
        });

        Schema::table('pairings', function (Blueprint $table) {
            $table->index(['round_id', 'is_bye']);
        });
    }

    public function down(): void
    {
        Schema::table('round_player_statuses', function (Blueprint $table) {
            $table->dropIndex(['round_id', 'status']);
        });

        Schema::table('rounds', function (Blueprint $table) {
            $table->dropIndex(['season_round_number']);
        });

        Schema::table('pairings', function (Blueprint $table) {
            $table->dropIndex(['round_id', 'is_bye']);
        });
    }
};
