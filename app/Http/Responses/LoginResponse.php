<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = Auth::user();

        $targetUrl = match (true) {
            in_array($user->role, ['super_admin', 'admin_fakultas', 'sdm']) => route('admin.dashboard'),
            in_array($user->role, ['dosen', 'mahasiswa']) => route('user.dashboard'),
            default => route('dashboard'),
        };

        return redirect()->intended($targetUrl)->with('toast', [
            'type' => 'success',
            'message' => 'Selamat datang kembali!',
        ]);
    }
}
