<?php

namespace App\Policies;

use App\Models\Procurement;
use App\Models\User;

class ProcurementPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array(
            $user->role,
            [
                'super_admin',
                'admin_fakultas',
                'sdm',
            ],
            true
        );
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Procurement $procurement): bool
    {
        /*
        |--------------------------------------------------------------------------
        | SUPER ADMIN & SDM
        |--------------------------------------------------------------------------
        |
        | Akses global (read untuk SDM, full untuk super_admin
        | ditentukan oleh method lain, bukan di sini).
        |
        */

        if (in_array($user->role, ['super_admin', 'sdm'], true)) {
            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | ADMIN FAKULTAS
        |--------------------------------------------------------------------------
        */

        if ($user->role === 'admin_fakultas') {
            return (int) $procurement->faculty_id === (int) $user->faculty_id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     *
     * SDM sengaja TIDAK dicantumkan: hanya monitoring, tidak
     * boleh membuat pengajuan pengadaan.
     */
    public function create(User $user): bool
    {
        return in_array(
            $user->role,
            [
                'super_admin',
                'admin_fakultas',
            ],
            true
        );
    }

    /**
     * Determine whether the user can update the model.
     *
     * Hanya pending yang boleh diubah.
     * Super Admin boleh edit semua pending.
     * Admin Fakultas hanya pengajuan miliknya sendiri.
     */
    public function update(User $user, Procurement $procurement): bool
    {
        if (!$procurement->isPending()) {
            return false;
        }

        if ($user->role === 'super_admin') {
            return true;
        }

        if ($user->role === 'admin_fakultas') {
            return (int) $procurement->requested_by === (int) $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * Rule sama persis dengan update: hanya pending,
     * super_admin bebas, admin_fakultas hanya miliknya.
     */
    public function delete(User $user, Procurement $procurement): bool
    {
        return $this->update($user, $procurement);
    }

    /**
     * Determine whether the user can approve/reject/complete
     * the procurement.
     *
     * HANYA super_admin (Kepala Sarpras).
     */
    public function process(User $user, Procurement $procurement): bool
    {
        return $user->role === 'super_admin';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Procurement $procurement): bool
    {
        return $user->role === 'super_admin';
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Procurement $procurement): bool
    {
        return $user->role === 'super_admin';
    }
}
