<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\PostImage;
use Illuminate\Support\Facades\Validator;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;


class PostController extends Controller
{
    public function index()
    {
        $posts = Post::all();
        return responseJson($posts);
    }

    public function store(Request $request)
{
    try {
        $validator = Validator::make($request->all(), [
            'content' => 'nullable|string|max:300',
            'privacy' => 'required|in:PUBLIC,PRIVATE', 
            'post_type' => 'required|in:AVATAR_CHANGE,COVER_CHANGE,STATUS,SHARE', 
            'background' => 'required|in:color,image', 
            'images.*' => 'nullable|file|image|mimes:jpeg,png,jpg|max:2048', 
        ], [
            'content.max' => 'Nội dung bài đăng không được vượt quá 300 ký tự.',
            'background.required' => 'Nền bài đăng là bắt buộc.',
            'background.in' => 'Nền bài đăng không hợp lệ.',
            'images.*.image' => 'File phải là hình ảnh.',
            'images.*.mimes' => 'Hình ảnh phải có định dạng jpeg, png, jpg.',
            'images.*.max' => 'Dung lượng tối đa cho mỗi hình ảnh là 2MB.',
        ]);

        if ($validator->fails()) {
            return responseJson(null, 400, $validator->errors());
        }

        $post = Post::create([
            'owner_id' => auth()->id(), 
            'content' => $request->content,
            'privacy' => $request->privacy,
            'post_type' => $request->post_type,
            'background' => $request->background,
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->images as $image) {
                $result = Cloudinary::upload($image->getRealPath(), [
                    'folder' => 'postImages', 
                ]);

                $imagePublicId = $result->getPublicId();
                $imageUrl = "{$result->getSecurePath()}?public_id={$imagePublicId}";

                PostImage::create([
                    'post_id' => $post->id,
                    'url' => $imageUrl,
                ]);
            }
        }

        return responseJson(($post), 201, 'Bài đăng đã được tạo thành công');
    } catch (\Illuminate\Database\QueryException $e) {
        return responseJson(null, 500, 'Đã xảy ra lỗi khi tạo bài đăng: ' . $e->getMessage());
    }
}

    public function show($id)
    {
        $post = Post::find($id);
        if (!$post) {
            return responseJson(null, 404, 'Bài đăng không tồn tại');
        }

        return responseJson($post);
    }

    public function update(Request $request, $id)
    {
        try {
            $post = Post::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'content' => 'required|string|max:300', 
                'privacy' => 'required|in:PUBLIC,PRIVATE', 
                'post_type' => 'required|in:AVATAR_CHANGE,COVER_CHANGE,STATUS,SHARE', 
                'background' => 'required|in:color,image', 
            ]);

            if ($validator->fails()) {
                return responseJson(null, 400, $validator->errors());
            }

            $post->update($request->only('content', 'privacy', 'post_type', 'background'));

            return responseJson($post, 200, 'Bài đăng đã được cập nhật');
        } catch (\Exception $e) {
            return responseJson(null, 500, 'Đã xảy ra lỗi khi cập nhật bài đăng: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $post = Post::find($id);
        if (!$post) {
            return responseJson(null, 404, 'Bài đăng không tồn tại');
        }

        $post->delete();
        return responseJson(null, 200, 'Bài đăng đã được xóa');
    }
}

?>