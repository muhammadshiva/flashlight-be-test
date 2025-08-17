<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Optional relations from POS transactions to WO if needed later
        Schema::table('wash_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('wash_transactions', 'work_order_id')) {
                $table->foreignId('work_order_id')->nullable()->constrained('work_orders')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('wash_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('wash_transactions', 'work_order_id')) {
                $table->dropConstrainedForeignId('work_order_id');
            }
        });
    }
};
