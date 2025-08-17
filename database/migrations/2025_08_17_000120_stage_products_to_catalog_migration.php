<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add mapping columns to products for staged migration
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'service_item_id')) {
                $table->foreignId('service_item_id')->nullable()->after('category_id')->constrained('service_items')->nullOnDelete();
            }
            if (!Schema::hasColumn('products', 'fd_item_id')) {
                $table->foreignId('fd_item_id')->nullable()->after('service_item_id')->constrained('fd_items')->nullOnDelete();
            }
        });

        // Optional: add a flag to product categories to indicate legacy types
        Schema::table('product_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('product_categories', 'legacy_type')) {
                $table->enum('legacy_type', ['service', 'fd', 'other'])->default('service');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'fd_item_id')) {
                $table->dropConstrainedForeignId('fd_item_id');
            }
            if (Schema::hasColumn('products', 'service_item_id')) {
                $table->dropConstrainedForeignId('service_item_id');
            }
        });

        Schema::table('product_categories', function (Blueprint $table) {
            if (Schema::hasColumn('product_categories', 'legacy_type')) {
                $table->dropColumn('legacy_type');
            }
        });
    }
};
