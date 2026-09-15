<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\AbsensiHarian;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\SelectColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\AbsensiHarianResource\Pages;
use Filament\Tables\Filters\SelectFilter;

class AbsensiHarianResource extends Resource
{
    protected static ?string $model = AbsensiHarian::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-date-range';
    protected static ?string $navigationGroup = 'Manajemen Absensi';
    protected static ?string $label = 'Rekap Absen Harian';
    protected static ?string $pluralLabel = 'Rekap Absen Harian';
    protected static ?int $navigationSort = 3;

    // ============ TAMBAHAN HELPER TIMEZONE ============
    protected static function getTimezoneForRecord($record): string
    {
        $branch = $record->branch;
        if ($branch && $branch->timezone) {
            return $branch->timezone;
        }

        $user = $record->absenMasuk?->user ?? $record->absenKeluar?->user;
        if ($user && $user->employe && $user->employe->branch) {
            return $user->employe->branch->timezone;
        }

        return config('app.timezone', 'Asia/Jakarta');
    }
    // ==================================================

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'reviewing' => 'Reviewing',
                        'published' => 'Published',
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(
                Auth::user()->id_roles == 2 ? [
                TextColumn::make('user.nip')->label('NIP'),
                TextColumn::make('user.name')->label('Nama Karyawan'),
                TextColumn::make('user.employe.position')->label('Jabatan'),
                TextColumn::make('user.employe.division.name')->label('Divisi'),
                
                // ===== PERBAIKAN CABANG DI SINI (USER) =====
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
                // ===========================================

                TextColumn::make('tanggal_absen')
                    ->label('Tanggal')
                    ->getStateUsing(function ($record) {
                        $timezone = self::getTimezoneForRecord($record);
                        return \Carbon\Carbon::parse($record->created_at)
                            ->setTimezone($timezone)
                            ->translatedFormat('d M Y');
                    }),
                TextColumn::make('absenMasuk.time_attendance')
                    ->label('Waktu Masuk')
                    ->getStateUsing(function ($record) {
                        if (!$record->absenMasuk?->time_attendance) {
                            return 'Belum melakukan absen masuk';
                        }
                        $timezone = self::getTimezoneForRecord($record);
                        return \Carbon\Carbon::parse($record->absenMasuk->time_attendance)
                            ->setTimezone($timezone)
                            ->format('H:i:s');
                    }),
                TextColumn::make('absenMasuk.desc')
                    ->label('Keterangan Masuk')
                    ->getStateUsing(fn($record) =>
                        $record->absenMasuk?->desc ?? 'Belum melakukan absen masuk'),
                TextColumn::make('absenKeluar.time_attendance')
                    ->label('Waktu Keluar')
                    ->getStateUsing(function ($record) {
                        if (!$record->absenKeluar?->time_attendance) {
                            return 'Belum melakukan absen keluar';
                        }
                        $timezone = self::getTimezoneForRecord($record);
                        return \Carbon\Carbon::parse($record->absenKeluar->time_attendance)
                            ->setTimezone($timezone)
                            ->format('H:i:s');
                    }),
                TextColumn::make('absenKeluar.desc')
                    ->label('Keterangan Keluar')
                    ->getStateUsing(fn($record) =>
                        $record->absenKeluar?->desc ?? 'Belum melakukan absen keluar'),
                TextColumn::make('work_time')
                    ->label('Durasi Waktu Kerja')
                    ->getStateUsing(fn($record) => 
                        $record->absenMasuk
                        ? ($record->absenKeluar?->time_attendance 
                        ? $record->work_time 
                        : 'Belum selesai') 
                        : 'Belum selesai'),
                TextColumn::make('lokasi_masuk')
                    ->label('Lokasi Masuk')
                    ->icon('heroicon-o-map-pin')
                    ->getStateUsing(fn($record) => 
                        "<a href='https://www.google.com/maps?q={$record->absenMasuk?->latitude},{$record->absenMasuk?->longitude}' target='_blank'>Maps</a>")
                    ->html()
                    ->tooltip('Klik untuk melihat lokasi'),
                TextColumn::make('lokasi_keluar')
                    ->label('Lokasi Keluar')
                    ->icon('heroicon-o-map-pin')
                    ->getStateUsing(fn($record) => 
                        "<a href='https://www.google.com/maps?q={$record->absenKeluar?->latitude},{$record->absenKeluar?->longitude}' target='_blank'>Maps</a>")
                    ->html()
                    ->tooltip('Klik untuk melihat lokasi'),                
                ImageColumn::make('absenMasuk.foto')->label('Foto Absen')->disk('public'),
                ImageColumn::make('absenKeluar.foto')->label('Foto Keluar')->disk('public'),
                ] : [
                     TextColumn::make('user.nip')
                        ->label('NIP')
                        ->searchable(query: function (Builder $query, string $search): Builder {
                            return $query->whereHas('absenMasuk.user', fn($q) => 
                                $q->where('nip', 'like', "%{$search}%")
                            )->orWhereHas('absenKeluar.user', fn($q) => 
                                $q->where('nip', 'like', "%{$search}%")
                            );
                        }),
                    TextColumn::make('user.name')
                        ->label('Nama Karyawan')
                        ->searchable(query: function (Builder $query, string $search): Builder {
                            return $query->whereHas('absenMasuk.user', fn($q) => 
                                $q->where('name', 'like', "%{$search}%")
                            )->orWhereHas('absenKeluar.user', fn($q) => 
                                $q->where('name', 'like', "%{$search}%")
                            );
                        }),
                    TextColumn::make('user.employe.position')
                        ->label('Jabatan')
                        ->searchable(query: function (Builder $query, string $search): Builder {
                            return $query->whereHas('absenMasuk.user.employe', fn($q) => 
                                $q->where('position', 'like', "%{$search}%")
                            )->orWhereHas('absenKeluar.user.employe', fn($q) => 
                                $q->where('position', 'like', "%{$search}%")
                            );
                        }),
                    TextColumn::make('user.employe.division.name')
                        ->label('Divisi')
                        ->sortable()
                        ->searchable(query: function (Builder $query, string $search): Builder {
                            return $query->whereHas('absenMasuk.user.employe.division', fn($q) => 
                                $q->where('name', 'like', "%{$search}%")
                            )->orWhereHas('absenKeluar.user.employe.division', fn($q) => 
                                $q->where('name', 'like', "%{$search}%")
                            );
                        }),

                    // ===== PERBAIKAN CABANG DI SINI (ADMIN) =====
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
                    // ===========================================

                    TextColumn::make('tanggal_absen')
                        ->label('Tanggal')
                        ->getStateUsing(function ($record) {
                            $timezone = self::getTimezoneForRecord($record);
                            return \Carbon\Carbon::parse($record->created_at)
                                ->setTimezone($timezone)
                                ->translatedFormat('d M Y');
                        }),
                    TextColumn::make('absenMasuk.time_attendance')
                        ->label('Waktu Masuk')
                        ->getStateUsing(function ($record) {
                            if (!$record->absenMasuk?->time_attendance) {
                                return 'Belum melakukan absen masuk';
                            }
                            $timezone = self::getTimezoneForRecord($record);
                            return \Carbon\Carbon::parse($record->absenMasuk->time_attendance)
                                ->setTimezone($timezone)
                                ->format('H:i:s');
                        }),
                    TextColumn::make('absenMasuk.desc')
                        ->label('Keterangan Masuk')
                        ->getStateUsing(fn($record) =>
                            $record->absenMasuk?->desc ?? 'Belum melakukan absen masuk'),
                    TextColumn::make('absenKeluar.time_attendance')
                        ->label('Waktu Keluar')
                        ->getStateUsing(function ($record) {
                            if (!$record->absenKeluar?->time_attendance) {
                                return 'Belum melakukan absen keluar';
                            }
                            $timezone = self::getTimezoneForRecord($record);
                            return \Carbon\Carbon::parse($record->absenKeluar->time_attendance)
                                ->setTimezone($timezone)
                                ->format('H:i:s');
                        }),
                    TextColumn::make('absenKeluar.desc')
                        ->label('Keterangan Keluar')
                        ->getStateUsing(fn($record) =>
                            $record->absenKeluar?->desc ?? 'Belum melakukan absen keluar'),
                    TextColumn::make('work_time')
                        ->label('Durasi Waktu Kerja')
                        ->getStateUsing(fn($record) => 
                            $record->absenMasuk
                            ? ($record->absenKeluar?->time_attendance 
                            ? $record->work_time 
                            : 'Belum selesai') 
                            : 'Belum selesai'),
                    TextColumn::make('lokasi_masuk')
                        ->label('Lokasi Masuk')
                        ->icon('heroicon-o-map-pin')
                        ->getStateUsing(fn($record) => 
                            "<a href='https://www.google.com/maps?q={$record->absenMasuk?->latitude},{$record->absenMasuk?->longitude}' target='_blank'>Maps</a>")
                        ->html()
                        ->tooltip('Klik untuk melihat lokasi'),
                    TextColumn::make('lokasi_keluar')
                        ->label('Lokasi Keluar')
                        ->icon('heroicon-o-map-pin')
                        ->getStateUsing(fn($record) => 
                            "<a href='https://www.google.com/maps?q={$record->absenKeluar?->latitude},{$record->absenKeluar?->longitude}' target='_blank'>Maps</a>")
                        ->html()
                        ->tooltip('Klik untuk melihat lokasi'),                
                    ImageColumn::make('absenMasuk.foto')->label('Foto Absen')->disk('public'),
                    ImageColumn::make('absenKeluar.foto')->label('Foto Keluar')->disk('public'),
                ])
            ->filters([
                Filter::make('tanggal')
                    ->form([
                        DatePicker::make('from')->label('Dari'),
                        DatePicker::make('to')->label('Sampai'),
                    ])
                   ->query(function (Builder $query, array $data) {
                    if (!empty($data['from']) && empty($data['to'])) {
                        return $query->whereDate('created_at', $data['from']);
                    }
                    if (empty($data['from']) && !empty($data['to'])) {
                        return $query->whereDate('created_at', $data['to']);
                    }
                    return $query
                        ->when($data['from'] ?? null, fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
                        ->when($data['to']   ?? null, fn ($q) => $q->whereDate('created_at', '<=', $data['to']));
                }),
                Filter::make('branch_id')
                    ->label('Cabang')
                    ->form([
                        Select::make('branch_id')
                            ->label('Cabang')
                            ->options(\App\Models\Branch::all()->pluck('name', 'id'))
                            ->placeholder('Semua Cabang'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['branch_id'])) {
                            return $query;
                        }

                        return $query->where(function (Builder $q) use ($data) {
                            // Cek branch dari absenMasuk (user.employe.branch)
                            $q->whereHas('absenMasuk.user.employe', function ($q2) use ($data) {
                                $q2->where('branch_id', $data['branch_id']);
                            })
                            // Atau dari absenKeluar (user.employe.branch)
                            ->orWhereHas('absenKeluar.user.employe', function ($q2) use ($data) {
                                $q2->where('branch_id', $data['branch_id']);
                            })
                            // Atau langsung dari branch_id di absensi_harian (jika ada)
                            ->orWhere('branch_id', $data['branch_id']);
                        });
                    }),
            ])
            ->actions([ViewAction::make()])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Data Karyawan')
                    ->icon('heroicon-m-user')
                    ->description('Prevent abuse by limiting the number of requests per period')
                    ->schema([
                        TextEntry::make('user.name')->label('Nama Karyawan'),
                        TextEntry::make('user.nip')->label('NIP'),
                        TextEntry::make('user.employe.position')->label('Jabatan'),
                        TextEntry::make('user.employe.division.name')->label('Divisi'),
                        
                        // ===== PERBAIKAN CABANG DI INFOLIST =====
                        TextEntry::make('branch.name')
                            ->label('Cabang')
                            ->getStateUsing(function ($record) {
                                if ($record->branch) {
                                    return $record->branch->name;
                                }
                                $user = $record->absenMasuk?->user ?? $record->absenKeluar?->user;
                                return $user?->employe?->branch?->name ?? '-';
                            }),
                        // =========================================
                    ]),
                Section::make('Waktu Kerja')
                    ->icon('heroicon-m-clock')
                    ->aside()
                    ->description('Prevent abuse by limiting the number of requests per period')
                    ->schema([
                        TextEntry::make('tanggal_absen')
                            ->label('Tanggal')
                            ->getStateUsing(function ($record) {
                                $timezone = self::getTimezoneForRecord($record);
                                return \Carbon\Carbon::parse($record->created_at)
                                    ->setTimezone($timezone)
                                    ->translatedFormat('d M Y');
                            }),
                        TextEntry::make('absenMasuk.time_attendance')
                            ->label('Waktu Masuk')
                            ->getStateUsing(function ($record) {
                                if (!$record->absenMasuk?->time_attendance) {
                                    return 'Belum melakukan absen masuk';
                                }
                                $timezone = self::getTimezoneForRecord($record);
                                return \Carbon\Carbon::parse($record->absenMasuk->time_attendance)
                                    ->setTimezone($timezone)
                                    ->format('H:i:s');
                            }),
                        TextEntry::make('absenMasuk.desc')
                            ->label('Keterangan Masuk')
                            ->getStateUsing(fn ($record) => $record->absenMasuk?->desc ?? 'Tidak ada keterangan'),
                        TextEntry::make('absenKeluar.time_attendance')
                            ->label('Waktu Keluar')
                            ->getStateUsing(function ($record) {
                                if (!$record->absenKeluar?->time_attendance) {
                                    return 'Belum melakukan absen keluar';
                                }
                                $timezone = self::getTimezoneForRecord($record);
                                return \Carbon\Carbon::parse($record->absenKeluar->time_attendance)
                                    ->setTimezone($timezone)
                                    ->format('H:i:s');
                            }),
                        TextEntry::make('absenKeluar.desc')
                            ->label('Keterangan Keluar')
                            ->getStateUsing(fn ($record) => $record->absenKeluar?->desc ?? 'Tidak ada keterangan'),
                        TextEntry::make('work_time')
                            ->label('Durasi Waktu Kerja')
                            ->getStateUsing(fn ($record) => $record->work_time ?? 'Tidak tersedia'),
                    ]),
                Section::make('Lokasi Kerja')
                    ->icon('heroicon-m-map-pin')
                    ->aside()
                    ->description('Prevent abuse by limiting the number of requests per period')
                    ->schema([
                        TextEntry::make('lokasi_masuk')
                            ->label('Lokasi Masuk')
                            ->icon('heroicon-o-map-pin')
                            ->getStateUsing(fn($record) => 
                                "<a href='https://www.google.com/maps?q={$record->absenMasuk?->latitude},{$record->absenMasuk?->longitude}' target='_blank'>Maps</a>")
                            ->html()
                            ->tooltip('Klik untuk melihat lokasi'),
                        TextEntry::make('lokasi_keluar')
                            ->label('Lokasi Keluar')
                            ->icon('heroicon-o-map-pin')
                            ->getStateUsing(fn($record) => 
                                "<a href='https://www.google.com/maps?q={$record->absenKeluar?->latitude},{$record->absenKeluar?->longitude}' target='_blank'>Maps</a>")
                            ->html()
                            ->tooltip('Klik untuk melihat lokasi'),                
                    ]),
                Section::make('Foto Absen')
                    ->icon('heroicon-m-photo')
                    ->description('Prevent abuse by limiting the number of requests per period')
                    ->schema([
                        ImageEntry::make('absenMasuk.foto')->label('Foto Absen Masuk')->disk('public'),
                        ImageEntry::make('absenKeluar.foto')->label('Foto Absen Keluar')->disk('public'),
                    ])->columns(2),
            ])
            ->columns(1)
            ->inlineLabel();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAbsensiHarians::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);

        $user = Auth::user();

        if ($user) {
            if ($user->id_roles == 2) {
                $query->where(function ($q) use ($user) {
                    $q->whereHas('absenMasuk', fn($q1) => $q1->where('user_id', $user->id))
                    ->orWhereHas('absenKeluar', fn($q2) => $q2->where('user_id', $user->id));
                });
            } elseif ($user->id_roles == 3) {
                $query->whereHas('absenMasuk.user.employe', fn($q1) =>
                    $q1->where('division_id', $user->employe->division_id))
                    ->orWhereHas('absenKeluar.user.employe', fn($q2) =>
                    $q2->where('division_id', $user->employe->division_id));
            }
        }

        $query->orderBy(
            DB::raw("
                COALESCE(
                    (SELECT users.name FROM attendance_in JOIN users ON users.id = attendance_in.user_id WHERE attendance_in.id = daily_attendance.id_attendance_in),
                    (SELECT users.name FROM attendance_out JOIN users ON users.id = attendance_out.user_id WHERE attendance_out.id = daily_attendance.id_attendance_out)
                )
            "),
            'ASC'
        );

        $query->orderBy('created_at', 'DESC');

        $query->orderByRaw("
        SUBSTRING_INDEX(tanggal, ' ', -1) ASC,
        FIELD(
            SUBSTRING_INDEX(SUBSTRING_INDEX(tanggal, ' ', 2), ' ', -1),
            'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'
        ) ASC,
        SUBSTRING_INDEX(tanggal, ' ', 1) ASC");
        
        return $query;
    }
}