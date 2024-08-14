<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Admin\BackgroundController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FriendshipController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PusherAuthController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SocialLinksController;
use App\Http\Controllers\StoryController;
use App\Http\Controllers\UsersSearchRecentController;
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
    Route::get('/get-suggestion-list', [UserController::class, 'getSuggestionList']);
    Route::get('/images/{id}', [UserController::class, 'getUserImages']);
});


Route::group([
    'middleware' => ['api', AdminMiddleware::class],
    'prefix' => 'admin',
], function () {
    Route::get('users', [AdminUserController::class, 'index']);
    Route::get('users/{id}', [AdminUserController::class, 'show']);
    Route::patch('users/{id}/ban', [AdminUserController::class, 'banUser']);
    Route::patch('users/{id}/unban', [AdminUserController::class, 'unbanUser']);

    Route::group([
        'prefix' => 'reports',
    ], function () {
        Route::get('/', [ReportController::class, 'index']);
        Route::put('/approve/{id}', [ReportController::class, 'approve']);
        Route::put('/reject/{id}', [ReportController::class, 'reject']);
        Route::delete('/delete/{id}', [ReportController::class, 'destroy']);
    });
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'auth',
], function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('forgot-password', [AuthController::class, 'sendResetLinkEmail']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
});

Route::group([
    'prefix' => 'auth',
], function () {
    Route::post('refresh', [AuthController::class, 'refresh']);
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'backgrounds'
], function () {
    Route::get('/', [BackgroundController::class, 'index']);
    Route::post('/', [BackgroundController::class, 'store']);
    Route::get('/{id}', [BackgroundController::class, 'show']);
    Route::post('/{id}', [BackgroundController::class, 'update']);
    Route::put('/toggle-visibility/{id}', [BackgroundController::class, 'toggleVisibility']);
    Route::delete('/{id}', [BackgroundController::class, 'destroy']);
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'friendship',
], function () {
    Route::get('user/{user_id}', [FriendshipController::class, 'getFriendListOfUser']);
    Route::get('find', [FriendshipController::class, 'findFriends']);
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

    Route::get('user/{id}', [PostController::class, 'getUserPosts']);

    Route::get('reaction-detail/{postId}/{reactionType}', [PostController::class, 'getReactionsByType']);
    Route::get('reaction-counts/{postId}', [PostController::class, 'getReactionCounts']);
    Route::get('all-reactions/{postId}', [PostController::class, 'getAllReactions']);
    Route::get('reaction-detail/{postId}', [PostController::class, 'getReactionsDetail']);
    Route::get('user-reaction/{postId}', [PostController::class, 'getUserReaction']);
    Route::get('top-reactions/{postId}', [PostController::class, 'getTopReactions']);
    Route::post('reaction/{postId}', [PostController::class, 'addOrUpdateReaction']);
    Route::delete('reaction/{id}', [PostController::class, 'removeReaction']);

    Route::get('/comments/{postId}', [PostController::class, 'getAllComments']);
    Route::get('/top-comments/{postId}', [PostController::class, 'getTopComments']);
    Route::post('/comments/{commentId}', [PostController::class, 'storeComment']);
    Route::put('/comments/{postId}/{commentId}', [PostController::class, 'updateComment']);
    Route::delete('/comments/{postId}/{commentId}', [PostController::class, 'deleteComment']);

    Route::post('/share/{postId}', [PostController::class, 'sharePost']);

});

Route::group([
    'middleware' => 'api',
    'prefix' => 'archived',
], function () {
    Route::get('/', [PostController::class, 'getArchivedPosts']);
    Route::post('/{id}', [PostController::class, 'saveToArchive']);
    Route::delete('/{id}', [PostController::class, 'removeFromArchive']);
    Route::delete('/', [PostController::class, 'removeAllFromArchive']);
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
    Route::get('messages/{conversation_id}', [ChatController::class, 'getMessagesByConversationId']);
    Route::get('conversation_participants', [ChatController::class, 'getConversationParticipants']);
    Route::get('message-images/{conversation_id}', [ChatController::class, 'getMessageImages']);
    Route::get('unread-messages-count', [ChatController::class, 'getMyUnreadMessagesCount']);
    Route::post('message/mark-is-read', [ChatController::class, 'markMessageAsRead']);
    Route::delete('conversation/{conversation_id}', [ChatController::class, 'deleteConversation']);
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'stories',
], function () {
    Route::get('/', [StoryController::class, 'index']);
    Route::post('/', [StoryController::class, 'create']);
    Route::patch('/update/{id}', [StoryController::class, 'update']);
    Route::get('/{id}', [StoryController::class, 'show']);
    Route::delete('/{id}', [StoryController::class, 'delete']);
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'notifications',
], function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::get('/unread-count', [NotificationController::class, 'getUnreadCountNotifications']);
    Route::put('/read/{id}', [NotificationController::class, 'markAsRead']);
    Route::put('/read-all', [NotificationController::class, 'markAllAsRead']);
});

Route::group([
    'middleware' => 'api',
], function () {
    Route::get('/get-secret-key', [NotificationController::class, 'getSecretKey']);
});


Route::group([
    'middleware' => 'api',
    'prefix' => 'reports',
], function () {
    Route::get('/', [ReportController::class, 'index']);
    Route::get('/type/{type}', [ReportController::class, 'getReportsByType']);
    Route::post('/', [ReportController::class, 'store']);
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'reports',
], function () {
    Route::get('/', [ReportController::class, 'index']);
    Route::post('/', [ReportController::class, 'store']);
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'pusher',
], function () {
    Route::post('user-auth', [PusherAuthController::class, 'userAuth']);
    Route::post('channel-auth', [PusherAuthController::class, 'channelAuth']);
});

Route::group([
    'middleware' => ['api', AdminMiddleware::class],
    'prefix' => 'dashboard',
], function () {
    Route::get('overview-users', [DashboardController::class, 'overviewUsers']);
    Route::get('overview-posts', [DashboardController::class, 'overviewPosts']);
    Route::get('sex-ratio', [DashboardController::class, 'sexRatio']);
    Route::get('quantity-post', [DashboardController::class, 'detailedPosts']);
    Route::get('quantity-user', [DashboardController::class, 'detailedUsers']);
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'social-links',
], function () {
    Route::post('', [SocialLinksController::class, 'createOrUpdate']);
    Route::get('{user_id}', [SocialLinksController::class, 'getByUser']);
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'users-search-recent',
], function () {
    Route::post('', [UsersSearchRecentController::class, 'create']);
    Route::get('', [UsersSearchRecentController::class, 'get']);
    Route::delete('{id}', [UsersSearchRecentController::class, 'delete']);
});
