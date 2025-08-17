<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Drop old pivot table if exists
        if (Schema::hasTable('wash_transaction_products')) {
            Schema::drop('wash_transaction_products');
        }

        // 2) Alter wash_transactions: drop product_id, add main_service_item_id
        Schema::table('wash_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('wash_transactions', 'product_id')) {
                $table->dropConstrainedForeignId('product_id');
            }
            if (!Schema::hasColumn('wash_transactions', 'main_service_item_id')) {
                $table->foreignId('main_service_item_id')->nullable()->after('customer_vehicle_id')->constrained('service_items')->nullOnDelete();
            }
        });

        // 3) Create new pivot tables for services and F&D
        Schema::create('wash_transaction_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wash_transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_item_id')->constrained('service_items')->restrictOnDelete();
            $table->integer('quantity')->default(1);
            $table->decimal('price', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
        });

        Schema::create('wash_transaction_fds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wash_transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fd_item_id')->constrained('fd_items')->restrictOnDelete();
            $table->integer('quantity')->default(1);
            $table->decimal('price', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
        });

        // 4) Drop products and product_categories (legacy)
        if (Schema::hasTable('products')) {
            Schema::drop('products');
        }
        if (Schema::hasTable('product_categories')) {
            Schema::drop('product_categories');
        }
    }

    public function down(): void
    {
        // Reverse new tables
        Schema::dropIfExists('wash_transaction_fds');
        Schema::dropIfExists('wash_transaction_services');

        // Add product_id back (without recreating all legacy tables)
        Schema::table('wash_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('wash_transactions', 'main_service_item_id')) {
                $table->dropConstrainedForeignId('main_service_item_id');
            }
            if (!Schema::hasColumn('wash_transactions', 'product_id')) {
                $table->unsignedBigInteger('product_id')->nullable();
            }
        });
    }
};
