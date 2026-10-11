<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\FactureController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route(auth()->user()->isAdmin() ? 'admin.dashboard' : 'dashboard');
    }

    return view('welcome');
});

Route::middleware(['auth', 'active', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        if (auth()->user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return view('dashboard');
    })->name('dashboard');

    Route::get('/factures', [FactureController::class, 'index'])->name('factures.index');
    Route::get('/factures/create', [FactureController::class, 'create'])->name('factures.create');
    Route::post('/factures', [FactureController::class, 'store'])->name('factures.store');
    Route::post('/factures/synchroniser', [FactureController::class, 'synchronizeOffline'])->name('factures.synchronize');
    Route::get('/factures/{facture}', [FactureController::class, 'show'])->name('factures.show');
    Route::get('/factures/{facture}/pdf', [FactureController::class, 'pdf'])->name('factures.pdf');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');

        Route::resource('users', UserController::class)->except(['show', 'destroy']);
        Route::patch('/users/{user}/status', [UserController::class, 'toggleStatus'])->name('users.status');
        Route::resource('services', ServiceController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::get('/factures/{facture}/edit', [FactureController::class, 'edit'])->name('factures.edit');
        Route::put('/factures/{facture}', [FactureController::class, 'update'])->name('factures.update');
    });
});

require __DIR__.'/auth.php';
