<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Storage;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements HasAvatar
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'nip',
        'id_employes',
        'id_roles',
        'avatar_url',
        'division_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'id_roles');
    }

    public function employe()
    {
        return $this->belongsTo(Employe::class, 'id_employes');
    }

    public function division()
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    public function getFilamentAvatarUrl(): ?string
    {
        // Gunakan disk 'public' (atau cukup Storage::url) karena 'karyawan' adalah nama folder di dalam database
        return $this->avatar_url ? Storage::disk('public')->url($this->avatar_url) : null;
    }

    // public function isAdmin()
    // {
    //     return in_array($this->id_roles, [1]);
    // }

    public function isAdmin(): bool
    {
        return $this->id_roles == 1;
    }

    public function isHRD(): bool
    {
        return $this->id_roles == 3;
    }
}
