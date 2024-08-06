<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Report;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        try {
                $user = auth()->user();
                if (!$user) {
                    return responseJson(null, 401, 'Chưa xác thực người dùng');
                }
                $reports = Report::latest()->paginate(10);

                return response()->json($reports, 200);
            } catch (\Exception $e) {
                return responseJson(null, 500, 'Đã xảy ra lỗi khi gửi báo cáo: ' . $e->getMessage());
            }
    }

    public function store(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return responseJson(null, 401, 'Chưa xác thực người dùng');
            }

            $request->validate([
                'target_id' => 'required|integer', 
                'type' => 'required|in:user,post',
                'code' => 'required|string',
            ]);

            $target = Post::find($request->target_id);

            if (!$target) {
                return responseJson(null, 404, 'Bài viết không tồn tại');
            }

            if ($user->id == $target->owner_id) {
                return responseJson(null, 400, 'Bạn không thể báo cáo bài viết của chính mình');
            }

            $report = Report::create([
                'emitter_id' => $user->id,
                'target_id' => $target->id,
                'type' => $request->type,
                'code' => $request->code,
            ]);

            return responseJson($report, 201, 'Báo cáo đã được gửi thành công!');
        } catch (\Exception $e) {
            return responseJson(null, 500, 'Đã xảy ra lỗi khi gửi báo cáo: ' . $e->getMessage());
        }
    }

    public function approve($id)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return responseJson(null, 401, 'Chưa xác thực người dùng');
            }
            $report = Report::findOrFail($id);

            if ($report->rejected || $report->approved) {
                return responseJson(null, 400, 'Báo cáo đã được phê duyệt, không thể chấp nhận');
            }

            $report->approved = true;
            $report->save();

            return responseJson($report , 200,'Báo cáo đã được phê duyệt thành công' );
        } catch (\Exception $e) {
            return responseJson(null, 500, 'Đã xảy ra lỗi khi phê duyệt báo cáo: ' . $e->getMessage());
        }
    }

    public function reject($id)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return responseJson(null, 401, 'Chưa xác thực người dùng');
            }
            $report = Report::findOrFail($id);

            if ($report->rejected || $report->approved) {
                return responseJson(null, 400, 'Báo cáo đã được phê duyệt, không thể từ chối');
            }

            $report->rejected = true;
            $report->save();

            return responseJson($report , 200, 'Báo cáo đã được từ chối thành công' );
        } catch (\Exception $e) {
            return responseJson(null, 500, 'Đã xảy ra lỗi khi từ chối báo cáo: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return responseJson(null, 401, 'Chưa xác thực người dùng');
            }
            $report = Report::findOrFail($id);
            $report->delete();

            return responseJson(null, 200, 'Báo cáo đã được xóa thành công');
        } catch (\Exception $e) {
            return responseJson(null, 500, 'Đã xảy ra lỗi khi xóa báo cáo: ' . $e->getMessage());
        }
    }


}
