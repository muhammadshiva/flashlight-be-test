<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'chosen_benefit')) {
                $table->enum('chosen_benefit', ['none', 'friend_fd_free', 'friend_fd_discount'])->default('none');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'chosen_benefit')) {
                $table->dropColumn('chosen_benefit');
            }
        });
    }
};
