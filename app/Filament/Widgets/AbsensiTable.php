<?php

namespace App\Filament\Widgets;

use Filament\Tables\Table;
use App\Models\AbsensiHarian;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter; // <-- TAMBAHAN
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Widgets\TableWidget as BaseWidget;

class AbsensiTable extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 3;

    // ============ TAMBAHAN HELPER TIMEZONE ============
    protected function getTimezoneForRecord($record): string
    {
        // Ambil timezone dari cabang yang terhubung ke absensi harian ini
        $branch = $record->branch;
        if ($branch && $branch->timezone) {
            return $branch->timezone;
        }

        // Fallback ke timezone user (jika relasi branch di absensi harian kosong)
        $user = $record->absenMasuk?->user ?? $record->absenKeluar?->user;
        if ($user && $user->employe && $user->employe->branch) {
            return $user->employe->branch->timezone;
        }

        // Default ke waktu server
        return config('app.timezone', 'Asia/Jakarta');
    }
    // ==================================================

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $query = AbsensiHarian::query();
                $user = Auth::user();

                if ($user) {
                    if ($user->id_roles == 2) {
                        // User biasa: hanya data absensinya sendiri
                        $query->where(function ($q) use ($user) {
                            $q->whereIn('id_attendance_in', function ($subQuery) use ($user) {
                                $subQuery->select('id')
                                    ->from('attendance_in')
                                    ->where('user_id', $user->id);
                            })->orWhereIn('id_attendance_out', function ($subQuery) use ($user) {
                                $subQuery->select('id')
                                    ->from('attendance_out')
                                    ->where('user_id', $user->id);
                            });
                        });
                    } elseif ($user->id_roles == 3) {
                        // HRD: hanya data karyawan dalam divisinya (tambahkan cabang juga jika perlu)
                        $query->where(function ($q) use ($user) {
                            $q->whereHas('absenMasuk.user.employe', function ($sub) use ($user) {
                                $sub->where('division_id', $user->employe->division_id);
                            })->orWhereHas('absenKeluar.user.employe', function ($sub) use ($user) {
                                $sub->where('division_id', $user->employe->division_id);
                            });
                        });
                    }
                    // Admin lihat semua
                }

                // Urutkan dari yang terbaru
                $query->orderBy('created_at', 'desc');

                return $query;
            })
            ->filters([
                Filter::make('tanggal')
                    ->form([
                        DatePicker::make('from')->label('Dari'),
                        DatePicker::make('to')->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn($query) => $query->whereDate('created_at', '>=', $data['from']))
                            ->when($data['to'] ?? null, fn($query) => $query->whereDate('created_at', '<=', $data['to']));
                    }),
                // <-- TAMBAHAN FILTER CABANG (hanya untuk admin & HRD)
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->options(\App\Models\Branch::pluck('name', 'id')->toArray())
                    ->placeholder('Semua Cabang')
                    ->visible(fn () => in_array(Auth::user()->id_roles, [1, 3])), // hanya admin & HRD
            ])
            ->defaultPaginationPageOption(10)
            ->defaultSort('created_at', 'desc')
            ->columns(
                Auth::user()->id_roles == 2 ? [
                    // Kolom untuk user biasa (role 2)
                    TextColumn::make('user.name')->label('Nama Karyawan'),
                    // <-- TAMBAHAN CABANG untuk user biasa (SEKARANG DI-UNCOMMENT & DIPERBAIKI)
                    // TextColumn::make('branch.name')
                    //     ->label('Cabang')
                    //     ->searchable()
                    //     ->sortable()
                    //     ->getStateUsing(function ($record) {
                    //         if ($record->branch) {
                    //             return $record->branch->name;
                    //         }
                    //         // Fallback ke cabang user
                    //         $user = $record->absenMasuk?->user ?? $record->absenKeluar?->user;
                    //         return $user?->employe?->branch?->name ?? '-';
                    //     }),
                    TextColumn::make('tanggal_absen')
                        ->label('Tanggal')
                        ->getStateUsing(function ($record) {
                            $timezone = $this->getTimezoneForRecord($record);
                            return \Carbon\Carbon::parse($record->created_at)
                                ->setTimezone($timezone)
                                ->translatedFormat('d M Y');
                        })
                        ->sortable(),
                    TextColumn::make('absenMasuk.time_attendance')
                        ->label('Waktu Masuk')
                        ->getStateUsing(function ($record) {
                            if (!$record->absenMasuk?->time_attendance) {
                                return 'Belum absen masuk';
                            }
                            $timezone = $this->getTimezoneForRecord($record);
                            return \Carbon\Carbon::parse($record->absenMasuk->time_attendance)
                                ->setTimezone($timezone)
                                ->format('H:i:s');
                        }),
                    TextColumn::make('absenMasuk.desc')
                        ->label('Keterangan Masuk')
                        ->getStateUsing(fn($record) => $record->absenMasuk?->desc ?? '-'),
                    TextColumn::make('absenKeluar.time_attendance')
                        ->label('Waktu Pulang')
                        ->getStateUsing(function ($record) {
                            if (!$record->absenKeluar?->time_attendance) {
                                return 'Belum absen keluar';
                            }
                            $timezone = $this->getTimezoneForRecord($record);
                            return \Carbon\Carbon::parse($record->absenKeluar->time_attendance)
                                ->setTimezone($timezone)
                                ->format('H:i:s');
                        }),
                    TextColumn::make('absenKeluar.desc')
                        ->label('Keterangan Pulang')
                        ->getStateUsing(fn($record) => $record->absenKeluar?->desc ?? '-'),
                    TextColumn::make('work_time')
                        ->label('Durasi Waktu Kerja')
                        ->getStateUsing(fn($record) => 
                            $record->absenMasuk
                            ? ($record->absenKeluar?->time_attendance 
                                ? $record->work_time 
                                : 'Belum selesai')
                            : 'Belum absen masuk'),
                ] : [
                    // Kolom untuk Admin & HRD (role 1 & 3)
                    TextColumn::make('user.nip')
                        ->label('NIP')
                        ->searchable(query: function (Builder $query, string $search): Builder {
                            return $query->where(function ($q) use ($search) {
                                $q->whereHas('absenMasuk.user', function ($sub) use ($search) {
                                    $sub->where('nip', 'like', "%{$search}%");
                                })->orWhereHas('absenKeluar.user', function ($sub) use ($search) {
                                    $sub->where('nip', 'like', "%{$search}%");
                                });
                            });
                        }),

                    TextColumn::make('user.name')
                        ->label('Nama Karyawan')
                        ->searchable(query: function (Builder $query, string $search): Builder {
                            return $query->where(function ($q) use ($search) {
                                $q->whereHas('absenMasuk.user', function ($sub) use ($search) {
                                    $sub->where('name', 'like', "%{$search}%");
                                })->orWhereHas('absenKeluar.user', function ($sub) use ($search) {
                                    $sub->where('name', 'like', "%{$search}%");
                                });
                            });
                        }),

                    TextColumn::make('user.employe.position')
                        ->label('Jabatan')
                        ->searchable(query: function (Builder $query, string $search): Builder {
                            return $query->where(function ($q) use ($search) {
                                $q->whereHas('absenMasuk.user.employe', function ($sub) use ($search) {
                                    $sub->where('position', 'like', "%{$search}%");
                                })->orWhereHas('absenKeluar.user.employe', function ($sub) use ($search) {
                                    $sub->where('position', 'like', "%{$search}%");
                                });
                            });
                        }),

                    TextColumn::make('user.employe.division.name')
                        ->label('Divisi')
                        ->searchable(query: function (Builder $query, string $search): Builder {
                            return $query->where(function ($q) use ($search) {
                                $q->whereHas('absenMasuk.user.employe.division', function ($sub) use ($search) {
                                    $sub->where('name', 'like', "%{$search}%");
                                })->orWhereHas('absenKeluar.user.employe.division', function ($sub) use ($search) {
                                    $sub->where('name', 'like', "%{$search}%");
                                });
                            });
                        })
                        ->sortable(),

                    // <-- TAMBAHAN CABANG UNTUK ADMIN & HRD (SEKARANG DIPERBAIKI DENGAN FALLBACK)
                    TextColumn::make('branch.name')
                        ->label('Cabang')
                        ->searchable()
                        ->sortable()
                        ->getStateUsing(function ($record) {
                            if ($record->branch) {
                                return $record->branch->name;
                            }
                            $user = $record->absenMasuk?->user ?? $record->absenKeluar?->user;
                            return $user?->employe?->branch?->name ?? '-';
                        }),

                    TextColumn::make('tanggal_absen')
                        ->label('Tanggal')
                        ->getStateUsing(function ($record) {
                            $timezone = $this->getTimezoneForRecord($record);
                            return \Carbon\Carbon::parse($record->created_at)
                                ->setTimezone($timezone)
                                ->translatedFormat('d M Y');
                        })
                        ->sortable(),

                    TextColumn::make('absenMasuk.time_attendance')
                        ->label('Waktu Masuk')
                        ->getStateUsing(function ($record) {
                            if (!$record->absenMasuk?->time_attendance) {
                                return 'Belum absen masuk';
                            }
                            $timezone = $this->getTimezoneForRecord($record);
                            return \Carbon\Carbon::parse($record->absenMasuk->time_attendance)
                                ->setTimezone($timezone)
                                ->format('H:i:s');
                        }),

                    TextColumn::make('absenMasuk.desc')
                        ->label('Keterangan Masuk')
                        ->getStateUsing(fn($record) => $record->absenMasuk?->desc ?? '-'),

                    TextColumn::make('absenKeluar.time_attendance')
                        ->label('Waktu Pulang')
                        ->getStateUsing(function ($record) {
                            if (!$record->absenKeluar?->time_attendance) {
                                return 'Belum absen keluar';
                            }
                            $timezone = $this->getTimezoneForRecord($record);
                            return \Carbon\Carbon::parse($record->absenKeluar->time_attendance)
                                ->setTimezone($timezone)
                                ->format('H:i:s');
                        }),

                    TextColumn::make('absenKeluar.desc')
                        ->label('Keterangan Pulang')
                        ->getStateUsing(fn($record) => $record->absenKeluar?->desc ?? '-'),

                    TextColumn::make('work_time')
                        ->label('Durasi Waktu Kerja')
                        ->getStateUsing(fn($record) => 
                            $record->absenMasuk
                            ? ($record->absenKeluar?->time_attendance 
                                ? $record->work_time 
                                : 'Belum selesai')
                            : 'Belum absen masuk'),

                    TextColumn::make('lokasi_masuk')
                        ->label('Lokasi Masuk')
                        ->icon('heroicon-o-map-pin')
                        ->getStateUsing(fn($record) => "<a href='https://www.google.com/maps?q={$record->absenMasuk?->latitude},{$record->absenMasuk?->longitude}' target='_blank'>Lihat Lokasi</a>")
                        ->html()
                        ->tooltip('Klik untuk melihat lokasi'),

                    TextColumn::make('lokasi_keluar')
                        ->label('Lokasi Pulang')
                        ->icon('heroicon-o-map-pin')
                        ->getStateUsing(fn($record) => "<a href='https://www.google.com/maps?q={$record->absenKeluar?->latitude},{$record->absenKeluar?->longitude}' target='_blank'>Lihat Lokasi</a>")
                        ->html()
                        ->tooltip('Klik untuk melihat lokasi'),

                    ImageColumn::make('absenMasuk.foto')
                        ->label('Foto Masuk')
                        ->disk('public'),

                    ImageColumn::make('absenKeluar.foto')
                        ->label('Foto Pulang')
                        ->disk('public'),
                ]
            );
    }
}