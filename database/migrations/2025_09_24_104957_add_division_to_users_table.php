<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'division_id')) {
                $table->foreignId('division_id')
                      ->nullable()
                      ->constrained('divisions')
                      ->cascadeOnDelete() // 🔑 pakai cascade/restrict, bukan set null
                      ->after('id');
            }
        });

        // Isi data lama dengan divisi default (ID=1)
        DB::table('users')->whereNull('division_id')->update(['division_id' => 1]);

        // Jadikan kolom wajib setelah data lama diisi
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('division_id')->nullable(false)->change();

            // Drop dulu foreign lama biar gak bentrok
            $table->dropForeign(['division_id']);

            // Tambahin foreign key ulang
            $table->foreign('division_id')
                  ->references('id')
                  ->on('divisions')
                  ->cascadeOnDelete(); // atau restrictOnDelete()
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'division_id')) {
                $table->dropForeign(['division_id']);
                $table->dropColumn('division_id');
            }
        });
    }
};
