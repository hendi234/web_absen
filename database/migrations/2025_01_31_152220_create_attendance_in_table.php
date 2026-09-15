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
        Schema::create('attendance_in', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('latitude', 20)->nullable();       // bisa null
            $table->string('longitude', 20)->nullable();      // bisa null
            $table->text('foto')->nullable();                 // bisa null
            $table->text('desc')->nullable();                 // bisa null
            $table->timestamp('time_attendance')->nullable(); // bisa null
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_in');
    }
};
