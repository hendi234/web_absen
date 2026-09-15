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
        Schema::table('daily_attendance', function (Blueprint $table) {
            // Ganti 'employee_id' dengan kolom yang benar-benar ada di tabel daily_attendance
            // Misalnya: 'id_attendance_in', 'tanggal', atau bisa dihilangkan
            $table->foreignId('branch_id')->nullable()->after('id_attendance_in')
                  ->constrained('branches')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_attendance', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};