<?php

namespace App\Http\Controllers;

use App\Models\Archive;
use App\Models\Comment;
use App\Models\Like;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\PostImage;
use App\Models\Share;
use Illuminate\Support\Facades\Validator;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class PostController extends Controller
{
    public function index()
{
    try {
        $posts = Post::with(['images', 'comments', 'likes', 'shares'])
                    ->where('privacy', 'PUBLIC')
                    ->get();

        return responseJson($posts, 200, 'Danh sách các bài đăng công khai');
    } catch (\Exception $e) {
        return responseJson(null, 500, 'Đã xảy ra lỗi khi lấy danh sách các bài đăng: ' . $e->getMessage());
    }
}

    
public function getUserPosts($userId)
{
    try {
        $currentUser = auth()->userOrFail();
        
        if ($currentUser->id == $userId) {
            $posts = Post::with(['images', 'comments', 'likes', 'shares'])
                        ->where('owner_id', $userId)
                        ->get();
        } else {
            $posts = Post::with(['images', 'comments', 'likes', 'shares'])
                        ->where('owner_id', $userId)
                        ->where('privacy', 'PUBLIC')
                        ->get();
        }

        return responseJson($posts, 200, 'Danh sách bài đăng của người dùng');
    } catch (\Exception $e) {
        return responseJson(null, 500, 'Đã xảy ra lỗi khi lấy danh sách bài đăng của người dùng: ' . $e->getMessage());
    }
}


    public function store(Request $request)
{
    try {
        $validator = Validator::make($request->all(), [
            'content' => 'nullable|string|max:300',
            'privacy' => 'required|in:PUBLIC,PRIVATE', 
            'post_type' => 'required|in:AVATAR_CHANGE,COVER_CHANGE,STATUS,SHARE', 
            'background' => 'nullable|in:color,image',
            'images.*' => 'nullable|file|image|mimes:jpeg,png,jpg|max:2048', 
        ], [
            'content.required' => 'Nội dung bài viết không được để trống.',
            'content.string' => 'Nội dung bài viết phải là một chuỗi ký tự.',
            'content.max' => 'Nội dung bài viết không được vượt quá :max ký tự.',
            'privacy.required' => 'Bạn phải chọn quyền riêng tư cho bài viết.',
            'privacy.in' => 'Quyền riêng tư không hợp lệ.',
            'post_type.required' => 'Bạn phải chọn loại bài viết.',
            'post_type.in' => 'Loại bài viết không hợp lệ.',
            'background.in' => 'Nền bài viết không hợp lệ.',
            'images.*.file' => 'Tệp hình ảnh không hợp lệ.',
            'images.*.image' => 'Tệp phải là hình ảnh.',
            'images.*.mimes' => 'Hình ảnh phải có định dạng: jpeg, png, jpg.',
            'images.*.max' => 'Kích thước hình ảnh không được vượt quá 2MB.',
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
        $post = Post::with(['images', 'comments', 'likes', 'shares'])
                    ->where('privacy', 'PUBLIC')
                    ->find($id);
        
        if (!$post) {
            return responseJson(null, 404, 'Bài đăng không tồn tại');
        }

        return responseJson($post, 200, 'Thông tin bài đăng');
    }

    public function update(Request $request, $id)
    {
        try {
            $post = Post::find($id);
    
            if (!$post) {
                return responseJson(null, 404, 'Bài đăng không tồn tại');
            }
    
            $user = auth()->userOrFail();
    
            if ($post->owner_id !== $user->id) {
                return responseJson(null, 403, 'Bạn không có quyền chỉnh sửa bài đăng này');
            }
    
            $validator = Validator::make($request->all(), [
                'content' => 'nullable|string|max:300',
                'privacy' => 'required|in:PUBLIC,PRIVATE',
            ], postValidatorMessages());
    
            if ($validator->fails()) {
                return responseJson(null, 400, $validator->errors());
            }
    
            $post->update([
                'content' => $request->content,
                'privacy' => $request->privacy,
            ]);
    
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

    $user = auth()->userOrFail();

    if ($user->id !== $post->owner_id && !$user->hasRole('admin')) {
        return responseJson(null, 403, 'Bạn không có quyền xóa bài đăng này');
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

        return responseJson($like, 200,'Người dùng đã thích bài viết này.');
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
            ], [
                'content.required' => 'Nội dung bình luận không được để trống.',
                'content.string' => 'Nội dung bài viết phải là một chuỗi ký tự.',
                'content.max' => 'Nội dung bài viết không được vượt quá :max ký tự.'
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
            ], [
                'content.required' => 'Nội dung bình luận không được để trống.',
                'content.string' => 'Nội dung bài viết phải là một chuỗi ký tự.',
                'content.max' => 'Nội dung bài viết không được vượt quá :max ký tự.'
            ]);

            if ($validator->fails()) {
                return responseJson(null, 400, $validator->errors());
            }

            $comment = Comment::where('post_id', $postId)
                              ->findOrFail($commentId);

            if ($comment->owner_id != $user->id) {
                return responseJson(null, 403, 'Bạn không có quyền sửa bình luận này');
            }

            $comment->content = $request->content;
            $comment->save();

            return responseJson($comment, 200, 'Bình luận đã được sửa');
        } catch (\Exception $e) {
            return responseJson(null, 500, 'Đã xảy ra lỗi khi sửa bình luận: ' . $e->getMessage());
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

    public function sharePost($postId)
    {
        try {
            $user = auth()->userOrFail(); 
            $share = Share::create([
                'owner_id' => $user->id,
                'post_id' => $postId,
            ]);

            return responseJson($share, 200, 'Bài viết đã được chia sẻ');
        } catch (\Exception $e) {
            return responseJson(null, 500, 'Đã xảy ra lỗi khi chia sẻ bài viết ' . $e->getMessage());
        }
    }

    public function searchPost(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'q' => 'required|string|max:255',
                'page' => 'integer|min:1',
                'per_page' => 'integer|min:1|max:100',
            ], [
                'q.required' => 'Trường tìm kiếm không được để trống.',
                'q.string' => 'Trường tìm kiếm phải là một chuỗi ký tự.',
                'q.max' => 'Trường tìm kiếm không được vượt quá :max ký tự.',
                'page.integer' => 'Trường trang phải là một số nguyên.',
                'page.min' => 'Giá trị của trường trang phải lớn hơn hoặc bằng 1.',
                'per_page.integer' => 'Trường số lượng mỗi trang phải là một số nguyên.',
                'per_page.min' => 'Giá trị của trường số lượng mỗi trang phải lớn hơn hoặc bằng 1.',
                'per_page.max' => 'Giá trị của trường số lượng mỗi trang không được vượt quá :max.',
            ]);

            if ($validator->fails()) {
                return responseJson(null, 400, $validator->errors());
            }

            $query = $request->input('q');
            $perPage = $request->input('per_page', 2); 
            $page = $request->input('page', 1);

            $posts = Post::with(['images', 'comments', 'likes', 'shares'])
                        ->where('content', 'like', '%' . $query . '%')
                        ->where('privacy', 'PUBLIC')
                        ->paginate($perPage, ['*'], 'page', $page)
                        ->appends(['q' => $query]);

            return responseJson($posts, 200, 'Kết quả tìm kiếm bài viết');
        } catch (\Exception $e) {
            return responseJson(null, 500, 'Đã xảy ra lỗi khi tìm kiếm bài viết: ' . $e->getMessage());
        }
    }
}

?>