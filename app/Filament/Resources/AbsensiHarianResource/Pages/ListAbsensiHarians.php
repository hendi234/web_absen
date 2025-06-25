<?php

namespace App\Filament\Resources\AbsensiHarianResource\Pages;

use Filament\Actions;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Exports\AbsensiHarianExporter;
use App\Filament\Resources\AbsensiHarianResource;

class ListAbsensiHarians extends ListRecords
{
    protected static string $resource = AbsensiHarianResource::class;

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        
        $actions = [
            Actions\CreateAction::make(), // Tambahkan CreateAction sebagai default
        ];

        // Hanya role 1 (admin misalnya) yang bisa export
    if ($user->id_roles == 1) {
        $actions[] = ExportAction::make()
            ->exporter(AbsensiHarianExporter::class)
            ->label('Export Data')
            ->icon('heroicon-m-document-chart-bar')
            ->color('success')
            ->fileDisk('public');
    }

        return $actions;
    }
}
