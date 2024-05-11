<?php

namespace App\Http\Controllers;

use App\Models\Archive;
use App\Models\Comment;
use App\Models\Like;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\PostImage;
use Illuminate\Support\Facades\Validator;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::with('images')->get();
        return responseJson($posts);
    }

    public function store(Request $request)
{
    try {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:300',
            'privacy' => 'nullable|in:PUBLIC,PRIVATE', 
            'post_type' => 'nullable|in:AVATAR_CHANGE,COVER_CHANGE,STATUS,SHARE', 
            'background' => 'nullable|in:color,image',
            'images.*' => 'nullable|file|image|mimes:jpeg,png,jpg|max:2048', 
        ], [
            'content.max' => 'Nội dung bài đăng không được vượt quá 300 ký tự.',
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
            foreach ($request->file('images') as $image) {
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

        return responseJson($post, 201, 'Bài đăng đã được tạo thành công');
    } catch (\Exception $e) {
        return responseJson(null, 500, 'Đã xảy ra lỗi khi tạo bài đăng: ' . $e->getMessage());
    }
}


    public function show($id)
    {
        $post = Post::with('images')->find($id);
        
        if (!$post) {
            return responseJson(null, 404, 'Bài đăng không tồn tại');
        }

        return responseJson($post, 200, 'Thông tin bài đăng và các hình ảnh của nó');
    }

public function update(Request $request, $id)
{
    try {
        $post = Post::with('images')->withTrashed()->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:300', 
            'privacy' => 'required|in:PUBLIC,PRIVATE', 
            'post_type' => 'required|in:AVATAR_CHANGE,COVER_CHANGE,STATUS,SHARE', 
            'background' => 'required|in:color,image', 
        ]);

        if ($validator->fails()) {
            return responseJson(null, 400, $validator->errors());
        }

        if ($request->privacy == 'PRIVATE' && $post->privacy == 'PUBLIC') {
            $post->delete();
            $post->update($request->only('content', 'privacy', 'post_type', 'background'));
            return responseJson($post, 200, 'Bài đăng đã được chuyển thành trạng thái chỉ mình tôi');
        } 
        else if ($request->privacy == 'PRIVATE' && $post->privacy == 'PRIVATE') 
        {
            $post->update($request->only('content', 'privacy', 'post_type', 'background'));
            return responseJson($post, 200, 'Bài đăng đã được cập nhật và vẫn ở trạng thái chỉ mình tôi');
        } 
        else 
        {
            if ($post->trashed()) {
                $post->restore();
                $post->update($request->only('content', 'privacy', 'post_type', 'background'));
                return responseJson($post, 200, 'Bài đăng đã được chuyển thành trạng thái mọi người');
            } else {
                $post->update($request->only('content', 'privacy', 'post_type', 'background'));
                return responseJson($post, 200, 'Bài đăng đã được cập nhật');
            }
        }
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

    public function saveToArchive($postId)
{
    try {
        $user = auth()->userOrFail();
        $post = Post::findOrFail($postId);

        $existingArchive = Archive::where('user_id', $user->id)
                                   ->where('post_id', $post->id)
                                   ->first();

        if ($existingArchive) {
            return responseJson($existingArchive, 200, 'Bài đăng đã được lưu trước đó');
        }

        $archive = Archive::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        return responseJson($archive, 200, 'Bài đăng đã được lưu vào mục bài viết đã lưu');
    } catch (\Exception $e) {
        return responseJson(null, 500, 'Đã xảy ra lỗi khi lưu bài đăng vào mục đã lưu: ' . $e->getMessage());
    }
}

public function removeFromArchive($postId)
{
    try {
        $user = auth()->userOrFail();
        $post = Post::findOrFail($postId);

        $archive = Archive::where('user_id', $user->id)
                          ->where('post_id', $post->id)
                          ->first();

        if (!$archive) {
            return responseJson(null, 404, 'Bài đăng không tồn tại trong mục đã lưu');
        }

        $archive->delete();

        return responseJson(null, 200, 'Bài đăng đã được xóa khỏi mục bài viết đã lưu');
    } catch (\Exception $e) {
        return responseJson(null, 500, 'Đã xảy ra lỗi khi xóa bài đăng khỏi mục đã lưu: ' . $e->getMessage());
    }
}

public function getArchivedPosts()
    {
        try {
            $user = auth()->userOrFail();
            
            $savedPosts = Archive::where('user_id', $user->id)
                                 ->with('post')
                                 ->get();

            return responseJson($savedPosts, 200, 'Danh sách các bài đăng đã lưu');
        } catch (\Exception $e) {
            return responseJson(null, 500, 'Đã xảy ra lỗi khi lấy danh sách các bài đăng đã lưu: ' . $e->getMessage());
        }
    }

    public function likePost(Request $request, $postId)
{
    try {
        $user = auth()->userOrFail();
        $post = Post::findOrFail($postId);
        
        $existingLike = Like::where('post_id', $post->id)
                            ->where('owner_id', $user->id)
                            ->first();

        if ($existingLike) {
            $existingLike->delete();
            return responseJson(null, 200, 'Người dùng đã bỏ thích bài viết này.');
        }

        $like = Like::create([
            'post_id' => $post->id,
            'owner_id' => 1
        ]);

        return responseJson($post, 200,'Người dùng đã thích bài viết này.');
    } catch (\Exception $e) {
        return responseJson($post, 500, 'Đã xảy ra lỗi khi thích/bỏ thích bài viết: ' . $e->getMessage());
    }
}


    public function storeComment(Request $request, $postId)
    {
        try {
            $user = auth()->userOrFail(); 
            $post = Post::findOrFail($postId);          

            $validator = Validator::make($request->all(), [
                'content' => 'required|string|max:300',
            ]);

            if ($validator->fails()) {
                return responseJson(null, 400, $validator->errors());
            }

            $comment = Comment::create([
                'post_id' => $post->id,
                'owner_id' => $user->id,
                'content' => $request->content,
            ]);

            return responseJson($comment, 201, 'Bình luận đã được tạo thành công');
        } catch (\Exception $e) {
            return responseJson(null, 500, 'Đã xảy ra lỗi khi tạo bình luận: ' . $e->getMessage());
        }
    }

    public function getComments($postId)
{
    try {
        $comments = Comment::where('post_id', $postId)->get();

        return responseJson($comments, 200, 'Danh sách bình luận của bài viết');
    } catch (\Exception $e) {
        return responseJson(null, 500, 'Đã xảy ra lỗi khi lấy danh sách bình luận: ' . $e->getMessage());
    }
}

    public function updateComment(Request $request, $postId, $commentId)
    {
        try {
            $user = auth()->userOrFail(); 
            $validator = Validator::make($request->all(), [
                'content' => 'required|string|max:300',
            ]);

            if ($validator->fails()) {
                return responseJson(null, 400, $validator->errors());
            }

            $comment = Comment::where('post_id', $postId)
                              ->findOrFail($commentId);

            if ($comment->owner_id != $user->id) {
                return responseJson(null, 403, 'Bạn không có quyền cập nhật bình luận này');
            }

            $comment->content = $request->content;
            $comment->save();

            return responseJson($comment, 200, 'Bình luận đã được cập nhật thành công');
        } catch (\Exception $e) {
            return responseJson(null, 500, 'Đã xảy ra lỗi khi cập nhật bình luận: ' . $e->getMessage());
        }
    }

    public function deleteComment($postId, $commentId)
    {
        try {
            $user = auth()->userOrFail(); 
            $comment = Comment::where('post_id', $postId)
                              ->findOrFail($commentId);

            if ($comment->user_id != $user->id && $comment->post->owner_id != $user->id) {
                return responseJson(null, 403, 'Bạn không có quyền xóa bình luận này');
            }

            $comment->delete();

            return responseJson(null, 200, 'Bình luận đã được xóa thành công');
        } catch (\Exception $e) {
            return responseJson(null, 500, 'Đã xảy ra lỗi khi xóa bình luận: ' . $e->getMessage());
        }
    }
}

?>