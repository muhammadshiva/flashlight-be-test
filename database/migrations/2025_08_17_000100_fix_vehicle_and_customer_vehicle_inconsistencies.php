<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure vehicles has expected columns only; remove stray constraints if any in future
        Schema::table('vehicles', function (Blueprint $table) {
            if (!Schema::hasColumn('vehicles', 'color')) {
                $table->string('color')->nullable();
            }
        });

        // Plate belongs to customer_vehicles already; ensure unique index exists
        Schema::table('customer_vehicles', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_vehicles', 'license_plate')) {
                $table->string('license_plate')->unique();
            }
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            if (Schema::hasColumn('vehicles', 'color')) {
                $table->dropColumn('color');
            }
        });
    }
};
