<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Middleware\AdminMiddleware;

use App\Http\Controllers\ParentNavController;
use App\Http\Controllers\ChildNavController;
use App\Http\Controllers\ChildNavsTwoController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ExperienceController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\VideoController;

// Auth
Route::group([
    'middleware' => 'api',
    'prefix' => 'auth',
], function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('forgot-password', [AuthController::class, 'sendResetLinkEmail']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    Route::post('refresh', [AuthController::class, 'refresh']);
});

// Parent Nav
Route::group(['prefix' => 'parent-navs'], function () {
    Route::group(['middleware' => AdminMiddleware::class], function () {
        Route::post('/', [ParentNavController::class, 'store']);
        Route::patch('/{id}', [ParentNavController::class, 'update']);
        Route::delete('/{id}', [ParentNavController::class, 'destroy']);
    });

    Route::get('/', [ParentNavController::class, 'index']);
    Route::get('/all-with-child', [ParentNavController::class, 'getAllWithChildren']);
    Route::get('/slug/{slug}', [ParentNavController::class, 'getChildrenBySlug']);
    Route::get('/{id}', [ParentNavController::class, 'show']);
});


// Child Nav
Route::group(['prefix' => 'child-navs'], function () {
    Route::group(['middleware' => AdminMiddleware::class], function () {
        Route::post('/', [ChildNavController::class, 'store']);
        Route::patch('/{id}', [ChildNavController::class, 'update']);
        Route::delete('/{id}', [ChildNavController::class, 'destroy']);
    });

    Route::get('/', [ChildNavController::class, 'index']);
    Route::get('/{id}', [ChildNavController::class, 'show']);
});

// Child Nav Two
Route::group(['prefix' => 'child-navs-two'], function () {
    Route::group(['middleware' => AdminMiddleware::class], function () {
        Route::post('/', [ChildNavsTwoController::class, 'store']);
        Route::patch('/{id}', [ChildNavsTwoController::class, 'update']);
        Route::delete('/{id}', [ChildNavsTwoController::class, 'destroy']);
    });

    Route::get('/', [ChildNavsTwoController::class, 'index']);
    Route::get('/{id}', [ChildNavsTwoController::class, 'show']);
});

// Configuration
Route::group(['prefix' => 'configuration'], function () {
    Route::group(['middleware' => AdminMiddleware::class], function () {
        Route::post('/', [ConfigurationController::class, 'store']);
        Route::patch('/{id}', [ConfigurationController::class, 'update']);
        Route::delete('/{id}', [ConfigurationController::class, 'destroy']);
    });

    Route::get('/', [ConfigurationController::class, 'index']);
    Route::get('/{id}', [ConfigurationController::class, 'show']);
});

// Product
Route::group(['prefix' => 'products'], function () {
    Route::group(['middleware' => AdminMiddleware::class], function () {
        Route::post('/', [ProductController::class, 'store']);
        Route::post('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
    });

    Route::get('/', [ProductController::class, 'index']);
    Route::get('/{id}', [ProductController::class, 'show']);
});

// Service
Route::group(['prefix' => 'services'], function () {
    Route::group(['middleware' => AdminMiddleware::class], function () {
        Route::post('/', [ServiceController::class, 'store']);
        Route::patch('/{id}', [ServiceController::class, 'update']);
        Route::delete('/{id}', [ServiceController::class, 'destroy']);
    });

    Route::get('/', [ServiceController::class, 'index']);
    Route::get('/{id}', [ServiceController::class, 'show']);
});

// Experience
Route::group(['prefix' => 'experiences'], function () {
    Route::group(['middleware' => AdminMiddleware::class], function () {
        Route::post('/', [ExperienceController::class, 'store']);
        Route::patch('/{id}', [ExperienceController::class, 'update']);
        Route::delete('/{id}', [ExperienceController::class, 'destroy']);
    });

    Route::get('/', [ExperienceController::class, 'index']);
    Route::get('/{id}', [ExperienceController::class, 'show']);
});

// News
Route::group(['prefix' => 'news'], function () {
    Route::group(['middleware' => AdminMiddleware::class], function () {
        Route::post('/', [NewsController::class, 'store']);
        Route::patch('/{id}', [NewsController::class, 'update']);
        Route::delete('/{id}', [NewsController::class, 'destroy']);
    });

    Route::get('/', [NewsController::class, 'index']);
    Route::get('/{id}', [NewsController::class, 'show']);
});

// Team
Route::group(['prefix' => 'teams'], function () {
    Route::group(['middleware' => AdminMiddleware::class], function () {
        Route::post('/', [TeamController::class, 'store']);
        Route::patch('/{id}', [TeamController::class, 'update']);
        Route::delete('/{id}', [TeamController::class, 'destroy']);
    });

    Route::get('/', [TeamController::class, 'index']);
    Route::get('/{id}', [TeamController::class, 'show']);
});

// Video
Route::group(['prefix' => 'videos'], function () {
    Route::group(['middleware' => AdminMiddleware::class], function () {
        Route::post('/', [VideoController::class, 'store']);
        Route::patch('/{id}', [VideoController::class, 'update']);
        Route::delete('/{id}', [VideoController::class, 'destroy']);
    });

    Route::get('/', [VideoController::class, 'index']);
    Route::get('/{id}', [VideoController::class, 'show']);
});

// Image
Route::group(['prefix' => 'images'], function () {
    Route::group(['middleware' => AdminMiddleware::class], function () {
        Route::post('/', [ImageController::class, 'store']);
        Route::patch('/{id}', [ImageController::class, 'update']);
        Route::delete('/{id}', [ImageController::class, 'destroy']);
    });

    Route::get('/', [ImageController::class, 'index']);
    Route::get('/{id}', [ImageController::class, 'show']);
});

// Contact
Route::group(['prefix' => 'contact'], function () {
    Route::group(['middleware' => AdminMiddleware::class], function () {
        Route::delete('/{id}', [ContactController::class, 'destroy']);
        Route::get('/{id}', [ContactController::class, 'show']);
        Route::get('/', [ContactController::class, 'index']);
    });

    Route::post('/', [ContactController::class, 'store']);
});

// Page
Route::group(['prefix' => 'pages'], function () {
    Route::group(['middleware' => AdminMiddleware::class], function () {
        Route::post('/', [PageController::class, 'store']);
        Route::patch('/{slug}', [PageController::class, 'update']);
        Route::delete('/{id}', [PageController::class, 'destroy']);
    });

    Route::get('/', [PageController::class, 'index']);
    Route::get('/{id}', [PageController::class, 'show']);
    Route::get('/slug/{slug}', [PageController::class, 'getPageBySlug']);
});

// Search
Route::group(['prefix' => 'search'], function () {
    Route::get('/', [SearchController::class, 'search']);
});
