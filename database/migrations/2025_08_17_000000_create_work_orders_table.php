<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_orders', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Identitas & antrian
            $table->string('code', 32)->unique();
            $table->unsignedInteger('queue_no');
            $table->date('queue_date');
            $table->unsignedBigInteger('branch_id')->nullable();

            // Relasi pelanggan & kendaraan
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();

            // Status lifecycle di POS
            $table->enum('status', [
                'new',
                'queued',
                'washing',
                'drying',
                'inspection',
                'ready',
                'paid',
                'done',
                'cancelled',
                'on_hold'
            ])->default('queued');

            // Hint membership (indikatif dari kiosk)
            $table->enum('membership_hint_status', ['active', 'non_member', 'unknown'])->nullable();
            $table->enum('membership_hint_type', ['addict', 'friend'])->nullable();
            $table->timestamp('membership_hint_fetched_at')->nullable();

            // Catatan
            $table->text('special_request_note')->nullable();
            $table->text('notes')->nullable();

            // Metadata
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Indeks umum
            $table->index('status', 'idx_work_orders_status');
            $table->index(['queue_date', 'branch_id', 'queue_no'], 'idx_wo_queue_triplet');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
