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
        Schema::create('device_fcm_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('device_id')->unique()->comment('Unique device identifier');
            $table->text('fcm_token')->comment('FCM token for this device');
            $table->unsignedBigInteger('last_user_id')->nullable()->comment('Last user who logged in on this device');
            $table->string('device_name')->nullable()->comment('Device name/model');
            $table->string('platform')->nullable()->comment('iOS/Android');
            $table->timestamp('last_used_at')->comment('Last time this token was used');
            $table->boolean('is_active')->default(true)->comment('Whether this token is active');
            $table->timestamps();

            $table->foreign('last_user_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['device_id', 'is_active']);
            $table->index('last_used_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_fcm_tokens');
    }
};
