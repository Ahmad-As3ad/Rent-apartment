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
            $table->string('password');
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
            Schema::table('users', function (Blueprint $table) {
        $table->enum('status', ['pending', 'approved', 'rejected', 'suspended'])->default('pending')->change();

        $table->timestamp('reviewed_at')->nullable()->after('profile_completed_at');
        $table->text('admin_notes')->nullable()->after('reviewed_at');
        $table->foreignId('reviewed_by')->nullable()->after('admin_notes')->constrained('users')->onDelete('set null');
    });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
          Schema::table('users', function (Blueprint $table) {
        $table->dropColumn(['reviewed_at', 'admin_notes', 'reviewed_by']);
        $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->change();
    });
    }
};
