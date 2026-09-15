<div>
    <form wire:submit.prevent="save" class="space-y-6">
        
        {{-- Merender Foto Profil, Nama (disabled untuk karyawan), dan Email --}}
        {{ $this->form }}

        {{-- Tombol Simpan (Sekarang muncul untuk semua user) --}}
        <div class="flex justify-end">
            <x-filament::button type="submit" size="sm">
                Simpan Perubahan
            </x-filament::button>
        </div>
        
    </form>
</div>
