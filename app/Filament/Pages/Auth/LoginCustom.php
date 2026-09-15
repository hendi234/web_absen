<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Pages\Auth\Login;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;

class LoginCustom extends Login
{
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getLoginFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getRememberFormComponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    protected function getLoginFormComponent(): Component
    {
        return TextInput::make('login')
            ->label(__('Email / Nama'))
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        // Tidak perlu ubah banyak — nanti deteksi di authenticate()
        return [
            'login' => $data['login'],
            'password' => $data['password'],
        ];
    }

    public function authenticate(): ?LoginResponse
    {
        $data = $this->form->getState();
        $loginValue = $data['login'];

        // Deteksi tipe login
        if (filter_var($loginValue, FILTER_VALIDATE_EMAIL)) {
            $user = User::where('email', $loginValue)->first();
            $field = 'email';
        } 
        // elseif (is_numeric($loginValue)) {
        //     $user = User::where('nip', $loginValue)->first();
        //     $field = 'nip';
        // }
         else {
            $user = User::where('name', $loginValue)->first();
            $field = 'name';
        }

        // Jika user tidak ditemukan
        if (! $user) {
            throw ValidationException::withMessages([
                'data.login' => match ($field) {
                    'email' => 'Email tidak ditemukan.',
                    // 'nip' => 'NIP tidak ditemukan.',
                    'name' => 'Nama tidak ditemukan.',
                    default => 'Data tidak ditemukan.',
                },
            ]);
        }

        // Cek password
        if (! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'data.password' => 'Kata sandi salah.',
            ]);
        }

        // Login berhasil
        auth()->login($user, $data['remember'] ?? false);

        // Kembalikan response sesuai Filament
        return app(LoginResponse::class);
    }
}
