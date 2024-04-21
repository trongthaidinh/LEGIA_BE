<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        $avatarPath = null;
        $avatarPublicId = null;

        $validator = Validator::make($request->except(['role', 'is_verified']), [
            'first_name' => 'required|max:30',
            'last_name' => 'required|max:20',
            'avatar' => 'nullable|file|image|mimes:jpeg,png,jpg,gif|max:2048',
            'gender' => 'required|in:male,female,other',
            'address' => 'nullable|max:120',
            'email' => 'required|email|unique:users',
            'phone_number' => 'required|max:10|unique:users',
            'password' => 'required|min:8|max:200',
            'date_of_birth' => 'nullable|date',
        ], userValidatorMessages());

        if($validator->fails()){
            return responseJson(null, 400, $validator->errors());
        }

        if($request->hasFile('avatar')){
            $result = $request->file('avatar')->storeOnCloudinary('avatars/' . $request->email);
            $avatarPublicId = $result->getPublicId();
            $avatarPath = "{$result->getSecurePath()}?public_id={$avatarPublicId}";
        }

        $user = User::create(array_merge(
            $validator->validated(),
            ['password' => bcrypt($request->password)],
            ['avatar' => $avatarPath]
        ));

        return responseJson($user, 201, 'Đăng ký người dùng mới thành công!');
    }

    public function login()
    {
        $credentials = request(['email', 'password']);

        $validator = Validator::make($credentials, [
            'email' => 'required|email',
            'password' => 'required|min:8|max:200',
        ], userValidatorMessages());

        if($validator->fails()){
            return responseJson(null, 400, $validator->errors());
        }

        if (! $token = auth()->attempt($credentials)) {
            return responseJson(['error' => 'Không tìm thấy người dùng!'], 401);
        }


        return responseJson(['accessToken' => $token], 200, 'Đăng nhập thành công!');
    }

    public function logout()
    {
        auth()->logout();

        return responseJson(null, 200, 'Đăng xuất thành công!');
    }

}
