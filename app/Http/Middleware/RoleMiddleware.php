<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
   public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // 1. Cek apakah user sudah login
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // 2. Daftar 5 Role Valid Sistem
        $allowedSystemRoles = [
            'super_admin',
            'admin_fakultas',
            'sdm',
            'dosen',
            'mahasiswa',
        ];

        // 3. Jika role user tidak ada di 5 role valid sistem -> Logout & Reject
        if (!in_array($user->role, $allowedSystemRoles)) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Akun Anda tidak memiliki hak akses yang valid dalam sistem.',
            ]);
        }

        // 4. Jika middleware dipanggil dengan parameter role spesifik, cek kecocokannya
        if (!empty($roles) && !in_array($user->role, $roles)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
