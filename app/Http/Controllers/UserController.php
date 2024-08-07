<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Friendship;
use App\Models\Post;
use App\Models\PostImage;
use App\Models\User;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;


class UserController extends Controller
{

    public function me(){
        $user = auth()->user();

        if(!$user){
            return responseJson(null, 404, 'Người dùng chưa xác thực!');
        };

        $user = User::where('id', $user->id)
            ->with('socialLinks')
            ->first();

        return responseJson($user);
    }

    public function getProfile($id){
        try{
            $user = auth()->userOrFail();

            $profile = User::where('id', $id)
            ->with('socialLinks')
            ->first();

            if( ! $profile ) return responseJson(null, 404, 'Không tìm thấy thông tin người dùng!');

            $partnerId = $id;
            $userId = $user->id;

            if($partnerId != $userId){
                $conversation = Conversation::whereHas('participants', function ($query) use ($userId, $partnerId) {
                    $query->where('user_id', $userId)
                          ->orWhere('user_id', $partnerId);
                }, '=', 2)
                ->where('type', 'individual')
                ->first('id');

                if($conversation){
                    $profile->conversation_id = $conversation->id;

                }else{
                    $profile->conversation_id = null;
                }


                $friendship = Friendship::where(function ($query) use ($partnerId, $userId) {
                    $query->where(function ($query) use ($partnerId, $userId) {
                        $query->where('friend_id', $partnerId)
                              ->where('owner_id', $userId);
                    })->orWhere(function ($query) use ($partnerId, $userId) {
                        $query->where('friend_id', $userId)
                              ->where('owner_id', $partnerId);
                    });
                })->first();

                if($friendship){
                    $profile->friendship = $friendship;
                }else{
                    $profile->friendship = null;
                }
            }

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
                'relationship_status' => 'nullable|in:single,dating,married,widowed,divorced,complicated',
                'bio' => 'nullable|max:120',
            ], userValidatorMessages());

            if($validator->fails()){
                return responseJson(null, 400, $validator->errors());
            }
            $user->update($dataUpdate);
            $user->save();

            return responseJson($user, 200, 'Cập nhật thông tin người dùng thành công!');

        }catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
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
                'avatar' => 'required|file|image|mimes:jpeg,png,jpg,webp|max:2048',
            ], userValidatorMessages());

            if($validator->fails()){
                return responseJson(null, 400, $validator->errors());
            }

            $result = $request->file('avatar')->storeOnCloudinary('avatars');
            $avatarPublicId = $result->getPublicId();
            $avatarPath = "{$result->getSecurePath()}?public_id={$avatarPublicId}";

            $user->update(['avatar' => $avatarPath]);
            $user->save();



            $post = Post::create([
                'owner_id' => $user->id,
                'privacy' => 'PUBLIC',
                'post_type' => 'AVATAR_CHANGE',
            ]);

            PostImage::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
                'url' => $avatarPath,
            ]);

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
                'cover_image' => 'required|file|image|mimes:jpeg,png,jpg,webp|max:2048',
            ], userValidatorMessages());

            if($validator->fails()){
                return responseJson(null, 400, $validator->errors());
            }

            $result = $request->file('cover_image')->storeOnCloudinary('cover_images');
            $coverImagePublicId = $result->getPublicId();
            $coverImagePath = "{$result->getSecurePath()}?public_id={$coverImagePublicId}";

            $user->update(['cover_image' => $coverImagePath]);
            $user->save();

            $post = Post::create([
                'owner_id' => $user->id,
                'privacy' => 'PUBLIC',
                'post_type' => 'COVER_CHANGE',
            ]);

            PostImage::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
                'url' => $coverImagePath,
            ]);

            return responseJson($user, 200, 'Cập nhật ảnh bìa người dùng thành công!');

        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, 'Người dùng chưa xác thực!');
        }
    }

    public function deleteAvatar(){
        try{
            $user = auth()->userOrFail();

            if(! $user->avatar){
                return responseJson(null, 400, 'Bạn chưa có ảnh đại diện!');
            }

            $avatarPath = null;

            if($user->gender == 'male'){
                $avatarPath = "https://res.cloudinary.com/dh5674gvh/image/upload/fl_preserve_transparency/v1719510046/samples/AvatarMale_ixpufu.jpg?_s=public-apps";
            }else if($user->gender == 'female'){
                $avatarPath = "https://res.cloudinary.com/dh5674gvh/image/upload/fl_preserve_transparency/v1719510046/samples/AvatarFemale_olfayu.jpg?_s=public-apps";
            }else if($user->gender == 'other'){
                $avatarPath = "https://res.cloudinary.com/dh5674gvh/image/upload/fl_preserve_transparency/v1719510046/samples/AvatarOther_ftskmk.jpg?_s=public-apps";
            }

            $user->update(['avatar' => $avatarPath]);
            $user->save();

            return responseJson($user, 200, 'Xóa ảnh đại diện người dùng thành công!');

        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, 'Người dùng chưa xác thực!');
        }
    }

    public function deleteCoverImage(){
        try{
            $user = auth()->userOrFail();

            if(! $user->cover_image){
                return responseJson(null, 400, 'Bạn chưa có ảnh bìa!');
            }


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

            $users = DB::table('users')
            ->where(function($query) use ($request) {
                $query->where('first_name', 'like', '%' . $request->q . '%')
                    ->orWhere('last_name', 'like', '%' . $request->q . '%')
                    ->orWhere('email', 'like', '%' . $request->q . '%')
                    ->orWhere('phone_number', 'like', '%' . $request->q . '%');
            })
            ->where('id', '!=', $user->id)
            ->get();


            // Lặp qua từng user trong danh sách và thêm thuộc tính is_my_friend để biết người dùng đang xem có là bạn bè với user đó không
            // Vì is_my_friend là một thuộc tính của user, chúng ta cần gọi User::find($u->id) để lấy ra user thật để gọi phương thức isMyFriend
            $users->transform(function ($u) use ($user) {
                $userModel = User::find($u->id);
                $u->is_my_friend = $userModel->isMyFriend($user->id);
                return $u;
            });

            return responseJson($users, 200);


        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, 'Người dùng chưa xác thực!');
        }
    }

    public function getSuggestionList() {
        try{
            $user = auth()->userOrFail();

            $users = User::where('id', '!=', $user->id)
                ->whereDoesntHave('friends', function ($query) use ($user) {
                    $query->where('owner_id', $user->id)
                        ->orWhere('friend_id', $user->id);
                })
                ->inRandomOrder()
                ->limit(15)
                ->get();


            return responseJson($users, 200);

        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, 'Người dùng chưa xác thực!');
        }
    }

    public function getUserImages(Request $request, $id)
    {
        try {
            $authUser = auth()->user();
            if (!$authUser) {
                return responseJson(null, 401, 'Chưa xác thực người dùng');
            }

            $user = User::findOrFail($id);

            $perPage = $request->input('per_page', 9);
            $page = $request->input('page', 1);

            $images = collect();

            if ($user->avatar) {
                $images->push($user->avatar);
            }

            if ($user->cover_image) {
                $images->push($user->cover_image);
            }

            $postImages = DB::table('post_images')
                ->where('user_id', $id)
                ->pluck('url');

            if ($postImages->isNotEmpty()) {
                $images = $images->merge($postImages);
            }

            $imagesCollection = $images->forPage($page, $perPage);

            $response = [
                'images' => $imagesCollection->values()->all(),
                'page_info' => [
                    'total' => $images->count(),
                    'total_page' => (int) ceil($images->count() / $perPage),
                    'current_page' => $page,
                    'next_page' => $page < (int) ceil($images->count() / $perPage) ? $page + 1 : null,
                    'per_page' => $perPage,
                ],
            ];

            return responseJson($response, 200, 'Lấy tất cả ảnh của người dùng thành công!');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return responseJson(null, 404, 'Người dùng chưa xác thực!');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return responseJson(null, 404, 'Không tìm thấy thông tin người dùng!');
        } catch (\Exception $e) {
            return responseJson(null, 500, 'Đã xảy ra lỗi: ' . $e->getMessage());
        }
    }

    public function markOnline(){
        try{
            $user = auth()->userOrFail();

            return $user->markOnline();

        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, 'Người dùng chưa xác thực!');
        }
    }
}
