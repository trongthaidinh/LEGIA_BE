<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

require __DIR__ . '/../bootstrap/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Khởi động ứng dụng
$app->make(Illuminate\Contracts\Http\Kernel::class)->handle(
    Illuminate\Http\Request::capture()
);

// Kiểm tra và gọi lệnh Artisan để liên kết storage
try {
    if (!File::exists(public_path('storage'))) {
        Artisan::call('storage:link');
        echo "Liên kết storage đã được tạo thành công!";
    } else {
        echo "Storage đã được liên kết trước đó.";
    }
} catch (Exception $e) {
    echo "Đã xảy ra lỗi: " . $e->getMessage();
}
