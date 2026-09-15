<?php

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AbsenController;

Route::get('/', function () {
    return redirect("/absensi/login");
});

// 1. ROUTE ABSEN KELUAR (GET)
// Langsung arahkan ke view. Karena di blade sudah ada pengaman null, ini aman.
Route::get('/absenkeluar', function () {
    return view('absensi.keluar');
})->name('absen-keluar');

// 2. ROUTE ABSEN MASUK (GET)
// Langsung gunakan controller 'user' (Hapus duplikasi 'create' dan closure)
Route::get('/absenmasuk', [AbsenController::class, 'user'])->name('absenmasuk.user');

// 3. ROUTE POST (ACTION FORM)
Route::post('/absenmasuk', [AbsenController::class, 'absenMasuk'])->name('absenmasuk.absenMasuk');
Route::post('/absenkeluar', [AbsenController::class, 'absenKeluar'])->name('absenkeluar.absenKeluar');

// 4. ROUTE ABSENSI HARIAN
Route::get('/absensi-harian', [AbsenController::class, 'getAbsensiHarian'])->name('absensi.harian');