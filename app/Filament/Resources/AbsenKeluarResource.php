<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\AbsenKeluar;
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
use App\Filament\Resources\AbsenKeluarResource\Pages;
use App\Filament\Resources\AbsenKeluarResource\RelationManagers;

class AbsenKeluarResource extends Resource
{
    protected static ?string $model = AbsenKeluar::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationGroup = 'Manajemen Absensi';

    protected static ?int $navigationSort = 2;

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
                Hidden::make('attendance_time')
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
                    ->searchable(auth()->user()->id_roles == 1), // Pencarian hanya untuk admin
                TextColumn::make('location') // method untuk menampilkan lokasi absen
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
                    ->url(fn($record) => asset('storage/' . $record->foto))
                    ->label('Foto'),
                TextColumn::make('tanggal_absen')
                    ->label('Tanggal')
                    ->getStateUsing(fn ($record) => \Carbon\Carbon::parse($record->time_attendance)->translatedFormat('d M Y'))
                    ->sortable(),
                TextColumn::make('time_attendance')
                    ->label('Waktu')
                    ->dateTime('H:i:s')
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
                    ->url(fn () => url('/absenkeluar'))
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
                TextEntry::make('location') // method untuk menampilkan lokasi absen
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
                TextEntry::make('tanggal_absen')
                    ->label('Tanggal')
                    ->getStateUsing(fn ($record) => \Carbon\Carbon::parse($record->time_attendance)->translatedFormat('d M Y')),
                TextEntry::make('time_attendance')
                    ->label('Waktu')
                    ->dateTime('H:i:s'),
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
            'index' => Pages\ListAbsenKeluars::route('/'),
            'create' => Pages\CreateAbsenKeluar::route('/create'),
            'edit' => Pages\EditAbsenKeluar::route('/{record}/edit'),
        ];
    }

    // method untuk memfilter data berdasarkan user yang sedang login
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    
        if (Auth::check() && Auth::user()->role->id === 2) {
            $query->where('user_id', Auth::id());
        }
    
        return $query;
    }
}
