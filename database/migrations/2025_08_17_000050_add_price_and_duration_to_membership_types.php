<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membership_types', function (Blueprint $table) {
            if (!Schema::hasColumn('membership_types', 'price')) {
                $table->decimal('price', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('membership_types', 'duration_days')) {
                $table->unsignedInteger('duration_days')->default(30);
            }
        });
    }

    public function down(): void
    {
        Schema::table('membership_types', function (Blueprint $table) {
            if (Schema::hasColumn('membership_types', 'duration_days')) {
                $table->dropColumn('duration_days');
            }
            if (Schema::hasColumn('membership_types', 'price')) {
                $table->dropColumn('price');
            }
        });
    }
};
