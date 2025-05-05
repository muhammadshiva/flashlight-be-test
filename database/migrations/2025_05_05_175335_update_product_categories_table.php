<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            if (Schema::hasColumn('product_categories', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('product_categories', 'image')) {
                $table->dropColumn('image');
            }
            if (!Schema::hasColumn('product_categories', 'icon_image')) {
                $table->string('icon_image')->nullable();
            }
            if (!Schema::hasColumn('product_categories', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('product_categories', 'description')) {
                $table->text('description')->nullable();
            }
            if (!Schema::hasColumn('product_categories', 'image')) {
                $table->string('image')->nullable();
            }
            if (Schema::hasColumn('product_categories', 'icon_image')) {
                $table->dropColumn('icon_image');
            }
            if (Schema::hasColumn('product_categories', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
