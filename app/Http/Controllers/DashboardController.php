<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function overviewUsers(Request $request)
    {
        try{
            auth()->userOrFail();

            $filterOption = $request->input('filter', 'month');
            $totalUser = User::count();
            $increasePercent = null;


            switch ($filterOption) {
                case 'month':
                    $currentMonth = now()->startOfMonth();
                    $lastMonth = now()->subMonth()->startOfMonth();

                    $currentMonthUsers = User::whereBetween('created_at', [$currentMonth, now()])->count();
                    $lastMonthUsers = User::whereBetween('created_at', [$lastMonth, $currentMonth])->count();

                    if($lastMonthUsers == 0){
                        $increasePercent = (($currentMonthUsers - $lastMonthUsers)) * 100;
                    }else{
                        $increasePercent = (($currentMonthUsers - $lastMonthUsers) / $lastMonthUsers) * 100;
                    }
                    break;
                case 'year':
                    $currentYear = now()->startOfYear();
                    $lastYear = now()->subYear()->startOfYear();

                    $currentYearUsers = User::whereBetween('created_at', [$currentYear, now()])->count();
                    $lastYearUsers = User::whereBetween('created_at', [$lastYear, $currentYear])->count();

                    if($lastYearUsers == 0){
                        $increasePercent = (($currentYearUsers - $lastYearUsers)) * 100;
                    }else{
                        $increasePercent = (($currentYearUsers - $lastYearUsers) / $lastYearUsers) * 100;
                    }
                    break;
                default:
                    return responseJson(null, 400, 'Thông tin bộ lọc không hợp lệ');
            }

            $data = [
                'total' => $totalUser,
                'increase_percent' => $increasePercent,
                'filter_by' => $filterOption
            ];

            return responseJson($data);

        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, "Người dùng chưa xác thực!");
        }
    }

    public function overviewPosts(Request $request)
    {
        try{
            auth()->userOrFail();

            $filterOption = $request->input('filter', 'month');
            $totalPost = Post::count();
            $increasePercent = null;


            switch ($filterOption) {
                case 'month':
                    $currentMonth = now()->startOfMonth();
                    $lastMonth = now()->subMonth()->startOfMonth();

                    $currentMonthPosts = Post::whereBetween('created_at', [$currentMonth, now()])->count();
                    $lastMonthPosts = Post::whereBetween('created_at', [$lastMonth, $currentMonth])->count();

                    if($lastMonthPosts == 0){
                        $increasePercent = (($currentMonthPosts - $lastMonthPosts)) * 100;
                    }else{
                        $increasePercent = (($currentMonthPosts - $lastMonthPosts) / $lastMonthPosts) * 100;
                    }
                    break;
                case 'year':
                    $currentYear = now()->startOfYear();
                    $lastYear = now()->subYear()->startOfYear();

                    $currentYearPosts = Post::whereBetween('created_at', [$currentYear, now()])->count();
                    $lastYearPosts = Post::whereBetween('created_at', [$lastYear, $currentYear])->count();

                    if($lastYearPosts == 0){
                        $increasePercent = (($currentYearPosts - $lastYearPosts)) * 100;
                    }else{
                        $increasePercent = (($currentYearPosts - $lastYearPosts) / $lastYearPosts) * 100;
                    }

                    break;
                default:
                    return responseJson(null, 400, 'Thông tin bộ lọc không hợp lệ');
            }

            $data = [
                'total' => $totalPost,
                'increase_percent' => $increasePercent,
                'filter_by' => $filterOption
            ];

            return responseJson($data);


        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, "Người dùng chưa xác thực!");
        }
    }

    public function sexRatio()
    {
        try{
            auth()->userOrFail();

            $totalGender = User::count();

            $maleCount = User::where('gender', 'male')->count();
            $femaleCount = User::where('gender', 'female')->count();
            $otherCount = User::where('gender', 'other')->count();

            $maleRatio = $maleCount / $totalGender * 100;
            $femaleRatio = $femaleCount / $totalGender * 100;
            $otherRatio = $otherCount / $totalGender * 100;

            $data = [
                'male_count' => $maleCount,
                'female_count' => $femaleCount,
                'other_count' => $otherCount,
                'male_ratio' => $maleRatio,
                'female_ratio' => $femaleRatio,
                'other_ratio' => $otherRatio,

            ];

            return responseJson($data);

        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, "Người dùng chưa xác thực!");
        }
    }

    public function detailedPosts(Request $request)
    {
        try{
            auth()->userOrFail();

            $validation = validator($request->all(), [
                'year' => 'required'
            ],
            [
                'year.required' => 'Vui lòng chọn năm cần xem thông tin',
            ]
            );

            if($validation->fails()){
                return responseJson(null, 400, $validation->errors());
            }

            $year = $request->input('year');

            $posts = Post::selectRaw('count(*) as count, month(created_at) as month')
                ->whereYear('created_at', $year)
                ->groupBy('month')
                ->get()
                ->keyBy('month')
                ->map(function ($item) {
                    return $item->count;
                })
                ->toArray();

            $result = [];
            for($i = 1; $i <= 12; $i++){
                $result[] = $posts[$i] ?? 0;
            }

            return responseJson($result);

        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, "Người dùng chưa xác thực!");
        }
    }

    public function detailedUsers(Request $request)
    {
        try{
            auth()->userOrFail();

            $validation = validator($request->all(), [
                'year' => 'required'
            ],
            [
                'year.required' => 'Vui lòng chọn năm cần xem thông tin',
            ]
            );

            if($validation->fails()){
                return responseJson(null, 400, $validation->errors());
            }

            $year = $request->input('year');

            $posts = User::selectRaw('count(*) as count, month(created_at) as month')
                ->whereYear('created_at', $year)
                ->groupBy('month')
                ->get()
                ->keyBy('month')
                ->map(function ($item) {
                    return $item->count;
                })
                ->toArray();

            $result = [];
            for($i = 1; $i <= 12; $i++){
                $result[] = $posts[$i] ?? 0;
            }

            return responseJson($result);

        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, "Người dùng chưa xác thực!");
        }
    }

}
