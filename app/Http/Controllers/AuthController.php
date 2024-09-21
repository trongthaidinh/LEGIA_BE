<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use App\Mail\ResetPasswordMail;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use DateInterval;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use DateTimeImmutable;

class AuthController extends Controller
{

    public function register(Request $request)
    {


        $validator = Validator::make($request->except(['role', 'is_verified']), [
            'name' => 'required|max:30',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|max:200',
        ]);

        if ($validator->fails()) {
            return responseJson(null, 400, $validator->errors());
        }


        $user = User::create(array_merge(
            $validator->validated(),
            ['password' => bcrypt($request->password)],
        ));

        return responseJson($user, 201, 'Đăng ký người dùng mới thành công!');
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        $validator = Validator::make($credentials, [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return responseJson(null, 400, $validator->errors());
        }

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! password_verify($credentials['password'], $user->password)) {
            return responseJson(null, 404, 'Thông tin đăng nhập không chính xác');
        }

        if ($user->is_banned) {
            return responseJson(null, 403, 'Tài khoản đã bị khóa');
        }

        if (! $token = auth()->attempt($credentials)) {
            return responseJson(null, 401, 'Không thể tạo token');
        }
        $refreshToken = $this->_generateRefreshToken($user->email);

        $accessTokenExpiresAt = (new DateTimeImmutable())
            ->modify('+' . config('jwt.ttl') . ' minutes')
            ->add(new DateInterval('PT7H'));

        $refreshTokenExpiresAt = (new DateTimeImmutable())
            ->modify('+' . config('jwt.refresh_ttl') . ' minutes')
            ->add(new DateInterval('PT7H'));

        return responseJson([
            'accessToken' => $token,
            'refreshToken' => $refreshToken,
            'accessTokenExpiresAt' => $accessTokenExpiresAt->format('Y-m-d H:i:s'),
            'refreshTokenExpiresAt' => $refreshTokenExpiresAt->format('Y-m-d H:i:s'),
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
                return responseJson(null, 401, 'Không thể tạo token mới');
            }

            $newRefreshToken = $this->_generateRefreshToken($user->email);

            $accessTokenExpiresAt = (new DateTimeImmutable())
                ->modify('+' . config('jwt.ttl') . ' minutes')
                ->add(new DateInterval('PT7H'));

            $refreshTokenExpiresAt = (new DateTimeImmutable())
                ->modify('+' . config('jwt.refresh_ttl') . ' minutes')
                ->add(new DateInterval('PT7H'));

            return responseJson([
                'accessToken' => $newAccessToken,
                'refreshToken' => $newRefreshToken,
                'accessTokenExpiresAt' => $accessTokenExpiresAt->format('Y-m-d H:i:s'),
                'refreshTokenExpiresAt' => $refreshTokenExpiresAt->format('Y-m-d H:i:s'),
            ], 200, 'Làm mới token thành công!');
        } catch (Exception $ex) {
            return responseJson(null, 401, $ex->getMessage());
        }
    }


    public function logout()
    {
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
            'password' => 'required|string|min:6',
        ], [
            'email.required' => 'Email là bắt buộc.',
            'email.email' => 'Email không hợp lệ.',
            'email.exists' => 'Email không tồn tại trong hệ thống.',
            'token.required' => 'Token là bắt buộc.',
            'password.required' => 'Mật khẩu là bắt buộc.',
            'password.min' => 'Mật khẩu phải có ít nhất :min ký tự.',
        ]);

        if ($validator->fails()) {
            return responseJson(null, 400, $validator->errors());
        }

        $credentials = $request->only('email', 'password', 'token');

        $resetPasswordStatus = Password::reset($credentials, function ($user, $password) {
            $user->password = bcrypt($password);
            $user->save();
        });

        if ($resetPasswordStatus == Password::INVALID_TOKEN) {
            return responseJson(null, 400, 'Token không hợp lệ.');
        }

        return responseJson(null, 200, 'Mật khẩu đã được đặt lại thành công.');
    }

    private function _generateRefreshToken($email)
    {
        $data = [
            'sub' => $email,
            'random' => rand() . time(),
            'exp' => time() + config('jwt.JWT_TTL')
        ];

        return JWTAuth::getJWTProvider()->encode($data);
    }
}
