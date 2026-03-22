<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('initial_ranking', 'elo_rating');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('knsb_relatienummer', 20)->nullable()->unique()->after('elo_rating');
            $table->boolean('show_rating')->default(false)->after('knsb_relatienummer');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['show_rating', 'knsb_relatienummer']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('elo_rating', 'initial_ranking');
        });
    }
};
