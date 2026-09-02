<?php

use App\Http\Controllers\ApprovalRequestController;
use App\Http\Controllers\BuildApprovalLetterController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DistrictController;
use App\Http\Controllers\FrequencyAllocationController;
use App\Http\Controllers\FrequencyAllocationLetterController;
use App\Http\Controllers\FrequencyRenewalReceiptController;
use App\Http\Controllers\GeographyController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\InspectionController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\MinistrySettingsController;
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
    Route::get('/help', HelpController::class)->name('help');
    Route::get('/map', [MapController::class, 'index'])->name('map');
    Route::get('/map/towers', [MapController::class, 'towers'])->name('map.towers');
    Route::get('/geography/districts', [GeographyController::class, 'districts'])->name('geography.districts');
    Route::get('/geography/sub-districts', [GeographyController::class, 'subDistricts'])->name('geography.sub-districts');
    Route::get('/geography/cities', [GeographyController::class, 'cities'])->name('geography.cities');
    Route::get('/geography/operators', [GeographyController::class, 'operators'])->name('geography.operators');
    Route::get('districts', [DistrictController::class, 'index'])->name('districts.index');
    Route::get('districts/{district}/edit', [DistrictController::class, 'edit'])->name('districts.edit');
    Route::put('districts/{district}', [DistrictController::class, 'update'])->name('districts.update');
    Route::delete('districts/{district}', [DistrictController::class, 'destroy'])->name('districts.destroy');
    Route::post('districts/{district}/sub-districts', [DistrictController::class, 'storeSubDistrict'])->name('districts.sub-districts.store');
    Route::put('districts/{district}/sub-districts/{subDistrict}', [DistrictController::class, 'updateSubDistrict'])->name('districts.sub-districts.update');
    Route::delete('districts/{district}/sub-districts/{subDistrict}', [DistrictController::class, 'destroySubDistrict'])->name('districts.sub-districts.destroy');
    Route::get('towers/location-preview', [TowerController::class, 'locationPreview'])->name('towers.location-preview');
    Route::resource('towers', TowerController::class);
    Route::get('towers/{tower}/inspections/create', [InspectionController::class, 'create'])->name('towers.inspections.create');
    Route::post('towers/{tower}/inspections', [InspectionController::class, 'store'])->name('towers.inspections.store');
    Route::get('towers/{tower}/approval-letter', [BuildApprovalLetterController::class, 'show'])->name('towers.approval-letter.show');
    Route::post('towers/{tower}/approval-letter', [BuildApprovalLetterController::class, 'store'])->name('towers.approval-letter.store');
    Route::get('towers/{tower}/approval-letter/print', [BuildApprovalLetterController::class, 'print'])->name('towers.approval-letter.print');
    Route::resource('licenses', LicenseController::class)->except(['show']);
    Route::get('frequencies', [FrequencyAllocationController::class, 'dashboard'])->name('frequencies.dashboard');
    Route::get('frequencies/registry', [FrequencyAllocationController::class, 'registry'])->name('frequencies.registry');
    Route::resource('frequencies', FrequencyAllocationController::class)->except(['index']);
    Route::post('frequencies/{frequency}/renew', [FrequencyAllocationController::class, 'renew'])->name('frequencies.renew');
    Route::get('frequencies/{frequency}/letter', [FrequencyAllocationLetterController::class, 'show'])->name('frequencies.letter.show');
    Route::post('frequencies/{frequency}/letter', [FrequencyAllocationLetterController::class, 'store'])->name('frequencies.letter.store');
    Route::get('frequencies/{frequency}/letter/print', [FrequencyAllocationLetterController::class, 'print'])->name('frequencies.letter.print');
    Route::get('frequencies/{frequency}/receipt', [FrequencyRenewalReceiptController::class, 'show'])->name('frequencies.receipt.show');
    Route::get('frequencies/{frequency}/receipt/print', [FrequencyRenewalReceiptController::class, 'print'])->name('frequencies.receipt.print');
    Route::get('approvals', [ApprovalRequestController::class, 'index'])->name('approvals.index');
    Route::get('approvals/{approval}/edit', [ApprovalRequestController::class, 'edit'])->name('approvals.edit');
    Route::put('approvals/{approval}', [ApprovalRequestController::class, 'update'])->name('approvals.update');
    Route::get('approvals/{approval}', [ApprovalRequestController::class, 'show'])->name('approvals.show');
    Route::post('approvals/{approval}/approve', [ApprovalRequestController::class, 'approve'])->name('approvals.approve');
    Route::post('approvals/{approval}/reject', [ApprovalRequestController::class, 'reject'])->name('approvals.reject');
    Route::resource('users', UserController::class)->except(['show']);
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('settings/ministry', [MinistrySettingsController::class, 'edit'])->name('settings.ministry.edit');
    Route::put('settings/ministry/{ministry_setting}', [MinistrySettingsController::class, 'update'])->name('settings.ministry.update');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';
