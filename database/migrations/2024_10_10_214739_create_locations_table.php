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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address');
            $table->string('geolocation');
            $table->integer('max_capacity');
            $table->integer('current_people')->default(0);  // Tracks how many people are at the location
            $table->text('optional_details')->nullable();
            $table->string('picture')->nullable();  // Optional picture for location
            $table->string('qr_code')->nullable();  // Stores the generated QR code
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();  // Disable foreign key checks
        Schema::dropIfExists('locations');       // Drop the table
        Schema::enableForeignKeyConstraints();   // Re-enable foreign key checks
    }

};
