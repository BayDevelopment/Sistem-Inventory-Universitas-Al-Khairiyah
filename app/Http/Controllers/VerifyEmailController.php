<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class VerifyEmailController extends Controller
{
    /**
     * Menampilkan halaman verifikasi email.
     *
     * Route ini hanya membutuhkan auth dan sengaja
     * berada DI LUAR middleware "verified".
     */
    public function notice(): Response
    {
        $user = Auth::user();

        return Inertia::render('auth/Verifikasi', [
            'email' => $user->email,
            'email_verified' => $user->hasVerifiedEmail(),
        ]);
    }

    /**
     * Memproses link verifikasi email dari email.
     */
    public function verify(
        Request $request,
        int $id,
        string $hash
    ): RedirectResponse {
        /*
        |--------------------------------------------------------------------------
        | Validate Signed URL
        |--------------------------------------------------------------------------
        */

        abort_unless(
            $request->hasValidSignature(),
            403,
            'Link verifikasi tidak valid atau sudah kedaluwarsa.'
        );

        /*
        |--------------------------------------------------------------------------
        | Find User
        |--------------------------------------------------------------------------
        */

        $user = User::findOrFail($id);

        /*
        |--------------------------------------------------------------------------
        | Validate Email Hash
        |--------------------------------------------------------------------------
        */

        abort_unless(
            hash_equals(
                $hash,
                sha1($user->getEmailForVerification())
            ),
            403,
            'Link verifikasi tidak valid.'
        );

        /*
        |--------------------------------------------------------------------------
        | Mark Email As Verified
        |--------------------------------------------------------------------------
        */

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        /*
        |--------------------------------------------------------------------------
        | Setelah berhasil:
        | Jangan auto-login.
        |
        | User diarahkan ke login dan harus login kembali.
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route('home')
            ->with('toast', [
                'type' => 'success',
                'message' => 'Email berhasil diverifikasi. Silakan login untuk melanjutkan.',
            ]);
    }

    /**
     * Mengirim ulang email verifikasi.
     */
    public function send(Request $request): RedirectResponse
    {
        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Jika sudah verified
        |--------------------------------------------------------------------------
        */

        if ($user->hasVerifiedEmail()) {
            return back()->with('toast', [
                'type' => 'info',
                'message' => 'Email Anda sudah terverifikasi.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Kirim ulang verification notification
        |--------------------------------------------------------------------------
        */

        $user->sendEmailVerificationNotification();

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Link verifikasi berhasil dikirim ke email Anda.',
        ]);
    }
}