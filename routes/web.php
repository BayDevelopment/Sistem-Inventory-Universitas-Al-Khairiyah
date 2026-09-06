<?php

use App\Http\Controllers\BorrowingController;
use App\Http\Controllers\DashboardAdminController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\ItemCategoryController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ProcurementController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\RoomInventoryController;
use App\Http\Controllers\RoomTypeController;
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
            Route::get('/dashboard', [DashboardAdminController::class, 'index'])
                ->name('dashboard');

            Route::put('/dashboard/room-types/{roomType}', [DashboardAdminController::class, 'update'])
                ->name('dashboard.room-types.update');

            Route::delete('/dashboard/room-types/{roomType}', [DashboardAdminController::class, 'destroy'])
                ->name('dashboard.room-types.destroy');

            Route::resource('faculties', FacultyController::class)->only(['index', 'store', 'update', 'destroy']);

            Route::resource('study-programs', StudyProgramController::class)->only(['store', 'update', 'destroy']);

            Route::resource('rooms', RoomController::class);

            Route::resource('room-types', RoomTypeController::class)
                ->only(['index', 'store', 'update', 'destroy']);

            Route::resource('room-inventories', RoomInventoryController::class);

            Route::resource('categories', ItemCategoryController::class)
                ->only(['index', 'store', 'update', 'destroy']);

            Route::resource('items', ItemController::class)
                ->only(['index', 'store', 'update', 'destroy']);

            Route::resource('borrowings', BorrowingController::class)->only(['index', 'store', 'update', 'destroy',]);

            Route::resource('procurements', ProcurementController::class)
                ->only([
                    'index',
                    'store',
                    'show',
                    'update',
                    'destroy',
                ]);

            Route::post('/procurements/{procurement}/approve', [ProcurementController::class, 'approve'])
                ->name('procurements.approve');

            Route::post('/procurements/{procurement}/reject', [ProcurementController::class, 'reject'])
                ->name('procurements.reject');

            Route::post('/procurements/{procurement}/complete', [ProcurementController::class, 'complete'])
                ->name('procurements.complete');

            Route::get('/procurements/{procurement}/print', [ProcurementController::class, 'print'])
                ->name('procurements.print');
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
