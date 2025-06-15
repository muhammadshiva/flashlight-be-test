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
        // Only add the column if it doesn't already exist
        if (!Schema::hasColumn('wash_transactions', 'transaction_number')) {
            Schema::table('wash_transactions', function (Blueprint $table) {
                $table->string('transaction_number')->unique()->after('id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only drop the column if it exists
        if (Schema::hasColumn('wash_transactions', 'transaction_number')) {
            Schema::table('wash_transactions', function (Blueprint $table) {
                $table->dropColumn('transaction_number');
            });
        }
    }
};
