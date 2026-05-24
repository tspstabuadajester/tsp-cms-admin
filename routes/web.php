<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('websites', function () {
    return Inertia::render('Websites');
})->middleware(['auth', 'verified'])->name('websites');

Route::get('user', function () {
    return Inertia::render('User');
})->middleware(['auth', 'verified'])->name('user');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
