<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Division; // ⬅️ ini yang kurang
use App\Models\Role;      // 🔥 tambahkan ini
use App\Models\Employe;   // kalau dipakai

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
            // Jalankan RoleSeeder dulu
            $this->call(RoleSeeder::class);
    
            // Kalau ada seeder division dan employe, panggil juga
            $this->call(DivisionSeeder::class);
            $this->call(EmployeSeeder::class);
    
            // Ambil role admin
            $roleAdmin = Role::where('name', 'admin')->first();
    
            // Ambil division & employe pertama (biar nggak null)
            $division = Division::first();
            $employe  = Employe::first();
    
            User::create([
                'name' => 'Admin',
                'nip' => '1234',
                'email' => 'admin@gmail.com',
                'password' => bcrypt('12345678'),
                'role' => 'admin',
                'division_id' => $division?->id,   // pakai null safe operator
                'id_employes' => $employe?->id,    // biar tidak error kalau kosong
                'id_roles' => $roleAdmin?->id,     // aman walau null
            ]);
    }
}
