<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardUserController extends Controller
{
    /**
     * Dashboard User.
     *
     * Role yang dapat mengakses:
     * - dosen
     * - mahasiswa
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Users/Dashboard', [
            'user' => $user,
        ]);
    }
}