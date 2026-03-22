<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Split show_rating into show_knsb_rating and show_esg_rating.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('show_knsb_rating')->default(true)->after('show_rating');
            $table->boolean('show_esg_rating')->default(true)->after('show_knsb_rating');
        });

        // Copy existing show_rating value to both new columns
        DB::table('users')->update([
            'show_knsb_rating' => DB::raw('show_rating'),
            'show_esg_rating' => DB::raw('show_rating'),
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('show_rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('show_rating')->default(true)->after('knsb_relatienummer');
        });

        // If either was true, set show_rating to true
        DB::statement('UPDATE users SET show_rating = (show_knsb_rating OR show_esg_rating)');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['show_knsb_rating', 'show_esg_rating']);
        });
    }
};
