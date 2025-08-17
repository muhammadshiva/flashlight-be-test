<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure payments.method supports transfer/e_wallet
        // Using enum change via DB statement to be safe across platforms
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN method ENUM('cash','qris','transfer','e_wallet') NOT NULL");
        } else {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('method')->change();
            });
        }

        // payments.staff_id exists, align to users table name if needed
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'staff_id')) {
                $table->dropConstrainedForeignId('staff_id');
                $table->foreignId('user_id')->after('wash_transaction_id')->constrained('users')->restrictOnDelete();
            } elseif (!Schema::hasColumn('payments', 'user_id')) {
                $table->foreignId('user_id')->after('wash_transaction_id')->constrained('users')->restrictOnDelete();
            }
        });
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN method ENUM('cash','qris') NOT NULL");
        }

        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }
        });
    }
};
