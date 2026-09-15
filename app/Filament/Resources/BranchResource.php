<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BranchResource\Pages;
use App\Models\Branch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static ?string $navigationLabel = 'Cabang';
    protected static ?string $pluralLabel = 'Cabang';
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationGroup = 'Manajemen Karyawan';

    // TIDAK ADA canAccess / shouldRegisterNavigation
    // Karena Filament otomatis membaca BranchPolicy

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Cabang')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('location')
                    ->label('Lokasi')
                    ->maxLength(255),
                Forms\Components\Select::make('timezone')
                    ->label('Zona Waktu')
                    ->options([
                        'Asia/Jakarta' => 'WIB (Tangerang, Jakarta, Sumatera, Jawa)',
                        'Asia/Makassar' => 'WITA (Bali, Makassar, Lombok)',
                        'Asia/Jayapura' => 'WIT (Papua, Maluku)',
                    ])
                    ->default('Asia/Jakarta')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Cabang')
                    ->searchable(),
                Tables\Columns\TextColumn::make('location')
                    ->label('Lokasi')
                    ->searchable(),
                Tables\Columns\TextColumn::make('timezone')
                    ->label('Zona Waktu'),
                // ===== TAMBAHAN KOLOM JUMLAH KARYAWAN =====
                Tables\Columns\TextColumn::make('employees_count')
                    ->label('Jumlah Karyawan')
                    ->counts('employees') // Menghitung relasi employees()
                    ->sortable()
                    ->badge()
                    ->color('success'),
                // ==========================================
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBranches::route('/'),
            'create' => Pages\CreateBranch::route('/create'),
            'edit' => Pages\EditBranch::route('/{record}/edit'),
        ];
    }
}