<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InfectionReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CheckinController;
use App\Http\Controllers\LocationController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Home and welcome page routes
Route::get('/', function () {
    return Auth::check() ? redirect('/home') : view('welcome');
});

// Authentication routes
Auth::routes();
Route::get('/login', [LoginController::class, 'showLoginForm'])->middleware('guest')->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.submit');

// Home route
Route::get('/home', [HomeController::class, 'index'])->middleware('auth')->name('home');

// Profile routes
Route::get('/profile', [UserController::class, 'showProfile'])->name('profile.show');
Route::put('/profile/{user}', [UserController::class, 'updateProfile'])->name('profile.update');

// User management routes (admin only)
Route::middleware('auth')->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::put('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.update.role');
});

// Check-in routes
Route::middleware('auth')->group(function () {
    Route::get('/checkin', [CheckinController::class, 'show'])->name('checkin');
    Route::post('/checkin', [CheckinController::class, 'process'])->name('checkin.process');
    Route::post('/checkout', [CheckinController::class, 'checkout'])->name('checkout');
    Route::get('/checkin/success/{location}', [CheckinController::class, 'success'])->name('checkin.success');
});

// Covid infection report routes
Route::post('/infection-reports', [InfectionReportController::class, 'store'])->name('infectionReports.store');
Route::post('/negative-test', [InfectionReportController::class, 'storeNegative'])->name('infectionReports.negative');

// Location routes
Route::get('/locations', [LocationController::class, 'index'])->name('locations');
Route::middleware('auth')->group(function () {
    Route::get('/locations/create', [LocationController::class, 'create'])->name('locations.create');
    Route::post('/locations', [LocationController::class, 'store'])->name('locations.store');
    Route::get('/locations/{location}/edit', [LocationController::class, 'edit'])->name('locations.edit');
    Route::put('/locations/{location}', [LocationController::class, 'update'])->name('locations.update');
    Route::delete('/locations/{location}', [LocationController::class, 'destroy'])->name('locations.destroy');
});
Route::get('/locations/{location}', [LocationController::class, 'show'])->name('locations.show');
