<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
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
<<<<<<< routes/api.php
    'prefix' => 'posts',
], function () {
    Route::get('/', [PostController::class, 'index']); 
    Route::post('/', [PostController::class, 'store']); 
    Route::get('/{id}', [PostController::class, 'show']);
    Route::put('/{id}', [PostController::class, 'update']);
    Route::delete('/{id}', [PostController::class, 'destroy']);

    Route::get('/archived', [PostController::class, 'getArchivedPosts']);
    Route::post('/{id}/archive', [PostController::class, 'saveToArchive']);
    Route::delete('/{id}/archive', [PostController::class, 'removeFromArchive']);

    Route::post('/{id}/like', [PostController::class, 'likePost']);

    Route::get('/{id}/comments', [PostController::class, 'getComments']);
    Route::post('/{id}/comments', [PostController::class, 'storeComment']);
    Route::put('/{id}/comments/{commentId}', [PostController::class, 'updateComment']);
    Route::delete('/{id}/comments/{commentId}', [PostController::class, 'deleteComment']);

    Route::post('/{id}/share', [PostController::class, 'sharePost']);
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'user',
], function () {
    Route::get('/{id}', [PostController::class, 'getUserPosts']);
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'search',
], function () {
    Route::get('/posts', [PostController::class, 'searchPost']);
});
=======
Route::group([
    'middleware' => 'api',
    'prefix' => 'chat',
], function () {
    Route::post('create-conversation', [ChatController::class, 'createConversation']);
    Route::post('message', [ChatController::class, 'createMessage']);
    Route::get('my-conversations', [ChatController::class, 'getMyConversations']);
    Route::get('get-secret-key/{conversation_id}', [ChatController::class, 'getSecretKey']);
    Route::get('messages/{conversation_id}', [ChatController::class, 'getMessagesByConversationId']);
>>>>>>> routes/api.php
});
