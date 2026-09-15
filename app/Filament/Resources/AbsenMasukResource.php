<?php

namespace App\Filament\Resources;

use Filament\Tables;
use Filament\Forms\Form;
use App\Models\AbsenMasuk;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\AbsenMasukResource\Pages;

class AbsenMasukResource extends Resource
{
    protected static ?string $model = AbsenMasuk::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationGroup = 'Manajemen Absensi';

    // Helper untuk mengambil timezone cabang user yang melakukan absen
    protected static function getTimezoneForRecord($record): string
    {
        $user = $record->user ?? null;
        if ($user && $user->employe && $user->employe->branch) {
            return $user->employe->branch->timezone ?? 'Asia/Jakarta';
        }
        return config('app.timezone', 'Asia/Jakarta');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Hidden::make('user_id')
                    ->default(Auth::id()),
                TextInput::make('latitude')
                    ->label('Latitude')
                    ->default(fn () => request()->input('latitude'))
                    ->required(),
                TextInput::make('longitude')
                    ->label('Longitude')
                    ->default(fn () => request()->input('longitude'))
                    ->required(),
                FileUpload::make('foto')
                    ->required()
                    ->image()
                    ->disk('absensi')
                    ->columnSpanFull(),
                Textarea::make('desc')
                    ->required()
                    ->columnSpanFull(),
                Hidden::make('time_attendance')
                    ->default(now()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Nama')
                    ->sortable()
                    ->searchable(auth()->user()->id_roles == 1),
                TextColumn::make('location')
                    ->label('Lokasi')
                    ->badge()
                    ->color('blue')
                    ->icon('heroicon-o-map-pin')
                    ->getStateUsing(fn ($record) => 
                        "<a href='https://www.google.com/maps?q={$record->latitude},{$record->longitude}' target='_blank'>Lihat Lokasi</a>")
                    ->html()
                    ->tooltip('Klik untuk melihat lokasi'),
                ImageColumn::make('foto')
                    ->disk('public')
                    ->height(80)
                    ->url(fn($record) => asset('storage/' . $record->foto)),
                TextColumn::make('desc')
                    ->label('Keterangan'),
                TextColumn::make('tanggal_absen')
                    ->label('Tanggal')
                    ->getStateUsing(function ($record) {
                        $timezone = self::getTimezoneForRecord($record);
                        return \Carbon\Carbon::parse($record->time_attendance)
                            ->setTimezone($timezone)
                            ->translatedFormat('d M Y');
                    })
                    ->sortable(),
                TextColumn::make('time_attendance')
                    ->label('Waktu')
                    ->getStateUsing(function ($record) {
                        $timezone = self::getTimezoneForRecord($record);
                        return \Carbon\Carbon::parse($record->time_attendance)
                            ->setTimezone($timezone)
                            ->format('H:i:s');
                    })
                    ->sortable(),
            ])
            ->filters([
                Filter::make('tanggal_absen')
                ->form([
                    DatePicker::make('from')->label('Dari'),
                    DatePicker::make('to')->label('Sampai'),
                ])
                ->query(function (Builder $query, array $data) {
                    return $query
                        ->when($data['from'] ?? null, fn ($query) => 
                            $query->whereDate('time_attendance', '>=', $data['from'])
                        )
                        ->when($data['to'] ?? null, fn ($query) => 
                            $query->whereDate('time_attendance', '<=', $data['to'])
                        );
                }),
            ])
            ->actions([
                EditAction::make(),
                ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions(array_filter([
                auth()->user()->id_roles == 2 ? 
                    Action::make('create')
                        ->label('Tambah Presensi')
                        ->icon('heroicon-o-plus')
                        ->color('success')
                        ->url(fn () => url('/absenmasuk'))
                        ->openUrlInNewTab(false)
                    : null,
            ]));            
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('user.name')
                    ->label('Nama'),
                TextEntry::make('location')
                    ->label('Lokasi')
                    ->badge()
                    ->color('blue')
                    ->icon('heroicon-o-map-pin')
                    ->getStateUsing(fn ($record) => 
                        "<a href='https://www.google.com/maps?q={$record->latitude},{$record->longitude}' target='_blank'>Lihat Lokasi</a>")
                    ->html(),
                ImageEntry::make('foto')
                    ->disk('public')
                    ->height(80)
                    ->url(fn($record) => asset('storage/' . $record->foto)),
                TextEntry::make('desc')
                    ->label('Keterangan'),
                TextEntry::make('tanggal_absen')
                    ->label('Tanggal')
                    ->getStateUsing(function ($record) {
                        $timezone = self::getTimezoneForRecord($record);
                        return \Carbon\Carbon::parse($record->time_attendance)
                            ->setTimezone($timezone)
                            ->translatedFormat('d M Y');
                    }),
                TextEntry::make('time_attendance')
                    ->label('Waktu')
                    ->getStateUsing(function ($record) {
                        $timezone = self::getTimezoneForRecord($record);
                        return \Carbon\Carbon::parse($record->time_attendance)
                            ->setTimezone($timezone)
                            ->format('H:i:s');
                    }),
            ])
            ->columns(1)
            ->inlineLabel();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAbsenMasuks::route('/'),
            'create' => Pages\CreateAbsenMasuk::route('/absenmasuk'),
            'edit' => Pages\EditAbsenMasuk::route('/{record}/edit'),
        ];
    } 

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    
        $user = Auth::user();
    
        if ($user) {
            if ($user->id_roles == 2) {
                $query->where('user_id', $user->id);
            } elseif ($user->id_roles == 3) {
                $query->whereHas('user.employe', function ($q) use ($user) {
                    $q->where('division_id', $user->employe->division_id);
                });
            }
        }

        $table = $query->getModel()->getTable();
            $query->join('users', 'users.id', '=', $table . '.user_id')
                ->orderBy($table . '.time_attendance', 'desc')
                ->orderBy('users.name', 'asc')
                ->select($table . '.*');
    
        return $query;
    }
}