<?php

namespace App\Livewire;

use Filament\Forms;
use Livewire\Component;
use Filament\Forms\Form;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Joaopaulolndev\FilamentEditProfile\Concerns\HasSort;

class CustomProfileComponent extends Component implements HasForms
{
    use InteractsWithForms;
    use HasSort;

    public ?array $data = [];

    protected static int $sort = 0;

    public function mount(): void
    {
        $user = Auth::user();

        // Hanya memuat data Foto, Nama, dan Email dari user yang login
        $this->form->fill([
            'avatar_url' => $user->avatar_url, 
            'name' => $user->name,        
            'email' => $user->email,        
        ]);
    }

    public function form(Form $form): Form
    {
        // Cek apakah user saat ini adalah admin (id_roles === 1)
        $isAdmin = Auth::user()->id_roles === 1;

        return $form
            ->schema([
                Section::make('Informasi Data Karyawan')
                    ->aside()
                    ->description('Pastikan data Anda sudah sesuai.')
                    ->schema([
                        // 1. FOTO PROFIL (Bisa diedit oleh karyawan & admin)
                        FileUpload::make('avatar_url')
                            ->label('Foto Profil')
                            ->image()
                            ->avatar()
                            ->directory('karyawan'),

                        // 2. NAMA LENGKAP (Hanya bisa diedit oleh Admin, Karyawan terkunci)
                        TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->disabled(!$isAdmin) // Terkunci jika BUKAN admin
                            ->dehydrated($isAdmin), // Data tidak ikut dikirim dari frontend jika di-inspect oleh karyawan

                        // 3. EMAIL (Bisa diedit oleh karyawan & admin)
                        TextInput::make('email')
                            ->label('Email')
                            ->required()
                            ->email(),
                    ]),
            ])
            ->statePath('data');
    }
    
    public function save(): void
    {
        $user = Auth::user();
        $formData = $this->form->getState();
        
        // Menyiapkan data yang akan di-update ke tabel users
        $updateData = [
            'avatar_url' => $formData['avatar_url'],
            'email' => $formData['email'],
        ];

        // Jika yang melakukan simpan adalah Admin, izinkan ikut meng-update Nama Lengkap
        if ($user->id_roles === 1) {
            $updateData['name'] = $formData['name'];
        }

        // Eksekusi penyimpanan perubahan ke database
        $user->update($updateData);
        
        Notification::make()
            ->title('Profil berhasil diperbarui!')
            ->success()
            ->send();
    }
    
    public function render(): View
    {
        return view('livewire.custom-profile-component');
    }
}
