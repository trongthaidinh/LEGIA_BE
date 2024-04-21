<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;

class PostController extends Controller
{
    // Hiển thị danh sách tất cả các bài đăng
    public function index()
    {
        $posts = Post::all();
        return responseJson(['posts' => $posts]);
    }

    public function store(Request $request)
    {
        $validator = $request->validate([
            'content' => 'required|string|max:300',
            'privacy' => 'required|in:PUBLIC,PRIVATE', 
            'post_type' => 'required|in:AVATAR_CHANGE,COVER_CHANGE,STATUS,SHARE', 
            'background' => 'required|in:color,image', 
        ], [
            'content.max' => 'Nội dung bài đăng không được vượt quá 300 ký tự.',
            'background.required' => 'Nền bài đăng là bắt buộc.',
            'background.in' => 'Nền bài đăng không hợp lệ.',
        ]);

        $post = Post::create([
            'owner' => auth()->id(), 
            'content' => $request->content,
            'privacy' => $request->privacy,
            'post_type' => $request->post_type,
            'background' => $request->background,
        ]);

        return responseJson(['post' => $post], 201, 'Bài đăng đã được tạo thành công');
    }

    public function show($id)
    {
        $post = Post::find($id);
        if (!$post) {
            return responseJson(null, 404, 'Bài đăng không tồn tại');
        }

        return responseJson(['post' => $post]);
    }

    public function update(Request $request, $id)
    {
        $post = Post::findOrFail($id);

        $validator = $request->validate([
            'content' => 'required|string|max:300', 
            'privacy' => 'required|in:PUBLIC,PRIVATE', 
            'post_type' => 'required|in:AVATAR_CHANGE,COVER_CHANGE,STATUS,SHARE', 
            'background' => 'required|in:color,image', 
        ]);

        $post->update($validator);

        return responseJson(['post' => $post], 200, 'Bài đăng đã được cập nhật');
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
