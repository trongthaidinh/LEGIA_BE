<?php

namespace App\Http\Controllers;

use App\Models\Archive;
use App\Models\Background;
use App\Models\Comment;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\PostImage;
use App\Models\Reaction;
use App\Models\Share;
use App\Models\User;
use App\Share\Pushers\NotificationAdded;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    private $NotificationAdded;

    public function __construct() {
        $this->NotificationAdded = new NotificationAdded();
    }

    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return responseJson(null, 401, 'Chưa xác thực người dùng');
            }

            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);
    
            $posts = Post::with(['background', 'images'])
                ->withCount(['comments', 'shares'])
                ->where('privacy', 'PUBLIC')
                ->whereHas('owner', function ($query) {
                    $query->where('is_locked', false);
                })
                ->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page)
                ->through(function ($post) use ($user) {
    
                    $reactionCounts = $post->reactions()
                        ->selectRaw('type, COUNT(*) as count')
                        ->groupBy('type')
                        ->orderByDesc('count')
                        ->limit(3)
                        ->pluck('type');
    
                    $totalReactions = $post->reactions()->count();
    
                    $post->top_reactions = [
                        'list' => $reactionCounts,
                        'total_count' => $totalReactions,
                    ];

                    $currentUserReaction = $post->reactions()->where('owner_id', $user->id)->first();
                    $post->current_user_reaction = $currentUserReaction ? $currentUserReaction->type : null;

                    $post->owner = $post->owner()->select('id', 'first_name', 'last_name', 'avatar', 'gender')->first();
    
                    return $post;
                });

            $response = [
                'posts' => $posts->items(),
                'page_info' => [
                    'total' => $posts->total(),
                    'total_page' => (int) ceil($posts->total() / $posts->perPage()),
                    'current_page' => $posts->currentPage(),
                    'next_page' => $posts->currentPage() < $posts->lastPage() ? $posts->currentPage() + 1 : null,
                    'per_page' => $posts->perPage(),
                ],
            ];
    
            return responseJson($response, 200, 'Danh sách các bài đăng công khai');
        } catch (\Exception $e) {
            return responseJson(null, 500, 'Đã xảy ra lỗi khi lấy danh sách các bài đăng: ' . $e->getMessage());
        }
    }

    public function getUserPosts($userId, Request $request)
    {
        try {
            $currentUser = auth()->userOrFail();
            if (!$currentUser) {
                return responseJson(null, 401, 'Chưa xác thực người dùng');
            }
    
            $user = User::findOrFail($userId);
    

            if ($user->is_locked) {
                return responseJson(null, 404, 'Người dùng không tồn tại hoặc đã bị khóa.');
            }
    
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);
    
            $query = Post::with(['background', 'images'])
                ->withCount(['comments', 'shares'])
                ->where('owner_id', $userId)
                ->whereHas('owner', function ($query) {
                    $query->where('is_locked', false);
                })
                ->orderBy('created_at', 'desc');
    
            $totalPosts = $query->count();
    
            $posts = $query->paginate($perPage, ['*'], 'page', $page)
                ->through(function ($post) use ($user) {
                    $reactionCounts = $post->reactions()
                        ->selectRaw('type, COUNT(*) as count')
                        ->groupBy('type')
                        ->orderByDesc('count')
                        ->limit(3)
                        ->pluck('type');
    
                    $totalReactions = $post->reactions()->count();
    
                    $post->top_reactions = [
                        'list' => $reactionCounts,
                        'total_count' => $totalReactions,
                    ];
    
                    $currentUserReaction = $post->reactions()->where('owner_id', $user->id)->first();
                    $post->current_user_reaction = $currentUserReaction ? $currentUserReaction->type : null;
    
                    $post->owner = $post->owner()->select('id', 'first_name', 'last_name', 'avatar', 'gender')->first();
    
                    return $post;
                });
    
            if ($posts->isEmpty()) {
                return responseJson(null, 404, 'Người dùng chưa có bài đăng nào!');
            }
    
            $response = [
                'posts' => $posts->items(),
                'page_info' => [
                    'total' => $totalPosts,
                    'total_page' => (int) ceil($totalPosts / $posts->perPage()),
                    'current_page' => $posts->currentPage(),
                    'next_page' => $posts->currentPage() < $posts->lastPage() ? $posts->currentPage() + 1 : null,
                    'per_page' => $posts->perPage(),
                ],
            ];
    
            return responseJson($response, 200, 'Danh sách bài đăng của người dùng');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return responseJson(null, 404, 'Người dùng chưa xác thực!');
        }
    }

    public function store(Request $request)
    {
        try {
            $user = auth()->user();
            if(!$user) {
                return responseJson(null, 401, 'Chưa xác thực người dùng');
            }

            $validator = Validator::make($request->all(), [
                'content' => 'nullable|string|max:300',
                'privacy' => 'required|in:PUBLIC,PRIVATE',
                'post_type' => 'required|in:AVATAR_CHANGE,COVER_CHANGE,STATUS,SHARE',
                'images.*' => 'nullable|file|image|mimes:jpeg,png,jpg|max:2048',
            ], [
                'content.string' => 'Nội dung bài viết phải là một chuỗi ký tự.',
                'content.max' => 'Nội dung bài viết không được vượt quá :max ký tự.',
                'privacy.in' => 'Quyền riêng tư không hợp lệ.',
                'post_type.in' => 'Loại bài viết không hợp lệ.',
                'images.*.file' => 'Tệp hình ảnh không hợp lệ.',
                'images.*.image' => 'Tệp phải là hình ảnh.',
                'images.*.mimes' => 'Hình ảnh phải có định dạng: jpeg, png, jpg.',
                'images.*.max' => 'Kích thước hình ảnh không được vượt quá 2MB.',
            ]);

            if ($validator->fails()) {
                return responseJson(null, 400, $validator->errors());
            }

            $postData = $validator->validated();

                if ($request->background_id != 'null') {
                    $background = Background::find($request->background_id);
                    if (!$background) {
                        return responseJson(null, 404, 'Background không tồn tại');
                    }
                    $postData['background_id'] = $request->background_id;
                }

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
            if (!$user) {
                return responseJson(null, 401, 'Chưa xác thực người dùng');
            }
    
            $post = Post::with(['background', 'images', 'owner:id,first_name,last_name,avatar,gender'])
                ->withCount(['comments', 'shares'])
                ->where('id', $id)
                ->where('privacy', 'PUBLIC')
                ->whereHas('owner', function ($query) {
                    $query->where('is_locked', false);
                })
                ->first();
    
            if (!$post) {
                return responseJson(null, 404, 'Bài đăng không tồn tại');
            }
    
            $reactionCounts = $post->reactions()
                ->selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->orderByDesc('count')
                ->limit(3)
                ->pluck('type');
    
            $totalReactions = $post->reactions()->count();
    
            $post->top_reactions = [
                'list' => $reactionCounts,
                'total_count' => $totalReactions,
            ];
    
            $currentUserReaction = $post->reactions()->where('owner_id', $user->id)->first();
            $post->current_user_reaction = $currentUserReaction ? $currentUserReaction->type : null;
    
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

        $post = Post::find($postId);
        if (!$post) {
            return responseJson(null, 404, 'Bài viết không tồn tại');
        }

        $existingReaction = Reaction::where('owner_id', $user->id)
                                    ->where('post_id', $postId)
                                    ->first();

        if ($existingReaction) {
            if ($existingReaction->type === $reactionType) {
                return responseJson($existingReaction, 200, 'Không có sự thay đổi trạng thái thả cảm xúc của bài đăng');
            } else {
                $existingReaction->update(['type' => $reactionType]);

                $postOwner = $post->owner;
                if (!$postOwner) {
                    return responseJson(null, 404, 'Chủ sở hữu bài viết không tồn tại');
                }

                if ($postOwner->id != $user->id) {
                    $notification = Notification::create([
                        'owner_id' => $postOwner->id,
                        'emitter_id' => $user->id,
                        'type' => 'post_like',
                        'content' => "đã bày tỏ cảm xúc bài viết của bạn.",
                        'read' => false,
                    ]);
    
                    $notification->user = [
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'avatar' => $user->avatar
                    ];
                }

                $this->NotificationAdded->pusherNotificationAdded($notification, $postOwner->id);

                return responseJson($existingReaction, 200, 'Cập nhật thành công trạng thái thả cảm xúc của bài đăng');
            }
        } else {
            $newReaction = Reaction::create([
                'owner_id' => $user->id,
                'post_id' => $postId,
                'type' => $reactionType
            ]);

            $postOwner = $post->owner;
            if (!$postOwner) {
                return responseJson(null, 404, 'Chủ sở hữu bài viết không tồn tại');
            }

                if ($postOwner->id != $user->id) {
                    $notification = Notification::create([
                        'owner_id' => $postOwner->id,
                        'emitter_id' => $user->id,
                        'type' => 'post_like',
                        'content' => "đã bày tỏ cảm xúc bài viết của bạn.",
                        'read' => false,
                    ]);

                    $notification->user = [
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'avatar' => $user->avatar
                    ];
                }
                $this->NotificationAdded->pusherNotificationAdded($notification, $postOwner->id);

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

            $post = $comment->post;
            $postOwner = $post->owner;

            if ($postOwner->id != $user->id) {
                $notification = Notification::create([
                    'owner_id' => $postOwner->id,
                    'emitter_id' => $user->id,
                    'type' => 'post_like',
                    'content' => "đã bình luận đến bài viết của bạn.",
                    'read' => false,
                ]);

                $notification->user = [
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'avatar' => $user->avatar
                ];
            }

            $this->NotificationAdded->pusherNotificationAdded($notification, $postOwner->id);

            return responseJson($comment, 201, 'Bình luận đã được tạo thành công');
        } catch (\Exception $e) {
            return responseJson(null, 500, 'Đã xảy ra lỗi khi tạo bình luận: ' . $e->getMessage());
        }
    }

    public function getAllComments($postId, Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return responseJson(null, 401, 'Chưa xác thực người dùng');
            }
    
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);
    
            $comments = Comment::where('post_id', $postId)
                ->with(['owner:id,first_name,last_name,avatar,gender'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);
    
            $response = [
                'comments' => $comments->items(),
                'page_info' => [
                    'total' => $comments->total(),
                    'total_page' => (int) ceil($comments->total() / $comments->perPage()),
                    'current_page' => $comments->currentPage(),
                    'next_page' => $comments->currentPage() < $comments->lastPage() ? $comments->currentPage() + 1 : null,
                    'per_page' => $comments->perPage(),
                ],
            ];
    
            return responseJson($response, 200, 'Danh sách bình luận của bài viết');
        } catch (\Exception $e) {
            return responseJson(null, 500, 'Đã xảy ra lỗi khi lấy danh sách bình luận: ' . $e->getMessage());
        }
    }
    

    public function getTopComments($postId)
{
    try {
        $user = auth()->user();
        if (!$user) {
            return responseJson(null, 401, 'Chưa xác thực người dùng');
        }

        $comments = Comment::where('post_id', $postId)
                    ->with(['owner:id,first_name,last_name,avatar'])
                    ->orderBy('created_at', 'desc')
                    ->take(3)
                    ->get();

        return responseJson($comments, 200, '3 bình luận mới nhất của bài viết');
    } catch (\Exception $e) {
        return responseJson(null, 500, 'Đã xảy ra lỗi khi lấy bình luận mới nhất: ' . $e->getMessage());
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

            $post = $share->post;
            $postOwner = $post->owner;

            if ($postOwner->id != $user->id) {
                $notification = Notification::create([
                    'owner_id' => $postOwner->id,
                    'emitter_id' => $user->id,
                    'type' => 'post_share',
                    'content' => "đã chia sẻ bài viết của bạn.",
                    'read' => false,
                ]);

                $notification->user = [
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'avatar' => $user->avatar
                ];
            }

            $this->NotificationAdded->pusherNotificationAdded($notification, $postOwner->id);

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
        $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);
    
            $posts = Post::with(['background', 'images'])
                ->withCount(['comments', 'shares'])
                ->where('content', 'like', '%' . $query . '%')
                ->where('privacy', 'PUBLIC')
                ->whereHas('owner', function ($query) {
                    $query->where('is_locked', false);
                })
                ->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page)
                ->through(function ($post) use ($user) {
    
                    $reactionCounts = $post->reactions()
                        ->selectRaw('type, COUNT(*) as count')
                        ->groupBy('type')
                        ->orderByDesc('count')
                        ->limit(3)
                        ->pluck('type');
    
                    $totalReactions = $post->reactions()->count();
    
                    $post->top_reactions = [
                        'list' => $reactionCounts,
                        'total_count' => $totalReactions,
                    ];

                    $currentUserReaction = $post->reactions()->where('owner_id', $user->id)->first();
                    $post->current_user_reaction = $currentUserReaction ? $currentUserReaction->type : null;

                    $post->owner = $post->owner()->select('id', 'first_name', 'last_name', 'avatar', 'gender')->first();
    
                    return $post;
                })
                ->appends(['q' => $query]);
    
            $response = [
                'posts' => $posts->items(),
                'page_info' => [
                    'total' => $posts->total(),
                    'total_page' => (int) ceil($posts->total() / $posts->perPage()),
                    'current_page' => $posts->currentPage(),
                    'next_page' => $posts->currentPage() < $posts->lastPage() ? $posts->currentPage() + 1 : null,
                    'per_page' => $posts->perPage(),
                ],
            ];

        return responseJson($response, 200, 'Kết quả tìm kiếm bài viết');
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

        $reactionTypes = $post->reactions()
            ->select('type')
            ->distinct()
            ->pluck('type');

        $reactionDetails = [];

        foreach ($reactionTypes as $type) {
            $reactions = $post->reactions()
                ->where('type', $type)
                ->with('owner:id,first_name,last_name,avatar')
                ->paginate(10);

            $reactionDetails[$type] = [
                'count' => $reactions->total(),
                'users' => $reactions->map(function ($reaction) {
                    return [
                        'id' => $reaction->owner->id,
                        'name' => $reaction->owner->last_name . ' ' . $reaction->owner->first_name,
                        'avatar' => $reaction->owner->avatar
                    ];
                }),
                'pagination' => [
                    'total' => $reactions->total(),
                    'per_page' => $reactions->perPage(),
                    'current_page' => $reactions->currentPage(),
                    'last_page' => $reactions->lastPage(),
                    'next_page' => $reactions->currentPage() < $reactions->lastPage() ? $reactions->currentPage() + 1 : null,
                ]
            ];
        }

        return responseJson($reactionDetails, 200, 'Lấy thông tin reaction thành công');
    } catch (\Exception $e) {
        return responseJson(null, 500, 'Đã xảy ra lỗi khi lấy thông tin reaction: ' . $e->getMessage());
    }
}


    public function getTopReactions($postId)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return responseJson(null, 401, 'Chưa xác thực người dùng');
            }

            $post = Post::findOrFail($postId);

            $reactions = $post->reactions()->get();

            $reactionCounts = $reactions->groupBy('type')->map(function ($group) {
                return $group->count();
            })->sortDesc()->take(3);

            return responseJson($reactionCounts, 200, 'Lấy thông tin 3 reaction có số lượng nhiều nhất thành công');
        } catch (\Exception $e) {
            return responseJson(null, 500, 'Đã xảy ra lỗi khi lấy thông tin reaction: ' . $e->getMessage());
        }
    }




}

?>
