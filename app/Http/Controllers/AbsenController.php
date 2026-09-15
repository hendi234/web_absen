<?php

namespace App\Http\Controllers;

use App\Models\AbsenMasuk;
use App\Models\AbsenKeluar;
use App\Models\AbsensiHarian;
use App\Models\Employe;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AbsenController extends Controller
{
    // Form Absen Masuk
    public function create()
    {
        $absenMasuk = AbsenMasuk::all();
        return view('absensi.masuk', compact('absenMasuk'));
    }

    // Ambil user yang login
    // public function user()
    // {
    //     $user = Filament::auth()->user();
    //     return view('absensi.masuk', compact('user'));
    // }
    public function user()
    {
        $user = Filament::auth()->user();
        
        // Jika user tidak ditemukan, tendang ke login
        if (!$user) {
            return redirect('/absensi/login');
        }

        return view('absensi.masuk', compact('user'));
    }

    // ===========================
    // Absen Masuk
    // ===========================
    public function absenMasuk(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'foto' => 'required|string',
            'desc' => 'required|string|max:255',
            'time_attendance' => 'sometimes|date',
        ], [
            'latitude.required' => 'Lokasi wajib diisi.',
            'longitude.required' => 'Lokasi wajib diisi.',
            'foto.required' => 'Foto wajib diisi.',
            'desc.required' => 'Keterangan wajib diisi.',
        ]);

        $userId = $validated['user_id'];
        $today = Carbon::today();

        // ========== PERBAIKAN: Ambil employee via relasi User ==========
        $user = \App\Models\User::find($userId);
        if (!$user) {
            return back()->with('error', 'User tidak ditemukan.');
        }
        
        $employee = $user->employe; // Langsung ambil via relasi belongsTo
        if (!$employee) {
            return back()->with('error', 'Data karyawan tidak ditemukan. Hubungi admin.');
        }
        
        $branchId = $employee->branch_id;
        if (is_null($branchId)) {
            return back()->with('error', 'Karyawan belum ditugaskan ke cabang. Hubungi admin.');
        }
        // ===============================================================

        // 1. CEK DULU SEBELUM PROSES FOTO
        $sudahMasuk = AbsenMasuk::where('user_id', $userId)
            ->whereDate('time_attendance', $today)
            ->exists();
            
        $sudahKeluar = AbsenKeluar::where('user_id', $userId)
            ->whereDate('time_attendance', $today)
            ->exists();

        if ($sudahMasuk && $sudahKeluar) {
            return back()->with('error', 'Anda sudah menyelesaikan absensi hari ini (masuk dan keluar).');
        }

        if ($sudahKeluar) {
            return back()->with('error', 'Anda sudah absen keluar hari ini, tidak bisa absen masuk lagi.');
        }

        if ($sudahMasuk) {
            return back()->with('error', 'Anda sudah melakukan absen masuk hari ini.');
        }

        // 2. JIKA AMAN, BARU PROSES FOTO
        try {
            $validated['foto'] = $this->processImage($validated['foto']);
            $validated['time_attendance'] = Carbon::now();
            $validated['branch_id'] = $branchId;

            DB::beginTransaction();

            $absenMasuk = AbsenMasuk::create($validated);

            AbsensiHarian::create([
                'tanggal' => now()->toDateString(),
                'id_attendance_in' => $absenMasuk->id,
                'branch_id' => $branchId,
                'desc' => $validated['desc'] ?? null,
                'status' => true,
                'updated_by' => null,
            ]);

            DB::commit();

            return redirect('/absensi/absen-masuks')->with('success', 'Absen Masuk berhasil & tercatat di Absensi Harian');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.');
        }
    }

    // ===========================
    // Absen Keluar
    // ===========================
    public function absenKeluar(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'foto' => 'required|string',
            'desc' => 'nullable|string|max:255',
        ], [
            'latitude.required' => 'Lokasi wajib diisi.',
            'longitude.required' => 'Lokasi wajib diisi.',
            'foto.required' => 'Foto wajib diisi.',
        ]);

        $userId = $validated['user_id'];
        $today = Carbon::today();

        // ========== PERBAIKAN: Ambil employee via relasi User ==========
        $user = \App\Models\User::find($userId);
        if (!$user) {
            return back()->with('error', 'User tidak ditemukan.');
        }
        
        $employee = $user->employe;
        if (!$employee) {
            return back()->with('error', 'Data karyawan tidak ditemukan.');
        }
        
        $branchId = $employee->branch_id;
        if (is_null($branchId)) {
            return back()->with('error', 'Karyawan belum ditugaskan ke cabang.');
        }
        // ===============================================================

        // 1. CEK DULU
        $sudahKeluar = AbsenKeluar::where('user_id', $userId)
            ->whereDate('time_attendance', $today)
            ->exists();

        if ($sudahKeluar) {
            return back()->with('error', 'Anda sudah melakukan absen keluar hari ini.');
        }

        try {
            $validated['foto'] = $this->processImage($validated['foto']);
            $validated['time_attendance'] = now();
            $validated['branch_id'] = $branchId;

            DB::beginTransaction();

            $absenKeluar = AbsenKeluar::create($validated);

            $absenMasuk = AbsenMasuk::where('user_id', $userId)
                ->whereDate('time_attendance', $today)
                ->first();

            if ($absenMasuk) {
                $masukTime = Carbon::parse($absenMasuk->time_attendance);
                $keluarTime = Carbon::parse($validated['time_attendance']);
                $durasi = $keluarTime->diff($masukTime)->format('%H:%I:%S');

                $absensiHarian = AbsensiHarian::where('id_attendance_in', $absenMasuk->id)->first();
                if ($absensiHarian) {
                    $absensiHarian->update([
                        'id_attendance_out' => $absenKeluar->id,
                        'work_time' => $durasi,
                        'status' => false,
                        'desc' => $validated['desc'] ?? $absensiHarian->desc,
                        'updated_by' => null,
                    ]);
                }
            } else {
                AbsensiHarian::create([
                    'tanggal' => now()->toDateString(),
                    'id_attendance_out' => $absenKeluar->id,
                    'branch_id' => $branchId,
                    'desc' => $validated['desc'] ?? null,
                    'status' => false,
                    'updated_by' => null,
                ]);
            }

            DB::commit();
            return redirect('/absensi/absen-keluars')->with('success', 'Absen Keluar berhasil & tercatat di Absensi Harian');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat menyimpan data absen keluar.');
        }
    }

    // ===========================
    // Fungsi helper simpan foto base64
    // ===========================
    private function processImage($base64Image)
    {
        $imageParts = explode(";base64,", $base64Image);
        $base64Image = count($imageParts) === 2 ? $imageParts[1] : $imageParts[0];

        $imageData = base64_decode($base64Image);
        $imageName = uniqid() . '.png';
        Storage::disk('absensi')->put($imageName, $imageData);

        return "absensi_images/{$imageName}";
    }
}