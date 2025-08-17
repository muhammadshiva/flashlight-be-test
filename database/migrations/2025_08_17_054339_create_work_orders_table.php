<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_vehicle_id')->constrained()->onDelete('cascade');
            $table->float('total_price', 10, 2);
            $table->dateTime('order_date');
            $table->enum('status', ['pending', 'confirmed', 'in_progress', 'ready_for_pickup', 'completed', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->text('special_instructions')->nullable();
            $table->integer('queue_number')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'order_date']);
            $table->index('queue_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
