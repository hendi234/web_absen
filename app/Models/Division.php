<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Division extends Model
{
    use HasFactory;

    protected $table = 'divisions';

    // IZINKAN MASS ASSIGNMENT
    protected $fillable = [
        'name',
    ];

    // Relasi ke Employe
    public function employes()
    {
        return $this->hasMany(Employe::class, 'division_id');
    }
}
