<?php

use Illuminate\Support\Facades\Route;
use Modules\WeightShipping\Http\Controllers\Admin\SettingsController;

Route::prefix('modules/shipping/weight-shipping')->name('admin.shipping.weight.')->group(function () {
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
});