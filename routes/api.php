<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;


// Route::prefix('user')->group(function () {
//     Route::get('', [UserController::class, 'index'])->name('index');
// });


Route::group([
    'middleware' => 'api',
    'prefix' => 'auth',
], function () {
    Route::post('register', [AuthController::class, 'register'])->name('registerUser');
    Route::post('login', [AuthController::class, 'login'])->name('loginUser');
    Route::post('logout', [AuthController::class, 'logout'])->name('logoutUser');
});
