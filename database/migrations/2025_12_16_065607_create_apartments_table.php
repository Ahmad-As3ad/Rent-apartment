<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apartments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->string('address');
            $table->string('city');
            $table->string('region');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('price_per_night', 10, 2);
            $table->integer('number_of_rooms');
            $table->integer('area')->nullable()->comment('Area in square meters');
            $table->boolean('is_available')->default(true);
            $table->boolean('approved_by_admin')->default(false);
            $table->integer('number_of_bathrooms')->after('number_of_rooms')->default(1);
            $table->integer('area')->after('number_of_bathrooms')->nullable()->comment('Area in square meters');
            $table->timestamps();
            $table->index(['city', 'is_available', 'approved_by_admin']);
            $table->index(['price_per_night', 'is_available']);
            $table->index(['owner_id', 'is_available']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apartments');
    }
};
