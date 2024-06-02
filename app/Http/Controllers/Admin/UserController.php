<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return responseJson($users, 200, 'Danh sách người dùng');
    }

    public function show($id)
    {
        $user = User::find($id);
        if (!$user) {
            return responseJson(null, 404, 'Người dùng không tồn tại');
        }
        return responseJson($user, 200, 'Thông tin chi tiết người dùng');
    }

    public function lock($id)
    {
        $user = User::find($id);
        if (!$user) {
            return responseJson(null, 404, 'Người dùng không tồn tại');
        }
        $user->is_locked = true;
        $user->save();
        return responseJson(null, 200, 'Tài khoản người dùng đã bị khóa');
    }

    public function unlock($id)
    {
        $user = User::find($id);
        if (!$user) {
            return responseJson(null, 404, 'Người dùng không tồn tại');
        }
        $user->is_locked = false;
        $user->save();
        return responseJson(null, 200, 'Tài khoản người dùng đã được mở khóa');
    }
}
