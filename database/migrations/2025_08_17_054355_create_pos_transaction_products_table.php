<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_transaction_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_transaction_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->integer('quantity')->default(1);
            $table->float('price', 10, 2); // Price at the time of transaction
            $table->float('subtotal', 10, 2);
            $table->timestamps();

            $table->unique(['pos_transaction_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_transaction_products');
    }
};
