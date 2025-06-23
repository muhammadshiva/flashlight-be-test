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
        Schema::table('wash_transactions', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['staff_id']);

            // Rename the column
            $table->renameColumn('staff_id', 'user_id');

            // Add the new foreign key constraint to users table
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wash_transactions', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['user_id']);

            // Rename the column back
            $table->renameColumn('user_id', 'staff_id');

            // Add the original foreign key constraint
            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('restrict');
        });
    }
};
