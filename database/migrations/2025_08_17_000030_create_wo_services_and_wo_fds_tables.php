<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wo_services', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('work_order_id')->constrained('work_orders')->cascadeOnDelete();
            $table->foreignId('service_item_id')->constrained('service_items')->restrictOnDelete();
            $table->unsignedSmallInteger('qty')->default(1);
            $table->integer('unit_price');
            $table->boolean('is_custom')->default(false);
            $table->string('custom_label', 100)->nullable();
            $table->boolean('is_premium_snapshot')->default(false);
            $table->timestamps();
            $table->index('work_order_id', 'idx_wos_wo');
        });

        Schema::create('wo_fds', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('work_order_id')->constrained('work_orders')->cascadeOnDelete();
            $table->foreignId('fd_item_id')->constrained('fd_items')->restrictOnDelete();
            $table->unsignedSmallInteger('qty')->default(1);
            $table->integer('unit_price');
            $table->timestamps();
            $table->index('work_order_id', 'idx_wof_wo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wo_fds');
        Schema::dropIfExists('wo_services');
    }
};
