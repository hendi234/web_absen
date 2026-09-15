<?php

use App\Traits\BaseModel;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    use BaseModel;
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employes', function (Blueprint $table) {
            $table->id();
            $table->string('nip', 16)->nullable(); 
            $table->string('name', 64);
            $table->text('avatar')->nullable(); 
            $table->string('email', 64)->nullable(); 
            $table->string('position', 64);
            $table->string('education', 64);
            $table->date('join_date');

            $table->foreignId('division_id')
            ->constrained('divisions')
            ->restrictOnDelete(); // atau cascadeOnDelete()
            
            $this->base($table);
        });
        // Update data lama (jika ada)
        DB::table('employes')->whereNull('division_id')->update(['division_id' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employes');
    }
};
