<?php

use App\Http\Controllers\AWSController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [AWSController::class, 'index'])->name('register');
Route::get('/login', [AWSController::class, 'login'])->name('login');
Route::post('/register', [AWSController::class, 'register'])->name('registerUser');
Route::post('/login-user', [AWSController::class, 'loginUser'])->name('loginUser');
Route::get('/welcome', [AWSController::class, 'welcome'])->name('welcome');
