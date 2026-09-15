<?php

namespace App\Filament\Exports;

use App\Models\AbsensiHarian;
use Illuminate\Support\Carbon;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;

class AbsensiHarianExporter extends Exporter
{
    protected static ?string $model = AbsensiHarian::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('user.nip')
                ->label('NIP')
                ->getStateUsing(function ($record) {
                    $user = $record->absenMasuk?->user ?? $record->absenKeluar?->user;
                    return $user?->nip ?? '-';
                }),

            ExportColumn::make('user.name')
                ->label('Nama Karyawan')
                ->getStateUsing(function ($record) {
                    $user = $record->absenMasuk?->user ?? $record->absenKeluar?->user;
                    return $user?->name ?? '-';
                }),

            ExportColumn::make('user.employe.position')
                ->label('Jabatan')
                ->getStateUsing(function ($record) {
                    $user = $record->absenMasuk?->user ?? $record->absenKeluar?->user;
                    return $user?->employe?->position ?? '-';
                }),

            ExportColumn::make('user.employe.division.name')
                ->label('Divisi')
                ->getStateUsing(function ($record) {
                    $user = $record->absenMasuk?->user ?? $record->absenKeluar?->user;
                    return $user?->employe?->division?->name ?? '-';
                }),

            ExportColumn::make('created_at')
                ->label('Tanggal Absen')
                ->getStateUsing(fn ($record) =>
                    $record->created_at
                        ? Carbon::parse($record->created_at)->translatedFormat('d M Y')
                        : '-'
                ),

            ExportColumn::make('absenMasuk.time_attendance')
                ->label('Waktu Masuk')
                ->getStateUsing(fn ($record) =>
                    $record->absenMasuk?->time_attendance
                        ? Carbon::parse($record->absenMasuk->time_attendance)->format('H:i:s')
                        : 'Belum Absen Masuk'
                ),

            ExportColumn::make('absenKeluar.time_attendance')
                ->label('Waktu Keluar')
                ->getStateUsing(fn ($record) =>
                    $record->absenKeluar?->time_attendance
                        ? Carbon::parse($record->absenKeluar->time_attendance)->format('H:i:s')
                        : 'Belum Absen Keluar'
                ),

            ExportColumn::make('work_time')
                ->label('Durasi Waktu Kerja')
                ->getStateUsing(fn ($record) => $record->work_time ?? 'Belum Selesai'),

            ExportColumn::make('absenMasuk.desc')
                ->label('Keterangan Masuk')
                ->getStateUsing(fn ($record) => $record->absenMasuk?->desc ?? 'Belum Absen Masuk'),

            ExportColumn::make('absenKeluar.desc')
                ->label('Keterangan Keluar')
                ->getStateUsing(fn ($record) => $record->absenKeluar?->desc ?? 'Belum Absen Keluar'),

            ExportColumn::make('lokasi_masuk')
                ->label('Lokasi Masuk')
                ->getStateUsing(fn ($record) =>
                    ($record->absenMasuk?->latitude && $record->absenMasuk?->longitude)
                        ? "https://www.google.com/maps?q={$record->absenMasuk->latitude},{$record->absenMasuk->longitude}"
                        : 'Belum Absen Masuk'
                ),

            ExportColumn::make('lokasi_keluar')
                ->label('Lokasi Keluar')
                ->getStateUsing(fn ($record) =>
                    ($record->absenKeluar?->latitude && $record->absenKeluar?->longitude)
                        ? "https://www.google.com/maps?q={$record->absenKeluar->latitude},{$record->absenKeluar->longitude}"
                        : 'Belum Absen Keluar'
                ),

            ExportColumn::make('status')
                ->label('Status')
                ->getStateUsing(fn ($record) =>
                    $record->status == 0 ? 'Pending' : 'Approve'
                ),

            ExportColumn::make('updatedBy.name')
                ->label('Di Approve Oleh')
                ->getStateUsing(fn ($record) => $record->updatedBy?->name ?? '-'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export absensi harian selesai. ' . number_format($export->successful_rows) . ' data berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' data gagal diekspor.';
        }

        return $body;
    }
}