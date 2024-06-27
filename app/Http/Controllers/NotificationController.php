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

            $notifications = Notification::where('owner_id', $user->id)
                ->with('user')
                ->orderByDesc('created_at')
                ->get();

            return responseJson($notifications, 200, "Lấy thành công danh sách thông báo");
        } catch (\Exception $e) {
            return responseJson(null, 500, 'Đã xảy ra lỗi khi lấy danh sách thông báo: ' . $e->getMessage());
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

            if ($notification->owner_id !== $user->id) {
                return responseJson(null, 403, 'Bạn không có quyền đánh dấu thông báo này là đã đọc');
            }

            $notification->read = true;
            $notification->save();

            

            return responseJson($notification, 200, "Thông báo được đánh dấu là đã đọc");
        } catch (\Exception $e) {
            return responseJson(null, 500, 'Đã xảy ra lỗi khi đánh dấu là đã đọc thông báo: ' . $e->getMessage());
        }
    }
}
