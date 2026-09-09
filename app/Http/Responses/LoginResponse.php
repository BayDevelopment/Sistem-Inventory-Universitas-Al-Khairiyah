<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        $user = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | Email Verification Guard
        |--------------------------------------------------------------------------
        | User yang belum memverifikasi email tidak boleh masuk
        | ke dashboard atau fitur aplikasi apa pun.
        |
        | Route middleware "verified" juga tetap menjadi lapisan
        | keamanan kedua untuk mencegah akses URL secara manual.
        |--------------------------------------------------------------------------
        */

        if (! $user->hasVerifiedEmail()) {
            return redirect()
                ->route('verification.notice')
                ->with('toast', [
                    'type' => 'warning',
                    'message' => 'Silakan verifikasi email Anda terlebih dahulu.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Role Based Redirect
        |--------------------------------------------------------------------------
        */

        $targetUrl = match (true) {
            in_array($user->role, [
                'super_admin',
                'admin_fakultas',
                'sdm',
            ], true) => route('admin.dashboard'),

            in_array($user->role, [
                'dosen',
                'mahasiswa',
            ], true) => route('user.dashboard'),

            default => route('dashboard'),
        };

        return redirect()
            ->intended($targetUrl)
            ->with('toast', [
                'type' => 'success',
                'message' => 'Selamat datang kembali!',
            ]);
    }
}