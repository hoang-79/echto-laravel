<?php

use Echto\Laravel\Http\Controllers\QuarantineController;
use Illuminate\Support\Facades\Route;

Route::get('/', [QuarantineController::class, 'index'])->name('index');
Route::get('{submission}', [QuarantineController::class, 'show'])->name('show')->whereNumber('submission');
Route::post('{submission}/release', [QuarantineController::class, 'release'])->name('release')->whereNumber('submission');
Route::delete('{submission}', [QuarantineController::class, 'discard'])->name('discard')->whereNumber('submission');
