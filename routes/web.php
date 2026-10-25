<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ResidentController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);

Route::get('/health', [HealthController::class, 'index']);

Route::get('/residents', [ResidentController::class, 'index'])->name('residents.index');
Route::get('/residents/create', [ResidentController::class, 'create'])->name('residents.create');
Route::post('/residents', [ResidentController::class, 'store'])->name('residents.store');
Route::get('/residents/{id}/edit', [ResidentController::class, 'edit'])->whereNumber('id')->name('residents.edit');
Route::patch('/residents/{id}', [ResidentController::class, 'update'])->whereNumber('id')->name('residents.update');
Route::post('/residents/{id}/deactivate', [ResidentController::class, 'deactivate'])->whereNumber('id')->name('residents.deactivate');
