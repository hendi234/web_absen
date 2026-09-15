<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah id_roles di tabel users
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'id_roles')) {
                $table->foreignId('id_roles')
                      ->nullable()
                      ->constrained('roles')
                      ->cascadeOnDelete();
            }
        });

        // Tambah division_id di tabel employes
        Schema::table('employes', function (Blueprint $table) {
            if (!Schema::hasColumn('employes', 'division_id')) {
                $table->foreignId('division_id')
                      ->nullable()
                      ->constrained('divisions')
                      ->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        // Rollback untuk users
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'id_roles')) {
                $table->dropForeign(['id_roles']);
                $table->dropColumn('id_roles');
            }
        });

        // Rollback untuk employes
        Schema::table('employes', function (Blueprint $table) {
            if (Schema::hasColumn('employes', 'division_id')) {
                $table->dropForeign(['division_id']);
                $table->dropColumn('division_id');
            }
        });
    }
};
