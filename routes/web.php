<?php

use App\Http\Controllers\BusinessController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::prefix('websites')->group(function () {
        Route::get('/', function () {
            return Inertia::render('Websites/Index');
        })->name('websites');
    });

    Route::prefix('user')->middleware('permission:settings.manage')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('user');
        Route::get('/create', [UserController::class, 'create'])->name('user.create');
        Route::post('/', [UserController::class, 'store'])->name('user.store');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('user.edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('user.update');
    });

    Route::prefix('business')->middleware('permission:business.manage')->group(function () {
        Route::get('/', [BusinessController::class, 'index'])->name('business');
        Route::get('/create', [BusinessController::class, 'create'])->name('business.create');
        Route::post('/', [BusinessController::class, 'store'])->name('business.store');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
