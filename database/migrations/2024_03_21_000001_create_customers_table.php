<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('address')->nullable();
            $table->foreignId('membership_type_id')->nullable()->constrained('membership_types')->onDelete('set null');
            $table->enum('membership_status', ['pending', 'approved', 'rejected'])
                ->default('pending');
            $table->timestamp('membership_expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->integer('total_transactions')->default(0);
            $table->integer('total_premium_transactions')->default(0);
            $table->integer('total_discount_approvals')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
