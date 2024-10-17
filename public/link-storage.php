<?php

// Đảm bảo rằng ứng dụng Laravel đã được khởi động

use Illuminate\Support\Facades\Artisan;

require __DIR__ . '/../bootstrap/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Khởi động ứng dụng
$app->make(Illuminate\Contracts\Http\Kernel::class)->handle(
    Illuminate\Http\Request::capture()
);

// Kiểm tra nếu symbolic link đã tồn tại
$storageLink = __DIR__ . '/storage';

if (is_link($storageLink)) {
    echo "Storage đã được liên kết với public.";
} else {
    // Thực hiện liên kết thư mục storage với public/storage
    $exitCode = Artisan::call('storage:link');

    if ($exitCode === 0) {
        echo "Liên kết storage thành công!";
    } else {
        echo "Lỗi khi liên kết storage.";
    }
}
