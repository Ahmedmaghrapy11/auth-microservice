<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// ----------------------------- JWT-Auth ------------------------------ //
Route::post('/register', [AuthController::class,'register'])->name('jwt.register');
Route::post('/login', [AuthController::class,'login'])->name('jwt.login');

Route::group(['middleware' => 'jwt.auth'], function () {
    Route::post('/logout', [AuthController::class,'logout'])->name('jwt.logout');
});
