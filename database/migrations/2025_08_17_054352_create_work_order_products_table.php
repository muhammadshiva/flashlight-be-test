<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_order_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->integer('quantity')->default(1);
            $table->float('price', 10, 2); // Price at the time of order
            $table->float('subtotal', 10, 2);
            $table->timestamps();

            $table->unique(['work_order_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order_products');
    }
};
