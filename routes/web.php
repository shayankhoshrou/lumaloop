<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HabitController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/habits', [HabitController::class, 'store'])->name('habits.store');
    Route::post('/habits/{habit}/logs', [HabitController::class, 'log'])->name('habits.logs.store');
});

require __DIR__.'/auth.php';

