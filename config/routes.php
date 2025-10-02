<?php

use App\Controllers\HomeController;
use App\Controllers\AuthController;
use Core\Router\Route;

// Authentication
Route::get('/', [HomeController::class, 'index'])->name('root');
Route::get('/login', [AuthController::class, 'index'])->name('root');
