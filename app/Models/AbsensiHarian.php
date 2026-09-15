<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Branch;

class AbsensiHarian extends Model
{
    use HasFactory;

    protected $table = 'daily_attendance';
    protected $guarded = ['id'];

    protected $fillable = [
        'tanggal',
        'id_attendance_in',
        'id_attendance_out',
        'work_time',
        'desc',          // keterangan absen keluar
        'status',
        'updated_by',
        'branch_id',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    // Boot untuk update otomatis updated_by saat admin
    protected static function booted()
    {
        static::updating(function ($model) {
            $user = Auth::user();
            if ($user && $user->id_roles == 1) { // admin
                $model->updated_by = $user->id;
            }
        });

        // Hapus otomatis relasi absen
        static::deleting(function ($absensi) {
            $absensi->absenMasuk?->delete();
            $absensi->absenKeluar?->delete();
        });
    }

    // Relasi ke admin yang update
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getUpdatedNameAttribute()
    {
        return $this->updatedBy?->name ?? '-';
    }

    // Relasi absen masuk
    public function absenMasuk()
    {
        return $this->belongsTo(AbsenMasuk::class, 'id_attendance_in');
    }

    // Relasi absen keluar
    public function absenKeluar()
    {
        return $this->belongsTo(AbsenKeluar::class, 'id_attendance_out');
    }

    // Accessor user — ambil dari absen masuk atau absen keluar
    public function getUserAttribute()
    {
        if ($this->absenMasuk) {
            return $this->absenMasuk->user;
        }

        if ($this->absenKeluar) {
            return $this->absenKeluar->user;
        }

        return null;
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
