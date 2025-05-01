<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Make vehicle fields nullable
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('brand')->nullable()->change();
            $table->string('model')->nullable()->change();
            $table->string('vehicle_type')->nullable()->change();
        });

        // Make wash_transactions staff_id nullable
        Schema::table('wash_transactions', function (Blueprint $table) {
            $table->foreignId('staff_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Revert vehicle fields to non-nullable
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('brand')->nullable(false)->change();
            $table->string('model')->nullable(false)->change();
            $table->string('vehicle_type')->nullable(false)->change();
        });

        // Revert wash_transactions staff_id to non-nullable
        Schema::table('wash_transactions', function (Blueprint $table) {
            $table->foreignId('staff_id')->nullable(false)->change();
        });
    }
};
