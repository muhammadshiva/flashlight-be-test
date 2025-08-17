<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_transactions', function (Blueprint $table) {
            // Add wash transaction integration (replace work_order_id)
            $table->foreignId('wash_transaction_id')->nullable()->after('work_order_id')->constrained()->onDelete('set null');

            // Keep work_order_id for backward compatibility but make it reference wash_transaction's work_order
            // This will be populated automatically via wash_transaction relationship

            // Add payment processing fields
            $table->timestamp('payment_started_at')->nullable()->after('completed_at');
            $table->timestamp('payment_verified_at')->nullable()->after('payment_started_at');

            // Add reference number for better tracking
            $table->string('reference_number')->nullable()->after('transaction_number');

            // Add indexes for better performance
            $table->index(['wash_transaction_id', 'status']);
            $table->index(['payment_method', 'status']);
            $table->index('reference_number');
        });
    }

    public function down(): void
    {
        Schema::table('pos_transactions', function (Blueprint $table) {
            $table->dropForeign(['wash_transaction_id']);
            $table->dropColumn([
                'wash_transaction_id',
                'payment_started_at',
                'payment_verified_at',
                'reference_number'
            ]);
        });
    }
};
