<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use App\Mail\ResetPasswordMail;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
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
            $result = $request->file('avatar')->storeOnCloudinary();
            $avatarPublicId = $result->getPublicId();
            $avatarPath = "{$result->getSecurePath()}?public_id={$avatarPublicId}";
        }else {
            if($request->gender == 'male'){
                $avatarPath = "https://res.cloudinary.com/dh5674gvh/image/upload/fl_preserve_transparency/v1719510046/samples/AvatarMale_ixpufu.jpg?_s=public-apps";
            }else if($request->gender == 'female'){
                $avatarPath = "https://res.cloudinary.com/dh5674gvh/image/upload/fl_preserve_transparency/v1719510046/samples/AvatarFemale_olfayu.jpg?_s=public-apps";
            }else if($request->gender == 'other'){
                $avatarPath = "https://res.cloudinary.com/dh5674gvh/image/upload/fl_preserve_transparency/v1719510046/samples/AvatarOther_ftskmk.jpg?_s=public-apps";
            }
        }

        $user = User::create(array_merge(
            $validator->validated(),
            ['password' => bcrypt($request->password)],
            ['avatar' => $avatarPath]
        ));

        return responseJson($user, 201, 'Đăng ký người dùng mới thành công!');
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        $validator = Validator::make($credentials, [
            'email' => 'required|email',
            'password' => 'required',
        ], userValidatorMessages());

        if($validator->fails()){
            return responseJson(null, 400, $validator->errors());
        }

        if(! $user = User::where('email', $credentials['email'])->first()) {
            return responseJson(null, 404, 'Sai email đăng nhập');
        }


        if (password_verify($credentials['password'], $user->password) == false) {
            return responseJson(null, 404, 'Sai mật khẩu đăng nhập');
        }


        if (! $token = auth()->attempt($credentials)) {

            return responseJson(null, 404, 'Không tìm thấy người dùng');
        }

        $email = $credentials['email'];

        $refreshToken = $this->_generateRefreshToken($email);

        $user->markOnline();

        return responseJson([
            'accessToken' => $token,
            'refreshToken' => $refreshToken,
            'accessTokenExpiresAt' => time() + config('jwt.ttl'),
            'refreshTokenExpiresAt' => time() + config('jwt.refresh_ttl')
        ], 200, 'Đăng nhập thành công!');


    }

    public function refresh(Request $request)
    {
        try {
            $refreshToken = $request->refresh_token;
            $decodedToken = JWTAuth::getJWTProvider()->decode($refreshToken);
            $user = User::where('email', $decodedToken['sub'])->first();

            if (! $user) {
                return responseJson(['error' => 'Không tìm thấy người dùng'], 404);
            }

            if (! $newAccessToken = auth()->login($user)) {
                return responseJson(null, 401);
            }


            $newRefreshToken = $this->_generateRefreshToken($user->email);

            return responseJson([
                'accessToken' => $newAccessToken,
                'refreshToken' => $newRefreshToken,
                'accessTokenExpiresAt' => time() + config('jwt.ttl'),
                'refreshTokenExpiresAt' => time() +  config('jwt.refresh_ttl')
            ]);
        } catch (Exception $ex) {
            return responseJson(null, 401, $ex->getMessage());
        }
    }

    public function logout()
    {
        auth()->logout(true);


        return responseJson(null, 200, 'Đăng xuất thành công!');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ], [
            'email.required' => 'Email là bắt buộc.',
            'email.email' => 'Email không hợp lệ.',
            'email.exists' => 'Email không tồn tại trong hệ thống.',
        ]);

        if ($validator->fails()) {
            return responseJson(null, 400, $validator->errors());
        }

        $email = $request->input('email');
        $token = Password::createToken(User::where('email', $email)->first());

        Mail::to($email)->send(new ResetPasswordMail($token, $email));

        return responseJson(null, 200, 'Liên kết đặt lại mật khẩu đã được gửi đến email của bạn.');
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'token' => 'required',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'email.required' => 'Email là bắt buộc.',
            'email.email' => 'Email không hợp lệ.',
            'email.exists' => 'Email không tồn tại trong hệ thống.',
            'token.required' => 'Token là bắt buộc.',
            'password.required' => 'Mật khẩu là bắt buộc.',
            'password.min' => 'Mật khẩu phải có ít nhất :min ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
        ]);

        if ($validator->fails()) {
            return responseJson(null, 400, $validator->errors());
        }

        $credentials = $request->only('email', 'password', 'password_confirmation', 'token');

        $resetPasswordStatus = Password::reset($credentials, function ($user, $password) {
            $user->password = bcrypt($password);
            $user->save();
        });

        if ($resetPasswordStatus == Password::INVALID_TOKEN) {
            return responseJson(null, 400, 'Token không hợp lệ.');
        }

        return responseJson(null, 200, 'Mật khẩu đã được đặt lại thành công.');
    }

    private function _generateRefreshToken($email){
        $data = [
            'sub' => $email,
            'random' => rand() . time(),
            'exp' => time() + config('jwt.JWT_TTL')
        ];

        return JWTAuth::getJWTProvider()->encode($data);
    }

}
