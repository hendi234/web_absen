<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Branch;
use Illuminate\Auth\Access\HandlesAuthorization;

class BranchPolicy
{
    use HandlesAuthorization;

    /**
     * Tentukan apakah pengguna dapat melihat daftar Branch.
     * Hanya Admin (role 1) yang diizinkan.
     */
    public function viewAny(User $user): bool
    {
        return $user->id_roles === 1;
    }

    /**
     * Tentukan apakah pengguna dapat melihat Branch tertentu.
     */
    public function view(User $user, Branch $branch): bool
    {
        return $user->id_roles === 1;
    }

    /**
     * Tentukan apakah pengguna dapat membuat Branch baru.
     */
    public function create(User $user): bool
    {
        return $user->id_roles === 1;
    }

    /**
     * Tentukan apakah pengguna dapat mengupdate Branch.
     */
    public function update(User $user, Branch $branch): bool
    {
        return $user->id_roles === 1;
    }

    /**
     * Tentukan apakah pengguna dapat menghapus Branch.
     */
    public function delete(User $user, Branch $branch): bool
    {
        return $user->id_roles === 1;
    }
}