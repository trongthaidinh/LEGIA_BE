<?php

namespace App\Http\Controllers;

class UserController extends Controller
{
    public function me(){
        if(! $user = auth()->user()){
            return responseJson(null, 404, 'Không tìm thấy người dùng!');
        }

        return responseJson($user);
    }
}
