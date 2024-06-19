<?php

namespace App\Http\Controllers;

use App\Models\User;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;


class UserController extends Controller
{

    public function me(){
        if(! $user = auth()->user()){
            return responseJson(null, 404, 'Người dùng chưa xác thực!');
        }

        return responseJson($user);
    }

    public function getProfile($id){
        try{
            auth()->userOrFail();
            $profile = DB::table('users')
            ->where('id', $id)
            ->first();

            if( ! $profile ) return responseJson(null, 404, 'Không tìm thấy thông tin người dùng!');


            return responseJson($profile, 200);

        }catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            var_dump($e);
            return responseJson(null, 404, 'Người dùng chưa xác thực!');
        }
    }

    public function updateInformation(Request $request){
        try{
            $user = auth()->userOrFail();

            $dataUpdate = $request->except(['is_verified', 'role', 'password', 'email', 'phone_number', 'avatar']);

            $validator = Validator::make($dataUpdate, [
                'first_name' => 'nullable|max:30',
                'last_name' => 'nullable|max:20',
                'gender' => 'nullable|in:male,female,other',
                'address' => 'nullable|max:120',
                'date_of_birth' => 'nullable|date',
            ], userValidatorMessages());

            if($validator->fails()){
                return responseJson(null, 400, $validator->errors());
            }

            $user->update($dataUpdate);
            $user->save();

            return responseJson($user, 200, 'Cập nhật thông tin người dùng thành công!');

        }catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            var_dump($e);
            return responseJson(null, 404, 'Người dùng chưa xác thực!');
        }
    }

    public function updatePassword(Request $request) {
        try {
            $user = auth()->userOrFail();

            $validator = Validator::make($request->all(), [
                'old_password' => 'required|string',
                'new_password' => 'required|string|min:8|max:200',
            ], userValidatorMessages());

            if($validator->fails()){
                return responseJson(null, 400, $validator->errors());
            }

            if (!Hash::check($request->old_password, $user->password)) {
                return responseJson(null, 400, 'Mật khẩu cũ không chính xác!');
            }

            if (Hash::check($request->new_password, $user->password)) {
                return responseJson(null, 400, 'Mật khẩu cũ và mật khẩu mới không được trùng nhau!');
            }

            $user->update(['password' => bcrypt($request->new_password)]);
            $user->save();

            return responseJson($user, 201, 'Đổi mật khẩu mới thành công!');

        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return responseJson(null, 404, 'Người dùng chưa xác thực!');
        }

    }

    public function updateAvatar(Request $request){
        try{
            $user = auth()->userOrFail();

            $dataUpdate = $request->only('avatar');

            $validator = Validator::make($dataUpdate, [
                'avatar' => 'required|file|image|mimes:jpeg,png,jpg|max:2048',
            ], userValidatorMessages());

            if($validator->fails()){
                return responseJson(null, 400, $validator->errors());
            }

            if($oldAvatar = $user->avatar){
                $publicId = getPublicIdFromAvatarUrl($oldAvatar);
                Cloudinary::destroy($publicId);
            }


            $result = $request->file('avatar')->storeOnCloudinary('avatars');
            $avatarPublicId = $result->getPublicId();
            $avatarPath = "{$result->getSecurePath()}?public_id={$avatarPublicId}";

            $user->update(['avatar' => $avatarPath]);
            $user->save();

            return responseJson($user, 200, 'Cập nhật ảnh đại diện người dùng thành công!');

        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, 'Người dùng chưa xác thực!');
        }
    }

    public function updateCoverImage(Request $request){
        try{
            $user = auth()->userOrFail();

            $dataUpdate = $request->only('cover_image');

            $validator = Validator::make($dataUpdate, [
                'cover_image' => 'required|file|image|mimes:jpeg,png,jpg|max:2048',
            ], userValidatorMessages());

            if($validator->fails()){
                return responseJson(null, 400, $validator->errors());
            }

            if($oldCoverImage = $user->cover_image){
                $publicId = getPublicIdFromAvatarUrl($oldCoverImage);
                Cloudinary::destroy($publicId);
            }


            $result = $request->file('cover_image')->storeOnCloudinary('cover_images');
            $coverImagePublicId = $result->getPublicId();
            $coverImagePath = "{$result->getSecurePath()}?public_id={$coverImagePublicId}";

            $user->update(['cover_image' => $coverImagePath]);
            $user->save();

            return responseJson($user, 200, 'Cập nhật ảnh bìa người dùng thành công!');

        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, 'Người dùng chưa xác thực!');
        }
    }

    public function deleteAvatar(){
        try{
            $user = auth()->userOrFail();

            if(! $oldAvatar = $user->avatar){
                return responseJson(null, 400, 'Bạn chưa có ảnh đại diện!');
            }

            $publicId = getPublicIdFromAvatarUrl($oldAvatar);
            Cloudinary::destroy($publicId);

            $user->update(['avatar' => null]);
            $user->save();

            return responseJson($user, 200, 'Xóa ảnh đại diện người dùng thành công!');

        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, 'Người dùng chưa xác thực!');
        }
    }

    public function deleteCoverImage(){
        try{
            $user = auth()->userOrFail();

            if(! $oldCoverImage = $user->cover_image){
                return responseJson(null, 400, 'Bạn chưa có ảnh bìa!');
            }

            $publicId = getPublicIdFromAvatarUrl($oldCoverImage);
            Cloudinary::destroy($publicId);

            $user->update(['cover_image' => null]);
            $user->save();

            return responseJson($user, 200, 'Xóa ảnh bìa người dùng thành công!');

        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, 'Người dùng chưa xác thực!');
        }
    }

    public function find(Request $request){
        try{
            $user = auth()->userOrFail();


            $validator = Validator::make($request->all(), [
                'q' => 'required',
            ], [
                'q.required' => 'Vui lý nhập thông tin người dùng cần tìm kiếm!',
            ]);

            if($validator->fails()){
                return responseJson(null, 400, $validator->errors());
            }


            $users = DB::table('users')
            ->where('id', '!=', $user->id)
            ->where(function($query) use ($request) {
                $query->where('first_name', 'like', '%' . $request->q . '%')
                    ->orWhere('last_name', 'like', '%' . $request->q . '%')
                    ->orWhere('email', 'like', '%' . $request->q . '%')
                    ->orWhere('phone_number', 'like', '%' . $request->q . '%');
            })
            ->get();

            return responseJson($users, 200);


        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, 'Người dùng chưa xác thực!');
        }
    }

}
