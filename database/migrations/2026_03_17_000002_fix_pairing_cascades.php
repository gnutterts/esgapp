<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pairings', function (Blueprint $table) {
            $table->dropForeign(['white_user_id']);
            $table->dropForeign(['black_user_id']);
            $table->dropForeign(['bye_user_id']);

            $table->foreign('white_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('black_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('bye_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pairings', function (Blueprint $table) {
            $table->dropForeign(['white_user_id']);
            $table->dropForeign(['black_user_id']);
            $table->dropForeign(['bye_user_id']);

            $table->foreign('white_user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('black_user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('bye_user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
