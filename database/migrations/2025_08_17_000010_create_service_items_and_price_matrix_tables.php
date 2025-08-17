<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('engine_classes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // e.g., 100, 200, 600
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('helmet_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('car_sizes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('apparel_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('service_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_main_wash')->default(false);
            $table->boolean('is_premium')->default(false);
            // applies_to: motor, car, helmet, apparel, general
            $table->enum('applies_to', ['motor', 'car', 'helmet', 'apparel', 'general'])->default('motor');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('price_matrix', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_item_id')->constrained('service_items')->cascadeOnDelete();
            $table->foreignId('engine_class_id')->nullable()->constrained('engine_classes')->nullOnDelete();
            $table->foreignId('helmet_type_id')->nullable()->constrained('helmet_types')->nullOnDelete();
            $table->foreignId('car_size_id')->nullable()->constrained('car_sizes')->nullOnDelete();
            $table->foreignId('apparel_type_id')->nullable()->constrained('apparel_types')->nullOnDelete();
            $table->decimal('price', 10, 2);
            $table->timestamps();
            $table->unique(['service_item_id', 'engine_class_id', 'helmet_type_id', 'car_size_id', 'apparel_type_id'], 'uq_price_matrix_combo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_matrix');
        Schema::dropIfExists('service_items');
        Schema::dropIfExists('apparel_types');
        Schema::dropIfExists('car_sizes');
        Schema::dropIfExists('helmet_types');
        Schema::dropIfExists('engine_classes');
    }
};
