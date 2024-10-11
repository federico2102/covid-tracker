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
Route::get('/home', [HomeController::class, 'index'])->middleware('auth')->name('home');

// Login and sign in
Route::get('/login', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->middleware('guest')->name('login');
Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'login'])->name('login.submit');


// Profile routes (show or update)
Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'show'])->name('profile');
Route::post('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');

// Check-in routes
Route::get('/checkin', [App\Http\Controllers\CheckinController::class, 'show'])->middleware('auth')->name('checkin');
Route::post('/checkin', [App\Http\Controllers\CheckinController::class, 'process'])->middleware('auth')->name('checkin.process');
Route::get('/checkin/success/{location}', [App\Http\Controllers\CheckinController::class, 'success'])->name('checkin.success');
// Check-out route
Route::get('/checkout', [App\Http\Controllers\CheckinController::class, 'checkout'])->middleware('auth')->name('checkout');

// Routes for reporting covid tests
Route::get('/positive-test', [App\Http\Controllers\TestController::class, 'positive'])->name('positive-test');
Route::get('/negative-test', [App\Http\Controllers\TestController::class, 'negative'])->name('negative-test');

// Locations list
Route::get('/locations', [App\Http\Controllers\LocationController::class, 'index'])->name('locations');
// Route for creating a new location (Admin only)
Route::get('/locations/create', [App\Http\Controllers\LocationController::class, 'create'])->name('locations.create')->middleware('auth');
// Route for storing a new location
Route::post('/locations', [App\Http\Controllers\LocationController::class, 'store'])->name('locations.store')->middleware('auth');
// Route for showing a specific location (after creating routes)
Route::get('/locations/{location}', [App\Http\Controllers\LocationController::class, 'show'])->name('locations.show');
// Route for editing an existing location (Admin only)
Route::get('/locations/{location}/edit', [App\Http\Controllers\LocationController::class, 'edit'])->name('locations.edit')->middleware('auth');
// Route for updating an existing location
Route::put('/locations/{location}', [App\Http\Controllers\LocationController::class, 'update'])->name('locations.update')->middleware('auth');
// Route for deleting a location (Admin only)
Route::delete('/locations/{location}', [App\Http\Controllers\LocationController::class, 'destroy'])->name('locations.destroy')->middleware('auth');


