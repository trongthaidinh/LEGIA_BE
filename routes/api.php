<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FriendshipController;
use App\Http\Controllers\PostController;

Route::group([
    'middleware' => 'api',
    'prefix' => 'user',
], function () {
    Route::get('me', [UserController::class, 'me']);
    Route::patch('update-password', [UserController::class, 'updatePassword']);
    Route::patch('update-information', [UserController::class, 'updateInformation']);
    Route::post('update-avatar', [UserController::class, 'updateAvatar']);
    Route::delete('delete-avatar', [UserController::class, 'deleteAvatar']);
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'auth',
], function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'friendship',
], function () {
    Route::get('get-accepted-list', [FriendshipController::class, 'getAcceptedList']);
    Route::get('get-pending-list', [FriendshipController::class, 'getPendingList']);
    Route::post('add/{friend}', [FriendshipController::class, 'add']);
    Route::patch('accept/{id}', [FriendshipController::class, 'accept']);
    Route::delete('{id}', [FriendshipController::class, 'delete']);
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'posts',
], function () {
    Route::get('/', [PostController::class, 'index']); 
    Route::post('/', [PostController::class, 'store']); 
    Route::get('/{id}', [PostController::class, 'show']);
    Route::put('/{id}', [PostController::class, 'update']);
    Route::delete('/{id}', [PostController::class, 'destroy']);

    Route::post('/{id}/archive', [PostController::class, 'saveToArchive']);
    Route::delete('/{id}/archive', [PostController::class, 'removeFromArchive']);
    Route::get('/archived', [PostController::class, 'getArchivedPosts']);

});
