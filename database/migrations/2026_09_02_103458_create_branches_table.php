<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name');               // Nama cabang, misal "Jakarta", "Bali"
            $table->string('location')->nullable(); // Opsional, alamat
            $table->string('timezone')->default('Asia/Jakarta'); // Zona waktu
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('branches');
    }
};