<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ResidentController;
use App\Http\Controllers\ServiceRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);

Route::get('/health', [HealthController::class, 'index']);

Route::get('/residents', [ResidentController::class, 'index'])->name('residents.index');
Route::get('/residents/create', [ResidentController::class, 'create'])->name('residents.create');
Route::post('/residents', [ResidentController::class, 'store'])->name('residents.store');
Route::get('/residents/{id}/edit', [ResidentController::class, 'edit'])->whereNumber('id')->name('residents.edit');
Route::patch('/residents/{id}', [ResidentController::class, 'update'])->whereNumber('id')->name('residents.update');
Route::post('/residents/{id}/deactivate', [ResidentController::class, 'deactivate'])->whereNumber('id')->name('residents.deactivate');

Route::get('/service-requests', [ServiceRequestController::class, 'index'])->name('service-requests.index');
Route::get('/service-requests/create', [ServiceRequestController::class, 'create'])->name('service-requests.create');
Route::post('/service-requests', [ServiceRequestController::class, 'store'])->name('service-requests.store');
Route::get('/service-requests/{id}', [ServiceRequestController::class, 'show'])->whereNumber('id')->name('service-requests.show');
Route::post('/service-requests/{id}/start', [ServiceRequestController::class, 'start'])->whereNumber('id')->name('service-requests.start');
Route::post('/service-requests/{id}/complete', [ServiceRequestController::class, 'complete'])->whereNumber('id')->name('service-requests.complete');
Route::post('/service-requests/{id}/cancel', [ServiceRequestController::class, 'cancel'])->whereNumber('id')->name('service-requests.cancel');
