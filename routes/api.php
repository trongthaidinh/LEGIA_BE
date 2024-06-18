<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Admin\BackgroundController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\FriendshipController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\StoryController;
use App\Http\Middleware\AdminMiddleware;

Route::group([
    'middleware' => 'api',
    'prefix' => 'user',
], function () {
    Route::get('me', [UserController::class, 'me']);
    Route::patch('update-password', [UserController::class, 'updatePassword']);
    Route::patch('update-information', [UserController::class, 'updateInformation']);
    Route::post('update-avatar', [UserController::class, 'updateAvatar']);
    Route::post('update-cover-image', [UserController::class, 'updateCoverImage']);
    Route::delete('delete-avatar', [UserController::class, 'deleteAvatar']);
    Route::delete('delete-cover-image', [UserController::class, 'deleteCoverImage']);
    Route::get('/profile/{id}', [UserController::class, 'getProfile']);
    Route::get('/find', [UserController::class, 'find']);

});


Route::group([
    'middleware' => ['api', AdminMiddleware::class],
    'prefix' => 'admin',
], function () {
    Route::get('users', [AdminUserController::class, 'index']);
    Route::get('users/{id}', [AdminUserController::class, 'show']);
    Route::patch('users/{id}/lock', [AdminUserController::class, 'lock']);
    Route::patch('users/{id}/unlock', [AdminUserController::class, 'unlock']);
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'auth',
], function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('forgot-password', [AuthController::class, 'sendResetLinkEmail']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
});

Route::group([
    'middleware' => ['api'],
], function () {
    Route::get('backgrounds', [BackgroundController::class, 'index']);
    Route::group([
        'middleware' => [AdminMiddleware::class],
        'prefix' => 'admin',
    ], function () {
        Route::post('backgrounds', [BackgroundController::class, 'store']);
        Route::get('backgrounds/{id}', [BackgroundController::class, 'show']);
        Route::put('backgrounds/{id}', [BackgroundController::class, 'update']);
        Route::patch('backgrounds/{id}/toggle-visibility', [BackgroundController::class, 'toggleVisibility']);
        Route::delete('backgrounds/{id}', [BackgroundController::class, 'destroy']);
    });
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

    Route::get('/user/{id}', [PostController::class, 'getUserPosts']);

    Route::get('reaction-detail/{id}', [PostController::class, 'getReactionsDetail']);
    Route::get('user-reaction/{id}', [PostController::class, 'getUserReaction']);
    Route::post('reaction/{id}', [PostController::class, 'addOrUpdateReaction']);
    Route::delete('reaction/{id}', [PostController::class, 'removeReaction']);



    Route::get('/comments/{id}', [PostController::class, 'getComments']);
    Route::post('/comments/{id}', [PostController::class, 'storeComment']);
    Route::put('/comments/{id}/{commentId}', [PostController::class, 'updateComment']);
    Route::delete('/comments/{id}/{commentId}', [PostController::class, 'deleteComment']);

    Route::post('/share/{id}', [PostController::class, 'sharePost']);
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'archived',
], function () {
    Route::get('/', [PostController::class, 'getArchivedPosts']);
    Route::post('/{id}', [PostController::class, 'saveToArchive']);
    Route::delete('/{id}', [PostController::class, 'removeFromArchive']);
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'search',
], function () {
    Route::get('/posts', [PostController::class, 'searchPost']);
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'chat',
], function () {
    Route::post('create-conversation', [ChatController::class, 'createConversation']);
    Route::post('message', [ChatController::class, 'createMessage']);
    Route::get('my-conversations', [ChatController::class, 'getMyConversations']);
    Route::get('get-secret-key/{conversation_id}', [ChatController::class, 'getSecretKey']);
    Route::get('messages/{conversation_id}', [ChatController::class, 'getMessagesByConversationId']);
    Route::get('conversation_participants', [ChatController::class, 'getConversationParticipants']);
    Route::post('message/mark-is-read/{message_id}', [ChatController::class, 'markIsRead']);
    Route::get('get-conversation-individual-id/{partner_id}', [ChatController::class, 'getConversationIndividualId']);
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'stories',
], function () {
    Route::get('/', [StoryController::class, 'index']);
    Route::post('/', [StoryController::class, 'create']);
    Route::patch('/{id}/update', [StoryController::class, 'update']);
    Route::get('/{id}', [StoryController::class, 'show']);
    Route::delete('/{id}', [StoryController::class, 'delete']);
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'notifications',
], function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
});
