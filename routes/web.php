<?php

use App\Http\Controllers\BorrowingController;
use App\Http\Controllers\DashboardAdminController;
use App\Http\Controllers\DashboardUserController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\ItemCategoryController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ProcurementController;
use App\Http\Controllers\ProcurementPdfController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\RoomInventoryController;
use App\Http\Controllers\RoomTypeController;
use App\Http\Controllers\StudyProgramController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VerifyEmailController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::inertia('/', 'auth/Login')
    ->middleware('guest')
    ->name('home');

Route::get('/email/verify/{id}/{hash}', [
    VerifyEmailController::class,
    'verify',
])
    ->middleware('throttle:6,1')
    ->name('verification.verify');

Route::middleware(['auth'])->group(function () {

    Route::get('/email/verify', [
        VerifyEmailController::class,
        'notice',
    ])->name('verification.notice');

    Route::post('/email/verification-notification', [
        VerifyEmailController::class,
        'send',
    ])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::middleware(['verified'])->group(function () {

        Route::get('/dashboard', function () {
            $role = Auth::user()->role;

            if (in_array($role, [
                'super_admin',
                'admin_fakultas',
                'sdm',
            ], true)) {
                return redirect()->route('admin.dashboard');
            }

            return redirect()->route('user.dashboard');
        })->name('dashboard');

        Route::middleware(['role:super_admin,admin_fakultas,sdm'])
            ->prefix('admin')
            ->name('admin.')
            ->group(function () {

                Route::get('/dashboard', [
                    DashboardAdminController::class,
                    'index',
                ])->name('dashboard');

                Route::put('/dashboard/room-types/{roomType}', [
                    DashboardAdminController::class,
                    'update',
                ])->name('dashboard.room-types.update');

                Route::delete('/dashboard/room-types/{roomType}', [
                    DashboardAdminController::class,
                    'destroy',
                ])->name('dashboard.room-types.destroy');

                Route::resource('faculties', FacultyController::class)
                    ->only([
                        'index',
                        'store',
                        'update',
                        'destroy',
                    ]);

                Route::resource('study-programs', StudyProgramController::class)
                    ->only([
                        'store',
                        'update',
                        'destroy',
                    ]);

                Route::resource('rooms', RoomController::class);

                Route::resource('room-types', RoomTypeController::class)
                    ->only([
                        'index',
                        'store',
                        'update',
                        'destroy',
                    ]);

                Route::resource('room-inventories', RoomInventoryController::class);

                Route::resource('categories', ItemCategoryController::class)
                    ->only([
                        'index',
                        'store',
                        'update',
                        'destroy',
                    ]);

                Route::resource('items', ItemController::class)
                    ->only([
                        'index',
                        'store',
                        'update',
                        'destroy',
                    ]);

                Route::resource('borrowings', BorrowingController::class)
                    ->only([
                        'index',
                        'store',
                        'update',
                        'destroy',
                    ]);

                Route::resource('procurements', ProcurementController::class)
                    ->only([
                        'index',
                        'store',
                        'show',
                        'update',
                        'destroy',
                    ]);

                Route::post('/procurements/{procurement}/approve', [
                    ProcurementController::class,
                    'approve',
                ])->name('procurements.approve');

                Route::post('/procurements/{procurement}/reject', [
                    ProcurementController::class,
                    'reject',
                ])->name('procurements.reject');

                Route::post('/procurements/{procurement}/complete', [
                    ProcurementController::class,
                    'complete',
                ])->name('procurements.complete');

                Route::get('/procurements/{procurement}/print', [
                    ProcurementController::class,
                    'print',
                ])->name('procurements.print');

                Route::get('/procurements/{procurement}/pdf', [
                    ProcurementPdfController::class,
                    'pdf',
                ])->name('procurements.pdf');

                Route::patch('/users/{user}/send-verification-email', [
                    UserController::class,
                    'sendVerificationEmail',
                ])->middleware('throttle:6,1')->name('users.send-verification-email');

                Route::resource('users', UserController::class);
            });

        Route::middleware(['role:dosen,mahasiswa'])
            ->prefix('user')
            ->name('user.')
            ->group(function () {
                Route::get('/dashboard', [
                    DashboardUserController::class,
                    'index',
                ])->name('dashboard');
            });

        Route::middleware(['role:super_admin'])
            ->prefix('super-admin')
            ->name('super-admin.')
            ->group(function () {});
    });
});

require __DIR__ . '/settings.php';
