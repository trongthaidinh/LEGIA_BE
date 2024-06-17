<?php

namespace App\Http\Controllers;

use App\Events\NotificationRead;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return responseJson(null, 401, 'Chưa xác thực người dùng');
            }

            $notifications = Notification::where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->get();

            return responseJson($notifications, 200, "Lấy thành công danh sách thông báo");
        } catch (\Exception $e) {
            return responseJson(null, 500, 'Đã xảy ra lỗi khi lấy danh sách thông báo' . $e->getMessage());
        }
    }

    public function markAsRead($id)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return responseJson(null, 401, 'Chưa xác thực người dùng');
            }

            $notification = Notification::findOrFail($id);

            $notification->read = true;
            $notification->save();

            event(new NotificationRead($notification));

            return responseJson($notification, 200, "Thông báo được đánh dấu là đã đọc");
        } catch (\Exception $e) {
            return responseJson(null, 500, 'Đã xảy ra lỗi khi đánh dấu là đã đọc thông báo' . $e->getMessage());
        }
    }
}
