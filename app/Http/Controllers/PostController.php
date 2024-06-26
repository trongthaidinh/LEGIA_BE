<?php

namespace App\Http\Controllers;

use App\Events\CommentAdded;
use App\Events\PostShared;
use App\Events\ReactionAdded;
use App\Events\ShareAdded;
use App\Models\Archive;
use App\Models\Comment;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\PostImage;
use App\Models\Reaction;
use App\Models\Share;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class PostController extends Controller
{
    public function index()
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return responseJson(null, 401, 'Chưa xác thực người dùng');
            }

            $posts = Post::with('images')
                ->withCount(['comments', 'reactions', 'shares'])
                ->with(['owner','background'])
                ->where('privacy', 'PUBLIC')
                ->whereHas('owner', function ($query) {
                    $query->where('is_locked', false);
                })
                ->orderBy('created_at', 'desc')
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
            if(!$currentUser) {
                return responseJson(null, 401, 'Chưa xác thực người dùng');
            }
            $user = User::findOrFail($userId);


            if ($user->is_locked) {
                return responseJson(null, 404, 'Người dùng không tồn tại hoặc đã bị khóa.');
            }

            if ($currentUser->id == $userId) {
                $posts = Post::with('images')
                            ->withCount(['comments', 'reactions', 'shares'])
                            ->with('owner')
                            ->where('owner_id', $userId)
                            ->get();
            } else {
                $posts = Post::with('images')
                            ->withCount(['comments', 'reactions', 'shares'])
                            ->with('owner')
                            ->where('owner_id', $userId)
                            ->where('privacy', 'PUBLIC')
                            ->get();
            }

            if($posts->isEmpty()) {
                return responseJson(null, 404, 'Người dùng không tìm thấy bài đăng');
            }

            return responseJson($posts, 200, 'Danh sách bài đăng của người dùng');

        } catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, 'Người dùng chưa xác thực!');
        }
    }

    public function store(Request $request)
    {
        try {
            $user = auth()->userOrFail();

            $validator = Validator::make($request->all(), [
                'content' => 'nullable|string|max:300',
                'privacy' => 'required|in:PUBLIC,PRIVATE',
                'post_type' => 'required|in:AVATAR_CHANGE,COVER_CHANGE,STATUS,SHARE',
                'background_id' => 'nullable|exists:backgrounds,id',
                'images.*' => 'nullable|file|image|mimes:jpeg,png,jpg|max:2048',
            ], [
                'content.string' => 'Nội dung bài viết phải là một chuỗi ký tự.',
                'content.max' => 'Nội dung bài viết không được vượt quá :max ký tự.',
                'privacy.required' => 'Bạn phải chọn quyền riêng tư cho bài viết.',
                'privacy.in' => 'Quyền riêng tư không hợp lệ.',
                'post_type.required' => 'Bạn phải chọn loại bài viết.',
                'post_type.in' => 'Loại bài viết không hợp lệ.',
                'background_id.exists' => 'Background không tồn tại.',
                'images.*.file' => 'Tệp hình ảnh không hợp lệ.',
                'images.*.image' => 'Tệp phải là hình ảnh.',
                'images.*.mimes' => 'Hình ảnh phải có định dạng: jpeg, png, jpg.',
                'images.*.max' => 'Kích thước hình ảnh không được vượt quá 2MB.',
            ]);

            if ($validator->fails()) {
                return responseJson(null, 400, $validator->errors());
            }

            $postData = $validator->validated();

            if (!$request->hasFile('images')) {
                if (empty($postData['content']) && empty($postData['background_id'])) {
                    return responseJson(null, 400, 'Bài viết phải có nội dung hoặc nội dung và background khi không có ảnh.');
                }
                if (!empty($postData['background_id']) && empty($postData['content'])) {
                    return responseJson(null, 400, 'Bài viết có background phải có nội dung.');
                }
            } else {
                if (!empty($postData['background_id'])) {
                    return responseJson(null, 400, 'Bài viết có ảnh không được có background.');
                }
            }

            $post = Post::create(array_merge(
                $postData,
                ['owner_id' => $user->id]
            ));

            $images = [];

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    if ($file->isValid()) {
                        $result = $file->storeOnCloudinary('post_images');
                        $imagePublicId = $result->getPublicId();
                        $imageUrl = "{$result->getSecurePath()}?public_id={$imagePublicId}";

                        $postImage = PostImage::create([
                            'post_id' => $post->id,
                            'url' => $imageUrl,
                        ]);

                        $images[] = $postImage;
                    }
                }
            }

            $post->images = $images;

            return responseJson($post, 201, 'Bài đăng đã được tạo thành công');
        } catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, 'Người dùng chưa xác thực!');
        }
    }


public function show($id)
{
    try {
        $user = auth()->user();
        if(!$user) {
            return responseJson(null, 401, 'Chưa xác thực người dùng');
        }
        $post = Post::with('images')
                    ->withCount(['comments', 'reactions', 'shares'])
                    ->where('privacy', 'PUBLIC')
                    ->whereHas('owner', function ($query) {
                        $query->where('is_locked', false);
                    })
                    ->find($id);

        if (!$post) {
            return responseJson(null, 404, 'Bài đăng không tồn tại');
        }

        return responseJson($post, 200, 'Thông tin bài đăng');
    } catch (\Exception $e) {
        return responseJson(null, 500, 'Đã xảy ra lỗi khi lấy thông tin bài đăng: ' . $e->getMessage());
    }
}

    public function update(Request $request, $id)
    {
        try {
            $user = auth()->user();
            if (!$user) {
            return responseJson(null, 401, 'Chưa xác thực người dùng');
            }

            $post = Post::find($id);

            if (!$post) {
                return responseJson(null, 404, 'Bài đăng không tồn tại');
            }


            if ($post->owner_id !== $user->id) {
                return responseJson(null, 403, 'Bạn không có quyền chỉnh sửa bài đăng này');
            }

            $validator = Validator::make($request->all(), [
                'content' => 'nullable|string|max:300',
                'privacy' => 'required|in:PUBLIC,PRIVATE',
            ], [
                'content.string' => 'Nội dung bài viết phải là một chuỗi ký tự.',
                'content.max' => 'Nội dung bài viết không được vượt quá :max ký tự.',
                'privacy.required' => 'Bạn phải chọn quyền riêng tư cho bài viết.',
                'privacy.in' => 'Quyền riêng tư không hợp lệ.',
            ]);

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
    $user = auth()->user();
    if(!$user) {
        return responseJson(null, 401, 'Chưa xác thực người dùng');
    }
    $post = Post::find($id);

    if (!$post) {
        return responseJson(null, 404, 'Bài đăng không tồn tại');
    }

    if ($user->id !== $post->owner_id && $user->role !== 'admin') {
        return responseJson(null, 403, 'Bạn không có quyền xóa bài đăng này');
    }

    $post->delete();
    return responseJson(null, 200, 'Bài đăng đã được xóa');
}


    public function saveToArchive($postId)
{
    try {
        $user = auth()->user();
        if (!$user) {
            return responseJson(null, 401, 'Chưa xác thực người dùng');
        }
        $post = Post::find($postId);

        if (!$post) {
            return responseJson(null, 404, 'Bài đăng không tồn tại');
        }

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
        $user = auth()->user();
        if (!$user) {
            return responseJson(null, 401, 'Chưa xác thực người dùng');
        }
        $post = Post::find($postId);

        if (!$post) {
            return responseJson(null, 404, 'Bài đăng không tồn tại');
        }

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
        $user = auth()->user();
        if (!$user) {
            return responseJson(null, 401, 'Chưa xác thực người dùng');
        }

        $savedPosts = Archive::where('user_id', $user->id)
                             ->whereHas('post.owner', function($query) {
                                 $query->where('is_locked', false);
                             })
                             ->with('post')
                             ->get();

        return responseJson($savedPosts, 200, 'Danh sách các bài đăng đã lưu');
    } catch (\Exception $e) {
        return responseJson(null, 500, 'Đã xảy ra lỗi khi lấy danh sách các bài đăng đã lưu: ' . $e->getMessage());
    }
}


public function addOrUpdateReaction(Request $request, $postId)
{
    try {
        $user = auth()->user();
        if (!$user) {
            return responseJson(null, 401, 'Chưa xác thực người dùng');
        }

        $reactionType = $request->input('type');

        $existingReaction = Reaction::where('owner_id', $user->id)
                                    ->where('post_id', $postId)
                                    ->first();

        if ($existingReaction) {
            if ($existingReaction->type === $reactionType) {
                return responseJson($existingReaction, 200, 'Không có sự thay đổi trạng thái thả cảm xúc của bài đăng');
            } else {
                $existingReaction->update(['type' => $reactionType]);
                event(new ReactionAdded($existingReaction));
                return responseJson($existingReaction, 200, 'Cập nhật thành công trạng thái thả cảm xúc của bài đăng');
            }
        } else {
            $newReaction = Reaction::create([
                'owner_id' => $user->id,
                'post_id' => $postId,
                'type' => $reactionType
            ]);
            event(new ReactionAdded($newReaction));
            return responseJson($newReaction, 201, 'Thêm thành công trạng thái thả cảm xúc cho bài đăng');
        }
    } catch (\Exception $e) {
        return responseJson(null, 500, 'Đã xảy ra lỗi khi thả cảm xúc bài đăng: ' . $e->getMessage());
    }
}

public function removeReaction($postId)
{
    try {
        $user = auth()->user();
        if (!$user) {
            return responseJson(null, 401, 'Chưa xác thực người dùng');
        }

        $reaction = Reaction::where('owner_id', $user->id)
                            ->where('post_id', $postId)
                            ->first();

        if ($reaction) {
            $reaction->delete();
            return responseJson(null, 200, 'Bỏ trạng thái thả cảm xúc cho bài viết thành công');
        }

    } catch (\Exception $e) {
        return responseJson(null, 500, 'Đã xảy ra lỗi khi xóa thả cảm xúc bài đăng: ' . $e->getMessage());
    }
}


    public function storeComment(Request $request, $postId)
    {
        try {
            $user = auth()->user();
            if (!$user) {
            return responseJson(null, 401, 'Chưa xác thực người dùng');
            }
            $post = Post::find($postId);

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

            event(new CommentAdded($comment));

            return responseJson($comment, 201, 'Bình luận đã được tạo thành công');
        } catch (\Exception $e) {
            return responseJson(null, 500, 'Đã xảy ra lỗi khi tạo bình luận: ' . $e->getMessage());
        }
    }

    public function getComments($postId)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return responseJson(null, 401, 'Chưa xác thực người dùng');
            }

            $comments = Comment::where('post_id', $postId)
                        ->with(['owner:id,first_name,last_name,avatar'])
                        ->orderBy('created_at', 'desc')
                        ->paginate(10);

            return responseJson($comments, 200, 'Danh sách bình luận của bài viết');
        } catch (\Exception $e) {
            return responseJson(null, 500, 'Đã xảy ra lỗi khi lấy danh sách bình luận: ' . $e->getMessage());
        }
    }

    public function updateComment(Request $request, $postId, $commentId)
    {
        try {
            $user = auth()->user();
            if (!$user) {
            return responseJson(null, 401, 'Chưa xác thực người dùng');
            }
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
            $user = auth()->user();
            if (!$user) {
            return responseJson(null, 401, 'Chưa xác thực người dùng');
            }
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
            $user = auth()->user();
            if (!$user) {
            return responseJson(null, 401, 'Chưa xác thực người dùng');
            }

            $originalPost = Post::find($postId);

            if (!$originalPost) {
                return response()->json(['message' => 'Bài viết không tồn tại'], 404);
            }

            $sharedPost = Post::create([
                'owner_id' => $originalPost->owner_id,
                'content' => $originalPost->content,
                'privacy' => $originalPost->privacy,
                'post_type' => 'SHARE',
                'background_id' => $originalPost->background_id,
            ]);

            $share = Share::create([
                'owner_id' => $user->id,
                'post_id' => $postId,
            ]);

            event(new ShareAdded($share));

            return responseJson($sharedPost, 200, 'Bài viết đã được chia sẻ');
        } catch (\Exception $e) {
            return responseJson(null, 500, 'Đã xảy ra lỗi khi chia sẻ bài viết ' . $e->getMessage());
        }
    }

    public function searchPost(Request $request)
{
    try {
        $user = auth()->user();
        if (!$user) {
            return responseJson(null, 401, 'Chưa xác thực người dùng');
        }
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
        $perPage = $request->input('per_page', 4);
        $page = $request->input('page', 1);

        $posts = Post::with('images')
                    ->withCount(['comments', 'reactions', 'shares'])
                    ->where('content', 'like', '%' . $query . '%')
                    ->where('privacy', 'PUBLIC')
                    ->whereHas('owner', function ($query) {
                        $query->where('is_locked', false);
                    })
                    ->paginate($perPage, ['*'], 'page', $page)
                    ->appends(['q' => $query]);

        return responseJson($posts, 200, 'Kết quả tìm kiếm bài viết');
    } catch (\Exception $e) {
        return responseJson(null, 500, 'Đã xảy ra lỗi khi tìm kiếm bài viết: ' . $e->getMessage());
    }
}

    public function getUserReaction($postId)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return responseJson(null, 401, 'Chưa xác thực người dùng');
            }

            $reaction = Reaction::where('post_id', $postId)
                                ->where('owner_id', $user->id)
                                ->first('type');

            if(! $reaction){
                return responseJson(null, 200, 'Người dùng chưa phản ứng với bài viết này');
            }

            return responseJson($reaction, 200, 'Lấy phản ứng của người dùng thành công');
        } catch (\Exception $e) {
            return responseJson(null, 500, 'Đã xảy ra lỗi khi lấy phản ứng của người dùng: ' . $e->getMessage());
        }
    }


    public function getReactionsDetail($postId)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return responseJson(null, 401, 'Chưa xác thực người dùng');
            }

            $post = Post::findOrFail($postId);

            $reactions = $post->reactions()->with('owner:id,first_name,last_name,avatar')->get();

            $reactionCounts = $reactions->groupBy('type')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'users' => $group->map(function ($reaction) {
                        return [
                            'id' => $reaction->owner->id,
                            'name' => $reaction->owner->last_name . ' ' . $reaction->owner->first_name,
                            'avatar' => $reaction->owner->avatar
                        ];
                    })
                ];
            });

            return responseJson($reactionCounts, 200, 'Lấy thông tin reaction thành công');
        } catch (\Exception $e) {
            return responseJson(null, 500, 'Đã xảy ra lỗi khi lấy thông tin reaction: ' . $e->getMessage());
        }
    }

}

?>
