<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Verwijder ESG-ratingkolommen en bijbehorende elo_ratings-records.
     */
    public function up(): void
    {
        // Verwijder alle ESG-ratingrecords uit de historietabel
        DB::table('elo_ratings')->where('source', 'esg')->delete();

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'esg_rating')) {
                $table->dropColumn('esg_rating');
            }
            if (Schema::hasColumn('users', 'show_esg_rating')) {
                $table->dropColumn('show_esg_rating');
            }
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('esg_rating')->nullable()->after('elo_rating');
            $table->boolean('show_esg_rating')->default(true)->after('show_knsb_rating');
        });
    }
};
