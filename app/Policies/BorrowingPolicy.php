<?php

namespace App\Policies;

use App\Models\Borrowing;
use App\Models\User;

class BorrowingPolicy
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
                'dosen',
                'mahasiswa',
            ],
            true
        );
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(
        User $user,
        Borrowing $borrowing
    ): bool {
        /*
        |--------------------------------------------------------------------------
        | SUPER ADMIN
        |--------------------------------------------------------------------------
        */

        if ($user->role === 'super_admin') {
            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | ADMIN FAKULTAS
        |--------------------------------------------------------------------------
        */

        if ($user->role === 'admin_fakultas') {
            return (int) $borrowing->faculty_id ===
                (int) $user->faculty_id;
        }

        /*
        |--------------------------------------------------------------------------
        | DOSEN / MAHASISWA
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $user->role,
                ['dosen', 'mahasiswa'],
                true
            )
        ) {
            return (int) $borrowing->user_id ===
                (int) $user->id;
        }

        /*
        |--------------------------------------------------------------------------
        | SDM
        |--------------------------------------------------------------------------
        */

        if ($user->role === 'sdm') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return in_array(
            $user->role,
            [
                'super_admin',
                'admin_fakultas',
                'dosen',
                'mahasiswa',
            ],
            true
        );
    }

    /**
     * Determine whether the user can update the model.
     *
     * Digunakan untuk:
     * - edit
     * - approve
     * - reject
     * - return
     * - cancel
     */
    public function update(
        User $user,
        Borrowing $borrowing
    ): bool {
        /*
        |--------------------------------------------------------------------------
        | SUPER ADMIN
        |--------------------------------------------------------------------------
        |
        | Super admin memiliki akses global.
        |
        */

        if ($user->role === 'super_admin') {
            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | ADMIN FAKULTAS
        |--------------------------------------------------------------------------
        |
        | Hanya boleh memproses borrowing
        | dari fakultasnya sendiri.
        |
        */

        if ($user->role === 'admin_fakultas') {
            return (int) $borrowing->faculty_id ===
                (int) $user->faculty_id;
        }

        /*
        |--------------------------------------------------------------------------
        | DOSEN / MAHASISWA
        |--------------------------------------------------------------------------
        |
        | Hanya pemilik yang boleh mengedit
        | selama pending atau rejected.
        |
        */

        if (
            in_array(
                $user->role,
                ['dosen', 'mahasiswa'],
                true
            )
        ) {
            return (
                (int) $borrowing->user_id ===
                (int) $user->id
            )
            && in_array(
                $borrowing->status,
                [
                    'pending',
                    'rejected',
                ],
                true
            );
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(
        User $user,
        Borrowing $borrowing
    ): bool {
        /*
        |--------------------------------------------------------------------------
        | SUPER ADMIN
        |--------------------------------------------------------------------------
        */

        if ($user->role === 'super_admin') {
            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | Borrowing aktif / selesai
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $borrowing->status,
                [
                    'borrowed',
                    'returned',
                ],
                true
            )
        ) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | ADMIN FAKULTAS
        |--------------------------------------------------------------------------
        */

        if ($user->role === 'admin_fakultas') {
            return (int) $borrowing->faculty_id ===
                (int) $user->faculty_id;
        }

        /*
        |--------------------------------------------------------------------------
        | DOSEN / MAHASISWA
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $user->role,
                ['dosen', 'mahasiswa'],
                true
            )
        ) {
            return (int) $borrowing->user_id ===
                (int) $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(
        User $user,
        Borrowing $borrowing
    ): bool {
        return $user->role === 'super_admin';
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(
        User $user,
        Borrowing $borrowing
    ): bool {
        return $user->role === 'super_admin';
    }
}