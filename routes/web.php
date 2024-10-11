<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/home');  // Redirect logged-in users to the home page
    }
    return view('welcome');  // Show welcome page for guests
});


// Authentication routes
Auth::routes();

// Route for the home screen
Route::get('/home', [HomeController::class, 'index'])->middleware('auth');

// Login and sign in
Route::get('/login', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->middleware('guest')->name('login');
Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'login'])->name('login.submit');


// Profile routes (show or update)
Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'show'])->name('profile');
Route::post('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');

// Add these routes for checking in (scan QR code)
Route::get('/checkin', [App\Http\Controllers\CheckinController::class, 'show'])->middleware('auth')->name('checkin');
Route::post('/checkin', [App\Http\Controllers\CheckinController::class, 'process'])->middleware('auth')->name('checkin.process');
Route::get('/checkin/success/{location}', [App\Http\Controllers\CheckinController::class, 'success'])->middleware('auth')->name('checkin.success');

// Routes for reporting covid tests
Route::get('/positive-test', [App\Http\Controllers\TestController::class, 'positive'])->name('positive-test');
Route::get('/negative-test', [App\Http\Controllers\TestController::class, 'negative'])->name('negative-test');

// Locations list
Route::get('/locations', [App\Http\Controllers\LocationController::class, 'index'])->name('locations');
Route::get('/locations/{location}', [App\Http\Controllers\LocationController::class, 'show'])->name('locations.show');
Route::get('/locations/create', [App\Http\Controllers\LocationController::class, 'create'])->name('locations.create')->middleware(AdminMiddleware::class);
Route::post('/locations', [App\Http\Controllers\LocationController::class, 'store'])->name('locations.store')->middleware(AdminMiddleware::class);
Route::get('/locations/{location}/edit', [App\Http\Controllers\LocationController::class, 'edit'])->name('locations.edit')->middleware(AdminMiddleware::class);
Route::put('/locations/{location}', [App\Http\Controllers\LocationController::class, 'update'])->name('locations.update')->middleware(AdminMiddleware::class);
Route::delete('/locations/{location}', [App\Http\Controllers\LocationController::class, 'destroy'])->name('locations.destroy')->middleware(AdminMiddleware::class);



