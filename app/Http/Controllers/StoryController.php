<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Story;
use App\Models\StoryView;
use Carbon\Carbon;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class StoryController extends Controller
{
    public function create(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return responseJson(null, 401, 'Chưa xác thực người dùng');
            };

            $validator = Validator::make($request->all(), [
                'type' => 'required|in:text,image',
                'privacy' => 'required|in:PUBLIC,PRIVATE',
                'content' => 'required_if:type,text|string|max:255',
                'image' => 'required_if:type,image|image|mimes:jpeg,png,jpg,gif|max:2048',
                'background_id' => 'nullable|exists:backgrounds,id',
            ], [
                'type.required' => 'Loại tin không được để trống.',
                'type.in' => 'Loại tin không hợp lệ.',
                'privacy.required' => 'Chế độ riêng tư không được để trống.',
                'privacy.in' => 'Chế độ riêng tư không hợp lệ.',
                'content.required_if' => 'Nội dung không được để trống cho loại tin văn bản.',
                'content.string' => 'Nội dung phải là chuỗi ký tự.',
                'content.max' => 'Nội dung không được vượt quá :max ký tự.',
                'image.required_if' => 'Ảnh không được để trống cho loại tin ảnh.',
                'image.image' => 'Ảnh phải là một file hình ảnh.',
                'image.mimes' => 'Ảnh phải có định dạng: jpeg, png, jpg, gif.',
                'image.max' => 'Ảnh không được vượt quá :max kilobytes.',
                'background_id.exists' => 'Phông nền không hợp lệ.',
            ]);

            if ($validator->fails()) {
                return responseJson(null, 400, $validator->errors());
            }

            $type = $request->input('type');
            $privacy = $request->input('privacy');
            $content = $request->input('content');
            $backgroundId = $request->input('background_id');

            $storyData = [
                'user_id' => $user->id,
                'type' => $type,
                'privacy' => $privacy,
                'content_text' => $content,
                'background_id' => $backgroundId,
                'expires_at' => Carbon::now()->addHours(24),
            ];

            if ($type == 'image' && $request->hasFile('image')) {
                $image = $request->file('stories');
                $result = Cloudinary::upload($image->getRealPath(), [
                    'folder' => 'stories', 
                ]);
                $imagePublicId = $result->getPublicId();
                $storyData['content_url'] = "{$result->getSecurePath()}?public_id={$imagePublicId}";
            }

            $story = Story::create($storyData);

            return responseJson($story, 201, 'Tin đã được tạo thành công.');
        } catch (\Exception $e) {
            return responseJson(null, 500, 'Đã xảy ra lỗi khi tạo tin: ' . $e->getMessage());
        }
    }

    public function index()
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return responseJson(null, 401, 'Chưa xác thực người dùng');
            };

            $myStories = Story::where('expires_at', '>', Carbon::now())
                            ->where('user_id', $user->id)
                            ->with('user')
                            ->orderBy('created_at', 'desc');

            $friendStories = Story::where('expires_at', '>', Carbon::now())
                                ->where('privacy', 'PUBLIC')
                                ->whereIn('user_id', function ($query) use ($user) {
                                    $query->select('friendships.friend_id')
                                            ->from('friendships')
                                            ->where('friendships.owner_id', $user->id)
                                            ->where('friendships.status', 'accepted');
                                })
                                ->with('user')
                                ->orderBy('created_at', 'desc');

            $stories = $myStories->union($friendStories)->get();

            $sortedStories = $stories->sortByDesc(function ($story) use ($user) {
                return $story->user_id == $user->id ? 0 : 1;
            });

            return responseJson($sortedStories->values()->all(), 200, 'Danh sách tin thành công.');
        } catch (\Exception $e) {
            return responseJson(null, 500, 'Đã xảy ra lỗi khi lấy danh sách tin: ' . $e->getMessage());
        }
    }



    public function show($id)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return responseJson(null, 401, 'Chưa xác thực người dùng');
            };
            $story = Story::findOrFail($id);

            $existingView = StoryView::where('story_id', $story->id)
                                     ->where('user_id', $user->id)
                                     ->first();

            if (!$existingView) {
                StoryView::create([
                    'story_id' => $story->id,
                    'user_id' => $user->id,
                ]);
            }

            $viewCount = StoryView::where('story_id', $story->id)->count();

            $viewers = StoryView::where('story_id', $story->id)
                                ->with('user')
                                ->get();

            return responseJson([
                'story' => $story,
                'view_count' => $viewCount,
                'viewers' => $viewers,
            ], 200, 'Dữ liệu tin được truy vấn thành công.');
        } catch (\Exception $e) {
            return responseJson(null, 500, 'Đã xảy ra lỗi khi lấy dữ liệu tin: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return responseJson(null, 401, 'Chưa xác thực người dùng');
            };
            $story = Story::where('id', $id)->where('user_id', $user->id)->firstOrFail();

            $story->delete();

            return responseJson(null, 200, 'Tin đã được xóa thành công.');
        } catch (\Exception $e) {
            return responseJson(null, 500, 'Đã xảy ra lỗi khi xóa tin: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return responseJson(null, 401, 'Chưa xác thực người dùng');
            };

            $validator = Validator::make($request->all(), [
                'privacy' => 'required|in:PUBLIC,PRIVATE',
            ], [
                'privacy.required' => 'Đối tượng xem của tin phải được chọn.',
                'privacy.in' => 'Đối tượng xem của tin không hợp lệ.',
            ]);

            if ($validator->fails()) {
                return responseJson(null, 400, $validator->errors());
            }

            $story = Story::where('id', $id)->where('user_id', $user->id)->firstOrFail();
            $story->privacy = $request->input('privacy');
            $story->save();

            return responseJson($story, 200, 'Đối tượng xem của tin đã được cập nhật thành công.');
        } catch (\Exception $e) {
            return responseJson(null, 500, 'Đã xảy ra lỗi khi cập nhật đối tượng xem của tin: ' . $e->getMessage());
        }
    }
}
