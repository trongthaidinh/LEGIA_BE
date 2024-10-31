<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Middleware\AdminMiddleware;

use App\Http\Controllers\ParentNavController;
use App\Http\Controllers\ChildNavController;
use App\Http\Controllers\ChildNavsTwoController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ExperienceController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\VisitController;

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

// User
Route::group([
    'middleware' => 'api',
    'prefix' => 'user',
], function () {
    Route::group(['middleware' => AdminMiddleware::class], function () {
        Route::post('update-password', [UserController::class, 'updatePassword']);
    });
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

// Zh Parent Nav
Route::group(['prefix' => 'zh-parent-navs'], function () {
    Route::group(['middleware' => AdminMiddleware::class], function () {
        Route::post('/', [ParentNavController::class, 'storeZh']);
        Route::patch('/{id}', [ParentNavController::class, 'updateZh']);
        Route::delete('/{id}', [ParentNavController::class, 'destroyZh']);
    });

    Route::get('/', [ParentNavController::class, 'indexZh']);
    Route::get('/all-with-child', [ParentNavController::class, 'getAllZhWithChildren']);
    Route::get('/slug/{slug}', [ParentNavController::class, 'getZhChildrenBySlug']);
    Route::get('/{id}', [ParentNavController::class, 'showZh']);
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

// Zh Child Nav
Route::group(['prefix' => 'zh-child-navs'], function () {
    Route::group(['middleware' => AdminMiddleware::class], function () {
        Route::post('/', [ChildNavController::class, 'storeZh']);
        Route::patch('/{id}', [ChildNavController::class, 'updateZh']);
        Route::delete('/{id}', [ChildNavController::class, 'destroyZh']);
    });

    Route::get('/', [ChildNavController::class, 'indexZh']);
    Route::get('/{id}', [ChildNavController::class, 'showZh']);
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

// Zh Child Nav Two
Route::group(['prefix' => 'zh-child-navs-two'], function () {
    Route::group(['middleware' => AdminMiddleware::class], function () {
        Route::post('/', [ChildNavsTwoController::class, 'storeZh']);
        Route::patch('/{id}', [ChildNavsTwoController::class, 'updateZh']);
        Route::delete('/{id}', [ChildNavsTwoController::class, 'destroyZh']);
    });

    Route::get('/', [ChildNavsTwoController::class, 'indexZh']);
    Route::get('/{id}', [ChildNavsTwoController::class, 'showZh']);
});


// Configuration
Route::group(['prefix' => 'configuration'], function () {
    Route::group(['middleware' => AdminMiddleware::class], function () {
        Route::post('/', [ConfigurationController::class, 'store']);
        Route::post('/{id}', [ConfigurationController::class, 'update']);
        Route::delete('/{id}', [ConfigurationController::class, 'destroy']);
    });

    Route::get('/', [ConfigurationController::class, 'index']);
    Route::get('/{id}', [ConfigurationController::class, 'show']);
});

// Zh Configuration
Route::group(['prefix' => 'zh-configuration'], function () {
    Route::group(['middleware' => AdminMiddleware::class], function () {
        Route::post('/', [ConfigurationController::class, 'storeZh']);
        Route::post('/{id}', [ConfigurationController::class, 'updateZh']);
        Route::delete('/{id}', [ConfigurationController::class, 'destroyZh']);
    });

    Route::get('/', [ConfigurationController::class, 'indexZh']);
    Route::get('/{id}', [ConfigurationController::class, 'showZh']);
});

// Product
Route::group(['prefix' => 'products'], function () {
    Route::group(['middleware' => AdminMiddleware::class], function () {
        Route::post('/', [ProductController::class, 'store']);
        Route::post('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
    });

    Route::get('/', [ProductController::class, 'index']);
    Route::get('/by-category/{slugNav}', [ProductController::class, 'getProductsBySlugNav']);
    Route::get('/{id}', [ProductController::class, 'show']);
});

// Zh Product
Route::group(['prefix' => 'zh-products'], function () {
    Route::group(['middleware' => AdminMiddleware::class], function () {
        Route::post('/', [ProductController::class, 'zhStore']);
        Route::post('/{id}', [ProductController::class, 'zhUpdate']);
        Route::delete('/{id}', [ProductController::class, 'zhDestroy']);
    });

    Route::get('/', [ProductController::class, 'zhIndex']);
    Route::get('/by-category/{slugNav}', [ProductController::class, 'zhGetProductsBySlugNav']);
    Route::get('/{id}', [ProductController::class, 'zhShow']);
});

// Order
Route::group(['prefix' => 'orders'], function () {
    Route::group(['middleware' => AdminMiddleware::class], function () {
        Route::delete('/{id}', [OrderController::class, 'destroy']);
        Route::get('/', [OrderController::class, 'index']);
    });
    Route::get('/{key}', [OrderController::class, 'showByKey']);
    Route::post('/', [OrderController::class, 'store']);
    Route::post('/{id}/status', [OrderController::class, 'updateStatus']);
});

// Zh Order
Route::group(['prefix' => 'zh-orders'], function () {
    Route::group(['middleware' => AdminMiddleware::class], function () {
        Route::delete('/{id}', [OrderController::class, 'zhDestroy']);
        Route::get('/', [OrderController::class, 'zhIndex']);
    });
    Route::get('/{key}', [OrderController::class, 'zhShowByKey']);
    Route::post('/', [OrderController::class, 'zhStore']);
    Route::post('/{id}/status', [OrderController::class, 'zhUpdateStatus']);
});

// Comment
Route::group(['prefix' => 'comments'], function () {
    Route::delete('/{id}', [CommentController::class, 'destroy']);
    Route::post('/', [CommentController::class, 'store']);
    Route::post('/{id}', [CommentController::class, 'update']);
    Route::get('/{id}', [CommentController::class, 'index']);
});

// Zh Comment
Route::group(['prefix' => 'zh-comments'], function () {
    Route::delete('/{id}', [CommentController::class, 'zhDestroy']);
    Route::post('/', [CommentController::class, 'zhStore']);
    Route::post('/{id}', [CommentController::class, 'zhUpdate']);
    Route::get('/{id}', [CommentController::class, 'zhIndex']);
});

// Service
Route::group(['prefix' => 'services'], function () {
    Route::group(['middleware' => AdminMiddleware::class], function () {
        Route::post('/', [ServiceController::class, 'store']);
        Route::post('/{id}', [ServiceController::class, 'update']);
        Route::delete('/{id}', [ServiceController::class, 'destroy']);
    });

    Route::get('/', [ServiceController::class, 'index']);
    Route::get('/{id}', [ServiceController::class, 'show']);
});

// Experience
Route::group(['prefix' => 'experiences'], function () {
    Route::group(['middleware' => AdminMiddleware::class], function () {
        Route::post('/', [ExperienceController::class, 'store']);
        Route::post('/{id}', [ExperienceController::class, 'update']);
        Route::delete('/{id}', [ExperienceController::class, 'destroy']);
    });

    Route::get('/', [ExperienceController::class, 'index']);
    Route::get('/{id}', [ExperienceController::class, 'show']);
});

// News
Route::group(['prefix' => 'news'], function () {
    Route::group(['middleware' => AdminMiddleware::class], function () {
        Route::post('/', [NewsController::class, 'store']);
        Route::post('/{id}', [NewsController::class, 'update']);
        Route::delete('/{id}', [NewsController::class, 'destroy']);
    });

    Route::get('/', [NewsController::class, 'index']);
    Route::get('/{id}', [NewsController::class, 'show']);
});

// Zh News
Route::group(['prefix' => 'zh-news'], function () {
    Route::group(['middleware' => AdminMiddleware::class], function () {
        Route::post('/', [NewsController::class, 'zhStore']);
        Route::post('/{id}', [NewsController::class, 'zhUpdate']);
        Route::delete('/{id}', [NewsController::class, 'zhDestroy']);
    });

    Route::get('/', [NewsController::class, 'zhIndex']);
    Route::get('/{id}', [NewsController::class, 'zhShow']);
});

// Team
Route::group(['prefix' => 'teams'], function () {
    Route::group(['middleware' => AdminMiddleware::class], function () {
        Route::post('/', [TeamController::class, 'store']);
        Route::post('/{id}', [TeamController::class, 'update']);
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

    Route::get('/public', [ImageController::class, 'getPublicImages']);
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

// Zh Contact
Route::group(['prefix' => 'zh-contact'], function () {
    Route::group(['middleware' => AdminMiddleware::class], function () {
        Route::delete('/{id}', [ContactController::class, 'zhDestroy']);
        Route::get('/{id}', [ContactController::class, 'zhShow']);
        Route::get('/', [ContactController::class, 'zhIndex']);
    });

    Route::post('/', [ContactController::class, 'zhStore']);
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

// Zh Page
Route::group(['prefix' => 'zh-pages'], function () {
    Route::group(['middleware' => AdminMiddleware::class], function () {
        Route::post('/', [PageController::class, 'zhStore']);
        Route::patch('/{slug}', [PageController::class, 'zhUpdate']);
        Route::delete('/{id}', [PageController::class, 'zhDestroy']);
    });

    Route::get('/', [PageController::class, 'zhIndex']);
    Route::get('/{id}', [PageController::class, 'zhShow']);
    Route::get('/slug/{slug}', [PageController::class, 'zhGetPageBySlug']);
});

// Review
Route::group([
    'middleware' => 'api',
    'prefix' => 'review',
], function () {
    Route::group(['middleware' => AdminMiddleware::class], function () {
        Route::post('/', [ReviewController::class, 'store']);
        Route::post('/{id}', [ReviewController::class, 'update']);
        Route::delete('/{id}', [ReviewController::class, 'destroy']);
    });

    Route::get('/', [ReviewController::class, 'index']);
    Route::get('/{id}', [ReviewController::class, 'show']);
});

// Zh Review
Route::group([
    'middleware' => 'api',
    'prefix' => 'zh-review',
], function () {
    Route::group(['middleware' => AdminMiddleware::class], function () {
        Route::post('/', [ReviewController::class, 'zhStore']);
        Route::post('/{id}', [ReviewController::class, 'zhUpdate']);
        Route::delete('/{id}', [ReviewController::class, 'zhDestroy']);
    });

    Route::get('/', [ReviewController::class, 'zhIndex']);
    Route::get('/{id}', [ReviewController::class, 'zhShow']);
});

// Search
Route::group(['prefix' => 'search'], function () {
    Route::get('/', [SearchController::class, 'search']);
});

// Zh Search
Route::group(['prefix' => 'zh-search'], function () {
    Route::get('/', [SearchController::class, 'zhSearch']);
});

//PageVisit
Route::post('/track-visit', [VisitController::class, 'trackVisit']);
Route::get('/visit-stats', [VisitController::class, 'getVisitStats']);
