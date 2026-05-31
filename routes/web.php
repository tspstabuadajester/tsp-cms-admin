<?php

use App\Http\Controllers\BusinessController;
use App\Http\Controllers\WebsiteController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::prefix('websites')->middleware('permission:websites.manage')->group(function () {
        Route::get('/', [WebsiteController::class, 'index'])->name('websites');
        Route::get('/create', [WebsiteController::class, 'create'])->name('websites.create');
        Route::post('/', [WebsiteController::class, 'store'])->name('websites.store');
        Route::get('/{website}', [WebsiteController::class, 'show'])->name('websites.show');
        Route::get('/{website}/edit', [WebsiteController::class, 'edit'])->name('websites.edit');
        Route::put('/{website}', [WebsiteController::class, 'update'])->name('websites.update');
    });

    Route::prefix('business')->middleware('permission:business.manage')->group(function () {
        Route::get('/', [BusinessController::class, 'index'])->name('business');
        Route::get('/create', [BusinessController::class, 'create'])->name('business.create');
        Route::post('/', [BusinessController::class, 'store'])->name('business.store');
        Route::get('/{business}/edit', [BusinessController::class, 'edit'])->name('business.edit');
        Route::put('/{business}', [BusinessController::class, 'update'])->name('business.update');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
