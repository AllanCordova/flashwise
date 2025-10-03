<?php

use App\Controllers\HomeController;
use App\Controllers\AuthController;
use Core\Router\Route;

// Pages
Route::get('/', [HomeController::class, 'index'])->name('root');

// Authentication
Route::get('/login', [AuthController::class, 'new']);
Route::post('/login', [AuthController::class, 'create']);
Route::get('/logout', [AuthController::class, 'destroy']);
