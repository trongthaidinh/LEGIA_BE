<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    private $messages = [
        'email.required' => 'Vui lòng nhập email',
        'email.email' => 'Email không đúng định dạng',
        'email.unique' => 'Email được sử dụng',
        'password.required' => 'Vui lòng nhập mật khẩu',
        'password.max' => 'Mật khẩu tối đa 200 ký tự',
        'password.min' => 'Mật khẩu tối thiểu 8 ký tự',
        'first_name.required' => 'Vui lòng nhập tên',
        'last_name.required' => 'Vui lòng nhập họ',
        'first_name.max' => 'Tên tối đa 30 ký tự',
        'last_name.max' => 'Họ tối đa 20 ký tự',
        'phone_number.required' => 'Vui lòng nhập số điện thoại',
        'phone_number.max' => 'Số điện thoại tối đa 10 ký tự',
        'phone_number.unique' => 'Số điện thoại đã được sử dụng',
    ];


    public function register(Request $request)
    {
        $avatarPath = null;
        $avatarPublicId = null;

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|max:30',
            'last_name' => 'required|max:20',
            'avatar' => 'nullable',
            'gender' => 'required|in:male,female,other',
            'role' => 'nullable|in:user,admin',
            'address' => 'nullable|max:120',
            'email' => 'required|email|unique:users',
            'phone_number' => 'required|max:10|unique:users',
            'password' => 'required|min:8|max:200',
            'date_of_birth' => 'nullable|date',
            'is_verified' => 'nullable|boolean',
        ], $this->messages);

        if($validator->fails()){
            return responseJson(['messages' => $validator->errors()], 400);
        }

        if($request->hasFile('avatar')){
            $result = $request->file('avatar')->storeOnCloudinary();
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
        ], $this->messages);

        if($validator->fails()){
            return responseJson(['messages' => $validator->errors()], 400);
        }

        if (! $token = auth()->attempt($credentials)) {
            return responseJson(['error' => 'Không tìm thấy người dùng!'], 401);
        }


        return responseJson(['accessToken' => $token]);
    }

    public function me()
    {
        return responseJson(auth()->user());
    }



}
