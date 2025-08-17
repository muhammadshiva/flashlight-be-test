<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'work_order_id')) {
                $table->foreignId('work_order_id')
                    ->nullable()
                    ->unique()
                    ->constrained('work_orders')
                    ->restrictOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'work_order_id')) {
                $table->dropConstrainedForeignId('work_order_id');
            }
        });
    }
};
