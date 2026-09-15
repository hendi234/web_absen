<?php

namespace App\Filament\Widgets;

use App\Models\Employe;
use Illuminate\Support\Carbon;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;
use App\Models\AbsensiHarian;
use App\Models\User;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        $tanggal = Carbon::now()->translatedFormat('d M Y');
        $jam = Carbon::now()->translatedFormat('H:i:s');

        $stats = [];

        // Semua role
        $stats[] = Stat::make('Selamat Beraktivitas', $jam)
            ->description($tanggal)
            ->descriptionIcon('heroicon-m-calendar-days', IconPosition::Before)
            ->color('success')
            ->icon('heroicon-s-bell-alert')
            ->chart([7, 2, 10, 3, 15, 4, 17]);

        // Admin
        if ($user->id_roles === 1) {
            $karyawan = User::where('id_roles', 2)->count();
            $absensiHarian = AbsensiHarian::count(); // ambil semua data, bukan cuma hari ini
            $sudahAcc = AbsensiHarian::where('status', 1)->count();
            $belumAcc = AbsensiHarian::where('status', 0)->count();
            $kepalaDivisi = User::where('id_roles', 3)->count();

            $stats[] = Stat::make('Jumlah Karyawan', $karyawan . ' Karyawan')
                ->description('Total Data karyawan')
                ->color('success')
                ->icon('heroicon-s-users')
                ->chart([5, 10, 15, 20, $karyawan, 10, 5]);

            $stats[] = Stat::make('Rekap Absensi Harian', $absensiHarian . ' Data')
                ->description('Total rekap absensi harian')
                ->color('success')
                ->icon('heroicon-s-calendar-days')
                ->chart([3, 4, 6, 5, $absensiHarian, 7, 6]);

                // $stats[] = Stat::make('Sudah ACC', $sudahAcc . ' Orang')
                //     ->description('Absensi yang sudah disetujui')
                //     ->color('success')
                //     ->icon('heroicon-s-check-circle')
                //     ->chart([$sudahAcc, 0, 1, 0, 2, 0, 1]);

                // $stats[] = Stat::make('Belum ACC', $belumAcc . ' Orang')
                //     ->description('Menunggu persetujuan ACC')
                //     ->color('danger')
                //     ->icon('heroicon-s-exclamation-circle')
                //     ->chart([0, $belumAcc, 0, 0, 0, 0, 0]);

                // $stats[] = Stat::make('Kepala Divisi', $kepalaDivisi . ' Orang')
                //     ->description('Jumlah Data pengguna')
                //     ->color('info')
                //     ->icon('heroicon-s-user-circle')
                //     ->chart([0, 0, $kepalaDivisi, 0, 0, 0, 0]);
        }

        // Karyawan
        if ($user->id_roles === 2) {
            // Sesuaikan jam dengan timezone cabang
            $branch = $user->employe->branch ?? null;
            $timezone = $branch->timezone ?? 'Asia/Jakarta';
            
            $carbon = Carbon::now($timezone);
            $tanggalUser = $carbon->translatedFormat('d M Y');
            $jamUser = $carbon->translatedFormat('H:i:s');

            // Update stat pertama dengan jam yang sesuai cabang
            $stats[0] = Stat::make('Selamat Beraktivitas', $jamUser)
                ->description($tanggalUser)
                ->descriptionIcon('heroicon-m-calendar-days', IconPosition::Before)
                ->color('success')
                ->icon('heroicon-s-bell-alert')
                ->chart([7, 2, 10, 3, 15, 4, 17]);

            $stats[] = Stat::make('', 'Absen Masuk')
                ->description('Klik Untuk Absen Masuk')
                ->descriptionIcon('heroicon-m-user', IconPosition::Before)
                ->extraAttributes([
                    'class' => 'cursor-pointer text-primary font-bold',
                    'onclick' => "window.location.href='/absenmasuk'",
                    'style' => 'background-color: #22c55e; color: white;'
                ])
                ->color('white')
                ->chart([7, 2, 10, 3, 15, 4, 17]);

            $stats[] = Stat::make('', 'Absensi Pulang')
                ->description('Klik Untuk Absen Pulang')
                ->descriptionIcon('heroicon-m-user', IconPosition::Before)
                ->extraAttributes([
                    'class' => 'cursor-pointer text-primary font-bold',
                    'onclick' => "window.location.href='/absenkeluar'",
                    'style' => 'background-color: #ef4444; color: white;'
                ])
                ->color('white')
                ->chart([7, 2, 10, 3, 15, 4, 17]);
        }

        // Kepala Divisi / HRD
        if ($user->id_roles === 3) {
            $divisionId = $user->employe->division_id ?? null;
            $divisionName = $user->employe->division->name ?? '-';

            $absenHariIni = AbsensiHarian::whereHas('user.employe', function ($q) use ($divisionId) {
                $q->where('division_id', $divisionId);
            })->count();

            $belumAcc = AbsensiHarian::where('status', 0)
                ->whereHas('user.employe', function ($q) use ($divisionId) {
                    $q->where('division_id', $divisionId);
                })->count();

            $stats[] = Stat::make('Total Rekap Absensi', $absenHariIni . ' Karyawan')
                ->description("Data Rekap absensi divisi {$divisionName}")
                ->descriptionIcon('heroicon-o-user-group')
                ->icon('heroicon-s-clipboard-document-check')
                ->color('success')
                ->chart([7, 5, 12, 4, 15, 6, 9]);

            $stats[] = Stat::make('Data Absen Belum Di-ACC', $belumAcc . ' Data')
                ->description("Menunggu persetujuan divisi {$divisionName}")
                ->descriptionIcon('heroicon-s-clock')
                ->icon('heroicon-s-exclamation-triangle')
                ->color('warning')
                ->chart([3, 4, 2, 5, 6, 2, 1]);
        }

        return $stats;
    }
}