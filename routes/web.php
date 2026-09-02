<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::inertia('/', 'auth/Login')->name('home');

Route::middleware(['auth', 'verified', 'role'])->group(function () {

    // 1. DASHBOARD UTAMA (Auto-routing tampilan berdasarkan grup role)
    Route::get('/dashboard', function () {
        $role = Auth::user()->role;

        // Kelompok Pengelola -> Render Admin/Dashboard
        if (in_array($role, ['super_admin', 'admin_fakultas', 'sdm'])) {
            return Inertia::render('Admin/Dashboard');
        }

        // Kelompok Pemohon (dosen & mahasiswa) -> Render User/Dashboard
        return Inertia::render('User/Dashboard');
    })->name('dashboard');


    // 2. ROUTE KELOMPOK PENGELOLA (super_admin, admin_fakultas, sdm)
    Route::middleware(['role:super_admin,admin_fakultas,sdm'])->prefix('admin')->name('admin.')->group(function () {
        // Contoh: Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        // Contoh: Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
    });


    // 3. ROUTE KELOMPOK PEMOHON (dosen, mahasiswa)
    Route::middleware(['role:dosen,mahasiswa'])->prefix('user')->name('user.')->group(function () {
        // Contoh: Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
        // Contoh: Route::get('/requests', [RequestController::class, 'index'])->name('requests.index');
    });


    // 4. ROUTE EKSKLUSIF (Hanya Super Admin)
    Route::middleware(['role:super_admin'])->prefix('super-admin')->name('super-admin.')->group(function () {
        // Contoh: Route::get('/users', [UserController::class, 'index'])->name('users.index');
        // Contoh: Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    });

});

require __DIR__.'/settings.php';
