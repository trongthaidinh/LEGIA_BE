<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Helpers\ResponseBuilder;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();

        return responseJson($users);
    }
}
