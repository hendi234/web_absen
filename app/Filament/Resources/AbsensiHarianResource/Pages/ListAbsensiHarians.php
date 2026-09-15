<?php

namespace App\Filament\Resources\AbsensiHarianResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\AbsensiHarianResource;
use Filament\Actions\Action;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ListAbsensiHarians extends ListRecords
{
    protected static string $resource = AbsensiHarianResource::class;

    // ============ TAMBAHAN HELPER TIMEZONE ============
    protected function getTimezoneForRecord($record): string
    {
        // Coba ambil dari relasi branch di daily_attendance
        $branch = $record->branch;
        if ($branch && $branch->timezone) {
            return $branch->timezone;
        }

        // Fallback ke branch user
        $user = $record->absenMasuk?->user ?? $record->absenKeluar?->user;
        if ($user && $user->employe && $user->employe->branch) {
            return $user->employe->branch->timezone;
        }

        // Default ke timezone server
        return config('app.timezone', 'Asia/Jakarta');
    }
    // ==================================================

    // Daftar semua kolom yang tersedia (TAMBAH CABANG)
    protected function getAvailableColumns(): array
    {
        return [
            'nip' => 'NIP',
            'nama' => 'Nama Karyawan',
            'jabatan' => 'Jabatan',
            'divisi' => 'Divisi',
            'cabang' => 'Cabang', // <-- TAMBAHAN
            'tanggal' => 'Tanggal Absen',
            'waktu_masuk' => 'Waktu Masuk',
            'waktu_keluar' => 'Waktu Keluar',
            'durasi' => 'Durasi Kerja',
            'keterangan_masuk' => 'Keterangan Masuk',
            'keterangan_keluar' => 'Keterangan Keluar',
            'lokasi_masuk' => 'Lokasi Masuk',
            'lokasi_keluar' => 'Lokasi Keluar',
            'status' => 'Status',
            'approve_by' => 'Di Approve Oleh',
        ];
    }

    // Default kolom yang dipilih (TAMBAH CABANG)
    protected function getDefaultColumns(): array
    {
        return [
            'nip', 
            'nama', 
            'jabatan', 
            'divisi', 
            'cabang', // <-- TAMBAHAN
            'tanggal',
            'waktu_masuk', 
            'waktu_keluar', 
            'durasi',
            'keterangan_masuk',
            'keterangan_keluar',
            'lokasi_masuk',
            'lokasi_keluar'
        ];
    }

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        
        $actions = [
            Actions\CreateAction::make(),
        ];

        if ($user->id_roles == 1) {
            $actions[] = Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-m-document-arrow-down')
                ->color('success')
                ->form([
                    Section::make('Filter Data')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    DatePicker::make('from_date')
                                        ->label('Dari Tanggal')
                                        ->placeholder('Pilih tanggal awal'),
                                    DatePicker::make('to_date')
                                        ->label('Sampai Tanggal')
                                        ->placeholder('Pilih tanggal akhir'),
                                ]),
                            Grid::make(3)
                                ->schema([
                                    Select::make('division_id')
                                        ->label('Divisi')
                                        ->placeholder('Semua Divisi')
                                        ->options(\App\Models\Division::pluck('name', 'id')->toArray())
                                        ->searchable(),
                                    Select::make('position')
                                        ->label('Jabatan')
                                        ->placeholder('Semua Jabatan')
                                        ->options(
                                            \App\Models\Employe::distinct()->pluck('position', 'position')->toArray()
                                        )
                                        ->searchable(),
                                    Select::make('branch_id')
                                        ->label('Cabang')
                                        ->placeholder('Semua Cabang')
                                        ->options(\App\Models\Branch::pluck('name', 'id')->toArray())
                                        ->searchable(),
                                ]),
                            Grid::make(2)
                                ->schema([
                                    \Filament\Forms\Components\Checkbox::make('protect_sheet')
                                        ->label('🔒 Proteksi Sheet (Read-Only)')
                                        ->default(true)
                                        ->helperText('Mencegah pengeditan data di Excel'),
                                    \Filament\Forms\Components\Checkbox::make('protect_structure')
                                        ->label('🔒 Proteksi Struktur')
                                        ->default(true)
                                        ->helperText('Mencegah penambahan/menghapus sheet'),
                                ]),
                        ])
                        ->collapsible(),
                    
                    Section::make('Pilih Kolom yang Diexport')
                        ->description('Centang kolom yang ingin dimasukkan ke Excel')
                        ->schema([
                            CheckboxList::make('columns')
                                ->label('')
                                ->options($this->getAvailableColumns())
                                ->default($this->getDefaultColumns())
                                ->columns(3)
                                ->gridDirection('row')
                                ->helperText('Pilih kolom yang akan muncul di file Excel'),
                        ])
                        ->collapsible(),
                ])
                ->action(function (array $data) {
                    try {
                        ini_set('memory_limit', '2048M');
                        ini_set('max_execution_time', 3600);

                        $selectedColumns = $data['columns'] ?? $this->getDefaultColumns();
                        $protectSheet = $data['protect_sheet'] ?? true;
                        $protectStructure = $data['protect_structure'] ?? true;
                        
                        $fromDate = $data['from_date'] ?? null;
                        $toDate = $data['to_date'] ?? null;
                        $divisionId = $data['division_id'] ?? null;
                        $position = $data['position'] ?? null;
                        $branchId = $data['branch_id'] ?? null;

                        $query = \App\Models\AbsensiHarian::query()
                            ->with([
                                'absenMasuk.user',
                                'absenMasuk.user.employe',
                                'absenMasuk.user.employe.division',
                                'absenKeluar.user',
                                'absenKeluar.user.employe',
                                'absenKeluar.user.employe.division',
                                'updatedBy',
                                'branch'
                            ]);

                        // Filter tanggal
                        if ($fromDate) {
                            $query->whereDate('created_at', '>=', $fromDate);
                        }
                        if ($toDate) {
                            $query->whereDate('created_at', '<=', $toDate);
                        }

                        // Filter Divisi
                        if ($divisionId) {
                            $query->where(function ($q) use ($divisionId) {
                                $q->whereHas('absenMasuk.user.employe', function ($q1) use ($divisionId) {
                                    $q1->where('division_id', $divisionId);
                                })->orWhereHas('absenKeluar.user.employe', function ($q2) use ($divisionId) {
                                    $q2->where('division_id', $divisionId);
                                });
                            });
                        }

                        // Filter Jabatan
                        if ($position) {
                            $query->where(function ($q) use ($position) {
                                $q->whereHas('absenMasuk.user.employe', function ($q1) use ($position) {
                                    $q1->where('position', $position);
                                })->orWhereHas('absenKeluar.user.employe', function ($q2) use ($position) {
                                    $q2->where('position', $position);
                                });
                            });
                        }

                        // Filter Cabang
                        if ($branchId) {
                            $query->where('branch_id', $branchId);
                        }

                        // Filter user jika bukan admin
                        if (auth()->user()->id_roles != 1) {
                            $query->where(function ($q) {
                                $q->whereHas('absenMasuk', function ($q1) {
                                    $q1->where('user_id', auth()->user()->id);
                                })->orWhereHas('absenKeluar', function ($q2) {
                                    $q2->where('user_id', auth()->user()->id);
                                });
                            });
                        }

                        // 🔥 Sorting: Urutkan berdasarkan Nama User lalu Tanggal
                        $query->orderBy(
                            DB::raw("
                                COALESCE(
                                    (
                                        SELECT users.name
                                        FROM attendance_in
                                        JOIN users ON users.id = attendance_in.user_id
                                        WHERE attendance_in.id = daily_attendance.id_attendance_in
                                    ),
                                    (
                                        SELECT users.name
                                        FROM attendance_out
                                        JOIN users ON users.id = attendance_out.user_id
                                        WHERE attendance_out.id = daily_attendance.id_attendance_out
                                    )
                                )
                            "),
                            'ASC'
                        )->orderBy('created_at', 'DESC');

                        $records = $query->limit(50000)->get();
                        
                        if ($records->isEmpty()) {
                            Notification::make()
                                ->title('Export Gagal')
                                ->body('Tidak ada data untuk diekspor')
                                ->danger()
                                ->send();
                            return redirect()->back();
                        }

                        $allData = [];
                        $headers = [];
                        $columnLabels = $this->getAvailableColumns();
                        foreach ($selectedColumns as $col) {
                            $headers[] = $columnLabels[$col] ?? $col;
                        }
                        
                        foreach ($records as $record) {
                            $user = $record->absenMasuk?->user ?? $record->absenKeluar?->user;
                            $row = [];
                            foreach ($selectedColumns as $col) {
                                $row[] = $this->getColumnValue($col, $record, $user);
                            }
                            $allData[] = $row;
                        }

                        // Buat Excel
                        $spreadsheet = new Spreadsheet();
                        $sheet = $spreadsheet->getActiveSheet();
                        $sheet->setTitle('Data Absensi');

                        $colIndex = 'A';
                        foreach ($headers as $header) {
                            $sheet->setCellValue($colIndex . '1', $header);
                            $colIndex++;
                        }

                        $lastColumn = chr(64 + count($headers));
                        
                        // Style Header
                        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A56DB']],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                        ]);

                        $rowIndex = 2;
                        foreach ($allData as $rowData) {
                            $colIndex = 'A';
                            foreach ($rowData as $value) {
                                $sheet->setCellValue($colIndex . $rowIndex, $value);
                                $colIndex++;
                            }
                            $rowIndex++;
                        }

                        foreach (range('A', $lastColumn) as $columnID) {
                            $sheet->getColumnDimension($columnID)->setAutoSize(true);
                        }

                        $sheet->getStyle("A1:{$lastColumn}" . ($rowIndex - 1))->applyFromArray([
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                    'color' => ['rgb' => '000000'],
                                ],
                            ],
                        ]);

                        $sheet->freezePane('A2');
                        $sheet->setAutoFilter("A1:{$lastColumn}" . ($rowIndex - 1));

                        // Proteksi Sheet
                        if ($protectSheet) {
                            $sheet->getStyle("A1:{$lastColumn}" . ($rowIndex - 1))
                                ->getProtection()
                                ->setLocked(Protection::PROTECTION_PROTECTED);
                            
                            $sheet->getProtection()->setSheet(true);
                            $sheet->getProtection()
                                ->setSelectLockedCells(false)
                                ->setSelectUnlockedCells(false)
                                ->setFormatCells(true)
                                ->setFormatColumns(true)
                                ->setFormatRows(true)
                                ->setInsertColumns(true)
                                ->setInsertRows(true)
                                ->setInsertHyperlinks(true)
                                ->setDeleteColumns(true)
                                ->setDeleteRows(true)
                                ->setSort(false)
                                ->setAutoFilter(false)
                                ->setPivotTables(true)
                                ->setObjects(true)
                                ->setScenarios(true);
                        }

                        if ($protectStructure) {
                            $spreadsheet->getSecurity()->setLockWindows(true);
                            $spreadsheet->getSecurity()->setLockStructure(true);
                        }

                        // Sheet Info
                        $infoSheet = $spreadsheet->createSheet();
                        $infoSheet->setTitle('Info');
                        $infoSheet->setCellValue('A1', 'LAPORAN ABSENSI HARIAN');
                        $infoSheet->setCellValue('A3', 'Tanggal Export: ' . Carbon::now()->translatedFormat('d M Y H:i:s'));
                        $infoSheet->setCellValue('A4', 'Total Data: ' . $records->count() . ' records');
                        $infoSheet->setCellValue('A5', 'Diexport oleh: ' . auth()->user()->name);
                        $infoSheet->setCellValue('A6', 'Status: READ-ONLY - Tidak dapat diedit');
                        
                        $infoSheet->getStyle('A1')->applyFromArray([
                            'font' => ['bold' => true, 'size' => 16],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                        ]);
                        $infoSheet->getColumnDimension('A')->setAutoSize(true);
                        
                        if ($protectSheet) {
                            $infoSheet->getProtection()->setSheet(true);
                            $infoSheet->getStyle('A1:A6')
                                ->getProtection()
                                ->setLocked(Protection::PROTECTION_PROTECTED);
                        }

                        $spreadsheet->setActiveSheetIndex(0);

                        $writer = new Xlsx($spreadsheet);
                        $tempFile = tempnam(sys_get_temp_dir(), 'export_') . '.xlsx';
                        $writer->save($tempFile);

                        return response()->download($tempFile, 'Absensi_Harian_' . date('Y-m-d_H-i-s') . '.xlsx', [
                            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])->deleteFileAfterSend(true);

                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Export Gagal')
                            ->body('Error: ' . $e->getMessage())
                            ->danger()
                            ->send();
                        
                        return redirect()->back();
                    }
                });
        }

        return $actions;
    }

    protected function getColumnValue(string $column, $record, $user)
    {
        switch ($column) {
            case 'nip':
                return $user?->nip ?? '-';
            case 'nama':
                return $user?->name ?? '-';
            case 'jabatan':
                return $user?->employe?->position ?? '-';
            case 'divisi':
                return $user?->employe?->division?->name ?? '-';
            case 'cabang':
                // ===== PERBAIKAN CABANG DENGAN FALLBACK =====
                if ($record->branch) {
                    return $record->branch->name;
                }
                return $user?->employe?->branch?->name ?? '-';
                // ============================================
            case 'tanggal':
                // ===== PERBAIKAN TIMEZONE =====
                $timezone = $this->getTimezoneForRecord($record);
                return $record->created_at 
                    ? Carbon::parse($record->created_at)->setTimezone($timezone)->translatedFormat('d M Y') 
                    : '-';
                // ===============================
            case 'waktu_masuk':
                // ===== PERBAIKAN TIMEZONE =====
                if ($record->absenMasuk?->time_attendance) {
                    $timezone = $this->getTimezoneForRecord($record);
                    return Carbon::parse($record->absenMasuk->time_attendance)->setTimezone($timezone)->format('H:i:s');
                }
                return 'Belum Absen Masuk';
                // ===============================
            case 'waktu_keluar':
                // ===== PERBAIKAN TIMEZONE =====
                if ($record->absenKeluar?->time_attendance) {
                    $timezone = $this->getTimezoneForRecord($record);
                    return Carbon::parse($record->absenKeluar->time_attendance)->setTimezone($timezone)->format('H:i:s');
                }
                return 'Belum Absen Keluar';
                // ===============================
            case 'durasi':
                return $record->work_time ?? 'Belum Selesai';
            case 'keterangan_masuk':
                return $record->absenMasuk?->desc ?? 'Belum Absen Masuk';
            case 'keterangan_keluar':
                return $record->absenKeluar?->desc ?? 'Belum Absen Keluar';
            case 'lokasi_masuk':
                return ($record->absenMasuk?->latitude && $record->absenMasuk?->longitude) 
                    ? "https://www.google.com/maps?q={$record->absenMasuk->latitude},{$record->absenMasuk->longitude}" 
                    : 'Belum Absen Masuk';
            case 'lokasi_keluar':
                return ($record->absenKeluar?->latitude && $record->absenKeluar?->longitude) 
                    ? "https://www.google.com/maps?q={$record->absenKeluar->latitude},{$record->absenKeluar->longitude}" 
                    : 'Belum Absen Keluar';
            case 'status':
                return $record->status == 1 ? 'Approve' : 'Pending';
            case 'approve_by':
                return $record->updatedBy?->name ?? '-';
            default:
                return '-';
        }
    }
}