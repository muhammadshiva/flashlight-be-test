<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number')->unique();
            $table->foreignId('work_order_id')->nullable()->constrained()->onDelete('set null'); // Relation to work order if from kiosk
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_vehicle_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('restrict'); // Cashier who processed
            $table->foreignId('shift_id')->nullable()->constrained()->onDelete('set null');
            $table->float('subtotal', 10, 2);
            $table->float('tax_amount', 10, 2)->default(0);
            $table->float('discount_amount', 10, 2)->default(0);
            $table->float('total_amount', 10, 2);
            $table->enum('payment_method', ['cash', 'qris', 'transfer', 'e_wallet']);
            $table->float('amount_paid', 10, 2);
            $table->float('change_amount', 10, 2)->default(0);
            $table->dateTime('transaction_date');
            $table->enum('status', ['pending', 'completed', 'cancelled', 'refunded'])->default('pending');
            $table->text('notes')->nullable();
            $table->json('receipt_data')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'transaction_date']);
            $table->index(['user_id', 'shift_id']);
            $table->index('work_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_transactions');
    }
};
