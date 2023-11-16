<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// ----------------------------- JWT-Auth ------------------------------ //
Route::post('/register', [AuthController::class,'register'])->name('jwt.register');
Route::post('/login', [AuthController::class,'login'])->name('jwt.login');

Route::group(['middleware' => 'jwt.auth'], function () {
    Route::post('/logout', [AuthController::class,'logout'])->name('jwt.logout');
});
