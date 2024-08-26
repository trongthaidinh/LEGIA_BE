<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Report;
use App\Models\ReportImage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return responseJson(null, 401, 'Chưa xác thực người dùng');
            }

            $type = $request->query('type');
            $status = $request->query('status');
            $targetId = $request->query('target_id');

            $reports = Report::with('images')->latest();

            if ($type && in_array($type, ['user', 'post'])) {
                $reports->where('type', $type);
            }

            if ($status) {
                if ($status === 'resolved') {
                    $reports->whereIn('status', ['approved', 'rejected']);
                } elseif (in_array($status, ['rejected', 'approved', 'pending'])) {
                    $reports->where('status', $status);
                }
            }

            if ($targetId) {
                $reports->where('target_id', $targetId);
            }

            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);
            $paginatedReports = $reports->paginate($perPage, ['*'], 'page', $page);

            $response = [
                'reports' => $paginatedReports->items(),
                'page_info' => [
                    'total' => $paginatedReports->total(),
                    'total_page' => (int) ceil($paginatedReports->total() / $paginatedReports->perPage()),
                    'current_page' => $paginatedReports->currentPage(),
                    'next_page' => $paginatedReports->currentPage() < $paginatedReports->lastPage() ? $paginatedReports->currentPage() + 1 : null,
                    'per_page' => $paginatedReports->perPage(),
                ],
            ];

            return responseJson($response, 200, 'Danh sách báo cáo');
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

            $validator = Validator::make($request->all(), [
                'target_id' => 'required|integer',
                'type' => 'required|in:user,post',
                'code' => 'required|in:101,102,103,104,105,106,107,108,109,110,200,201,202,203,204,205,206,207,208,209,210',
                'description' => 'nullable|string|max:300',
                'images.*' => 'nullable|file|image|mimes:jpeg,png,jpg,webp|max:2048',
            ], [
                'target_id.exists' => 'Nguồi dùng không tồn tại',
                'type.in' => 'Vui lòng chọn đúng loại báo cáo',
                'code.in' => 'Vui loại chọn đúng mã báo cáo',
                'images.*.file' => 'Tếp hình ảnh không hợp lệ.',
                'images.*.image' => 'Tếp hình ảnh phải không hợp lệ.',
                'images.*.mimes' => 'Tếp hình ảnh phải phải là một kỹ thuật: jpeg, png, jpg, webp.',
                'images.*.max' => 'Tếp hình ảnh phải nhỏ hơn 2MB.',
            ]);

            if ($validator->fails()) {
                return responseJson(null, 400, $validator->errors());
            }

            $existingReport = Report::where('emitter_id', $user->id)
                ->where('target_id', $request->target_id)
                ->where('type', $request->type)
                ->first();

            if ($existingReport) {
                return responseJson(null, 400, 'Bạn đã báo cáo ' . ($request->type === 'user' ? 'người dùng' : 'bài viết') . ' này.');
            }

            if ($request->type === 'post') {
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
                    'status' => 'pending',
                    'description' => $request->description,
                ]);
            } elseif ($request->type === 'user') {
                $targetUser = User::find($request->target_id);

                if (!$targetUser) {
                    return responseJson(null, 404, 'Người dùng không tồn tại');
                }

                if ($user->id == $targetUser->id) {
                    return responseJson(null, 400, 'Bạn không thể báo cáo người dùng của chính mình');
                }

                $report = Report::create([
                    'emitter_id' => $user->id,
                    'target_id' => $targetUser->id,
                    'type' => $request->type,
                    'code' => $request->code,
                    'status' => 'pending',
                    'description' => $request->description,
                ]);
            }
            if ($request->hasFile('images')) {
                $imagesUploaded = [];
                $files = $request->file('images');

                foreach ($files as $file) {
                    if ($file->isValid()) {
                        $result = $file->storeOnCloudinary('reports');
                        $imagePublicId = $result->getPublicId();
                        $imageUrl = "{$result->getSecurePath()}?public_id={$imagePublicId}";

                        $postImage = ReportImage::create([
                            'report_id' => $report->id,
                            'url' => $imageUrl,
                        ]);
                        $imagesUploaded[] = $postImage;
                    }
                }
                $report['images'] = $imagesUploaded;
            }

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

            if ($report->status === 'approved' || $report->status === 'rejected') {
                return responseJson(null, 400, 'Báo cáo đã được phê duyệt hoặc từ chối, không thể chấp nhận');
            }

            $report->status = 'approved';
            $report->save();

            if ($report->type === 'user') {
                $userToBan = User::find($report->target_id);
                if ($userToBan) {
                    $userToBan->is_banned = true;
                    $userToBan->save();
                }
            } elseif ($report->type === 'post') {
                $postToDelete = Post::find($report->target_id);
                if ($postToDelete) {
                    $postToDelete->delete();
                }
            }

            return responseJson($report, 200, 'Báo cáo đã được phê duyệt thành công');
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

            if ($report->status === 'rejected' || $report->status === 'approved') {
                return responseJson(null, 400, 'Báo cáo đã được phê duyệt hoặc từ chối, không thể từ chối lại');
            }

            $report->status = 'rejected';
            $report->save();

            return responseJson($report, 200, 'Báo cáo đã được từ chối thành công');
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
