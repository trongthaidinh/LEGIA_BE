<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function updatePassword(Request $request)
    {
        try {
            $user = auth()->userOrFail();

            $validator = Validator::make($request->all(), [
                'oldPassword' => 'required|string',
                'newPassword' => 'required|string|min:8|max:200',
            ]);

            if ($validator->fails()) {
                return responseJson(null, 400, $validator->errors());
            }

            if (Hash::check($request->old_password, $user->password)) {
                return responseJson(null, 400, 'Mật khẩu cũ không chính xác!');
            }

            if (Hash::check($request->new_password, $user->password)) {
                return responseJson(null, 400, 'Mật khẩu cũ và mật khẩu mới không được trùng nhau!');
            }

            $user->update(['password' => bcrypt($request->newPassword)]);
            $user->save();

            return responseJson($user, 201, 'Đổi mật khẩu mới thành công!');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return responseJson(null, 404, 'Người dùng chưa xác thực!');
        }
    }
}
