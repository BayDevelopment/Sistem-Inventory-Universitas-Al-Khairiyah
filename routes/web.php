<?php

use App\Http\Controllers\FacultyController;
use App\Http\Controllers\ItemCategoryController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\RoomInventoryController;
use App\Http\Controllers\StudyProgramController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// 1. HALAMAN UTAMA / LOGIN (Hanya untuk Guest/Belum Login)
Route::inertia('/', 'auth/Login')->middleware('guest')->name('home');

// 2. ROUTE TERPROTEKSI (Wajib Login & Terverifikasi)
Route::middleware(['auth', 'verified'])->group(function () {

    // AUTO-REDIRECTOR: Jika user mengakses URL /dashboard biasa
    Route::get('/dashboard', function () {
        $role = Auth::user()->role;

        if (in_array($role, ['super_admin', 'admin_fakultas', 'sdm'])) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('user.dashboard');
    })->name('dashboard');


    // ROUTE KELOMPOK PENGELOLA (super_admin, admin_fakultas, sdm)
    Route::middleware(['role:super_admin,admin_fakultas,sdm'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {

            // Dashboard Admin -> URL: /admin/dashboard | Name: admin.dashboard
            Route::get('/dashboard', function () {
                return Inertia::render('Admin/Dashboard');
            })->name('dashboard');

            Route::resource('faculties', FacultyController::class)->only(['index', 'store', 'update', 'destroy']);

            Route::resource('study-programs', StudyProgramController::class)->only(['store', 'update', 'destroy']);

            Route::resource('rooms', RoomController::class);
            Route::resource('room-inventories', RoomInventoryController::class);

            Route::resource('categories', ItemCategoryController::class)
                ->only(['index', 'update', 'destroy'])
                ->parameter('categories', 'category');

            Route::resource('items', ItemController::class)
                ->only(['index', 'store', 'update', 'destroy']);
        });


    // ROUTE KELOMPOK PEMOHON (dosen, mahasiswa)
    Route::middleware(['role:dosen,mahasiswa'])
        ->prefix('user')
        ->name('user.')
        ->group(function () {

            // Dashboard User -> URL: /user/dashboard | Name: user.dashboard
            Route::get('/dashboard', function () {
                return Inertia::render('User/Dashboard');
            })->name('dashboard');

            // Contoh: Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
        });


    // ROUTE EKSKLUSIF (Hanya Super Admin)
    Route::middleware(['role:super_admin'])
        ->prefix('super-admin')
        ->name('super-admin.')
        ->group(function () {
            // Contoh: Route::get('/users', [UserController::class, 'index'])->name('users.index');
        });
});

require __DIR__ . '/settings.php';
