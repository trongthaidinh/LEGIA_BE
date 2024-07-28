<?php

namespace App\Http\Controllers;

use App\Models\SocialLinks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SocialLinksController extends Controller
{
    public function getByUser($userId)
    {
        try{
            auth()->userOrFail();

            $socialLink = DB::table('social_links')
            ->where('user_id', $userId)
            ->first();

            return responseJson($socialLink, 200);

        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return responseJson(null, 404, 'Người dùng chưa xác thực!');
        } catch (\Exception $e) {
            return responseJson(null, 500, 'Đã xảy ra lỗi: ' . $e->getMessage());
        }
    }

    public function createOrUpdate (Request $request){

        try{
            $user = auth()->userOrFail();
            $userId = $user->id;

            $socialLink = DB::table('social_links')
            ->where('user_id', $userId)
            ->first();

            $data = $request->only(['telegram_link', 'facebook_link', 'instagram_link', 'x_link']);


            if($socialLink){
                DB::table('social_links')
                ->where('user_id', $userId)
                ->update($data);

                $socialLink = DB::table('social_links')
                ->where('user_id', $userId)
                ->first();

                return responseJson($socialLink, 200, "Cập nhật liên kết mạng xã hội thành công!");
            }else{
                $res = SocialLinks::create(array_merge(
                    $data,
                    ['user_id' => $user]
                ));

                return responseJson($res, 200, "Cập nhật liên kết mạng xã hội thành công!");
            }



        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return responseJson(null, 404, 'Người dùng chưa xác thực!');
        } catch (\Exception $e) {
            return responseJson(null, 500, 'Đã xảy ra lỗi: ' . $e->getMessage());
        }
    }

}
