<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number')->unique();
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('profile_picture')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('id_card_picture')->nullable();
            $table->enum('user_type', ['owner', 'tenant'])->default('tenant');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamp('profile_completed_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
