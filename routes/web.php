<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InspectionController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TowerController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::post('/locale', LocaleController::class)->name('locale.update');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/map', [MapController::class, 'index'])->name('map');
    Route::get('/map/towers', [MapController::class, 'towers'])->name('map.towers');
    Route::resource('towers', TowerController::class);
    Route::get('towers/{tower}/inspections/create', [InspectionController::class, 'create'])->name('towers.inspections.create');
    Route::post('towers/{tower}/inspections', [InspectionController::class, 'store'])->name('towers.inspections.store');
    Route::resource('licenses', LicenseController::class)->except(['show']);
    Route::resource('users', UserController::class)->except(['show']);
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';
