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
        Schema::create('daily_attendance', function (Blueprint $table) {
            $table->id();

            // relasi ke absen masuk
            $table->unsignedBigInteger('id_attendance_in')->nullable();
            $table->foreign('id_attendance_in')
                ->references('id')
                ->on('attendance_in')
                ->onDelete('CASCADE')
                ->onUpdate('CASCADE');

            // relasi ke absen keluar
            $table->unsignedBigInteger('id_attendance_out')->nullable();
            $table->foreign('id_attendance_out')
                ->references('id')
                ->on('attendance_out')
                ->onDelete('CASCADE')
                ->onUpdate('CASCADE');

            // tanggal absensi
            $table->date('tanggal')->default(now());

            // durasi kerja (nullable)
            $table->time('work_time')->nullable();

            // ✅ keterangan absensi harian (misal gabungan dari masuk & keluar)
            $table->text('desc')->nullable();

            // status absensi (true = sedang kerja, false = selesai)
            $table->boolean('status')->default(false);

            // update oleh siapa (nullable)
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_attendance');
    }
};
