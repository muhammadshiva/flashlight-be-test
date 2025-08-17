<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wash_transactions', function (Blueprint $table) {
            // Add work order integration
            $table->foreignId('work_order_id')->nullable()->after('id')->constrained()->onDelete('set null');

            // Add service tracking fields
            $table->timestamp('service_started_at')->nullable()->after('wash_date');
            $table->timestamp('service_completed_at')->nullable()->after('service_started_at');
            $table->integer('queue_number')->nullable()->after('service_completed_at');

            // Add service status separate from transaction status
            $table->enum('service_status', ['waiting', 'in_service', 'completed', 'cancelled'])
                ->default('waiting')->after('status');

            // Add indexes for better performance
            $table->index(['work_order_id', 'status']);
            $table->index(['service_status', 'wash_date']);
            $table->index('queue_number');
        });
    }

    public function down(): void
    {
        Schema::table('wash_transactions', function (Blueprint $table) {
            $table->dropForeign(['work_order_id']);
            $table->dropColumn([
                'work_order_id',
                'service_started_at',
                'service_completed_at',
                'queue_number',
                'service_status'
            ]);
        });
    }
};
