<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Change column default to true
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('show_rating')->default(true)->change();
        });

        // Update all existing players to show_rating = true
        DB::table('users')->update(['show_rating' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('show_rating')->default(false)->change();
        });
    }
};
