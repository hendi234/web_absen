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
        Schema::create('attendance_out', function (Blueprint $table) {
            $table->id();

            // relasi ke user
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // lokasi keluar
            $table->string('latitude', 20);
            $table->string('longitude', 20);

            // foto keluar
            $table->text('foto');

            // ✅ keterangan absensi keluar
            $table->text('desc')->nullable();

            // waktu absen keluar
            $table->timestamp('time_attendance')->default(now());
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_out');
    }
};
