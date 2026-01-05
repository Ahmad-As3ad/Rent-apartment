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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apartment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->enum('status', ['pending', 'approved', 'rejected', 'canceled', 'modified_pending'])->default('pending');
            $table->decimal('total_price', 10, 2)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('notes')->nullable();

            $table->dateTime('new_start_date')->nullable();
            $table->dateTime('new_end_date')->nullable();
            $table->timestamp('modified_requested_at')->nullable();
            $table->timestamp('approved_revalidated_at')->nullable();

            $table->timestamps();

            $table->index(['apartment_id', 'start_date', 'end_date', 'status']);
        });
    }

    
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
