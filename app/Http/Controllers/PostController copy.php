<?php

namespace App\Http\Controllers;

use App\Models\Archive;
use App\Models\Background;
use App\Models\Comment;
use App\Models\Friendship;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\PostImage;
use App\Models\PostVideos;
use App\Models\Reaction;
use App\Models\Share;
use App\Models\User;
use App\Share\Pushers\NotificationAdded;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PostController extends Controller
{
    private $NotificationAdded;

    public function __construct() {
        $this->NotificationAdded = new NotificationAdded();
    }

    public function index(Request $request)
    {
        try {
            $user = auth()->userOrFail();
            if (!$user) {
                return responseJson(null, 401, 'Chưa xác thực người dùng');
            }
            $userId = $user->id;

            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);

            $friendIds = [];

            $friendships = Friendship::where(function ($query) use ($userId) {
                $query->where('friend_id', $userId)
                      ->orWhere('owner_id', $userId);
            })
            ->where('status', 'accepted')
            ->with(['friend' => function ($query) use ($userId) {
                $query->where('id', '!=', $userId);
            }])
            ->with(['owner' => function ($query) use ($userId) {
                $query->where('id', '!=', $userId);
            }])
            ->get();

            $friendIds = $friendships->pluck('owner_id')->toArray();

            $posts = Post::with([
                    'owner:id,first_name,last_name,avatar,gender,is_verified',
                    'background',
                    'images',
                    'videos'
                ])
                ->withCount(['comments', 'reactions', 'shares'])
                ->where(function($query) use ($user, $friendIds) {
                    $query->where('privacy', 'PUBLIC')
                          ->orWhere(function ($query) use ($user, $friendIds) {
                              $query->where('privacy', 'FRIEND')
                                    ->whereIn('owner_id', $friendIds);
                          });
                })
                ->whereHas('owner', function ($query) {
                    $query->where('is_banned', false);
                })
                ->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page)
                ->through(function ($post) use ($user) {
                    $currentUserReaction = $post->reactions()->where('owner_id', $user->id)->select('type')->first();
                    $post->current_user_reaction = $currentUserReaction ? $currentUserReaction->type : null;

                    $reactionCounts = $post->reactions()
                        ->select('type', DB::raw('COUNT(*) as count'))
                        ->groupBy('type')
                        ->orderByDesc('count')
                        ->limit(3)
                        ->pluck('count', 'type')
                        ->toArray();

                    $topReactions = [];
                    foreach ($reactionCounts as $type => $count) {
                        $topReactions[] = [
                            'type' => $type,
                            'count' => $count,
                        ];
                    }
                    $post->top_reactions = $topReactions;

                    if ($post->post_type === 'SHARE') {
                        $share = Share::where('owner_post_id', $post->id)->first();
                        if ($share) {
                            $originalPost = Post::with(['owner:id,first_name,last_name,avatar,gender'])
                                ->with(['images', 'videos'])
                                ->find($share->post_id);

                            $reactionCounts = $originalPost->reactions()
                                ->select('type', DB::raw('COUNT(*) as count'))
                                ->groupBy('type')
                                ->orderByDesc('count')
                                ->limit(3)
                                ->pluck('count', 'type')
                                ->toArray();

                            $topReactions = [];
                            foreach ($reactionCounts as $type => $count) {
                                $topReactions[] = [
                                    'type' => $type,
                                    'count' => $count,
                                ];
                            }
                            $originalPost->top_reactions = $topReactions;

                            $currentUserReaction = $originalPost->reactions()->where('owner_id', $user->id)->select('type')->first();
                            $originalPost->current_user_reaction = $currentUserReaction ? $currentUserReaction->type : null;

                            if ($originalPost) {
                                $post->original_post = $originalPost;
                            }
                        }
                    }

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

            return responseJson($response, 200, 'Danh sách các bài đăng công khai và bạn bè');
        } catch (\Exception $e) {
            return responseJson(null, 500, 'Đã xảy ra lỗi khi lấy danh sách các bài đăng: ' . $e->getMessage());
        }
    }







    public function getUserPosts($userId, Request $request)
    {
        try {
            $currentUser = auth()->userOrFail();
            $currentUserId = $currentUser->id;

            if (!$currentUser) {
                return responseJson(null, 401, 'Chưa xác thực người dùng');
            }

            $user = User::findOrFail($userId);

            if ($user->is_banned) {
                return responseJson(null, 404, 'Người dùng không tồn tại hoặc đã bị khóa.');
            }

            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);

            $friendIds = [];

            $friendships = Friendship::where(function ($query) use ($currentUserId) {
                $query->where('friend_id', $currentUserId)
                      ->orWhere('owner_id', $currentUserId);
            })
            ->where('status', 'accepted')
            ->with(['friend' => function ($query) use ($currentUserId) {
                $query->where('id', '!=', $currentUserId);
            }])
            ->with(['owner' => function ($query) use ($currentUserId) {
                $query->where('id', '!=', $currentUserId);
            }])
            ->get();

            $friendIds = $friendships->pluck('owner_id')->toArray();

            $query = Post::with(['owner:id,first_name,last_name,avatar,gender,is_verified', 'background', 'images', 'videos'])
                ->withCount(['comments', 'reactions', 'shares'])
                ->where('owner_id', $userId)
                ->where(function ($query) use ($currentUser, $friendIds, $userId) {
                    if ($currentUser->id == $userId) {
                        $query->where('privacy', 'PUBLIC')
                              ->orWhere('privacy', 'PRIVATE')
                              ->orWhere('privacy', 'FRIEND');
                    } else {
                        $query->where('privacy', 'PUBLIC')
                              ->orWhere(function ($query) use ($friendIds) {
                                  $query->where('privacy', 'FRIEND')
                                        ->whereIn('owner_id', $friendIds);
                              });
                    }
                })
                ->whereHas('owner', function ($query) {
                    $query->where('is_banned', false);
                })
                ->orderBy('created_at', 'desc');

            $totalPosts = $query->count();

            $posts = $query->paginate($perPage, ['*'], 'page', $page)
                ->through(function ($post) use ($currentUser, $user) {
                    $currentUserReaction = $post->reactions()->where('owner_id', $currentUser->id)->select('type')->first();
                    $post->current_user_reaction = $currentUserReaction ? $currentUserReaction->type : null;

                    $reactionCounts = $post->reactions()
                        ->select('type', DB::raw('COUNT(*) as count'))
                        ->groupBy('type')
                        ->orderByDesc('count')
                        ->limit(3)
                        ->pluck('count', 'type')
                        ->toArray();

                    $topReactions = [];
                    foreach ($reactionCounts as $type => $count) {
                        $topReactions[] = [
                            'type' => $type,
                            'count' => $count,
                        ];
                    }
                    $post->top_reactions = $topReactions;

                    if ($post->post_type === 'SHARE') {
                        $share = Share::where('owner_post_id', $post->id)->first();
                        if ($share) {
                            $originalPost = Post::with(['owner:id,first_name,last_name,avatar,gender'])
                            ->with(['images', 'videos'])
                            ->find($share->post_id);

                            $reactionCounts = $originalPost->reactions()
                            ->select('type', DB::raw('COUNT(*) as count'))
                            ->groupBy('type')
                            ->orderByDesc('count')
                            ->limit(3)
                            ->pluck('count', 'type')
                            ->toArray();

                            $topReactions = [];
                            foreach ($reactionCounts as $type => $count) {
                                $topReactions[] = [
                                    'type' => $type,
                                    'count' => $count,
                                ];
                            }
                            $originalPost->top_reactions = $topReactions;

                            $currentUserReaction = $originalPost->reactions()->where('owner_id', $user->id)->select('type')->first();
                            $originalPost->current_user_reaction = $currentUserReaction ? $currentUserReaction->type : null;

                            if ($originalPost) {
                                $post->original_post = $originalPost;
                            }
                        }
                    }

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
        } catch (\Exception $e) {
            return responseJson(null, 500, 'Đã xảy ra lỗi khi lấy danh sách bài đăng của người dùng: ' . $e->getMessage());
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
                'content' => 'nullable|string|max:5000',
                'privacy' => 'in:PUBLIC,PRIVATE,FRIEND',
                'post_type' => 'in:AVATAR_CHANGE,COVER_CHANGE,STATUS,SHARE',
                'images.*' => 'nullable|file|image|mimes:jpeg,png,jpg,webp|max:5120',
                'videos.*' => 'nullable|file|mimes:mp4,mov,avi|max:10240',
            ], [
                'content.string' => 'Nội dung bài viết phải là một chuỗi ký tự.',
                'content.max' => 'Nội dung bài viết không được vượt quá :max ký tự.',
                'privacy.in' => 'Quyền riêng tư không hợp lệ.',
                'post_type.in' => 'Loại bài viết không hợp lệ.',
                'images.*.file' => 'Tệp hình ảnh không hợp lệ.',
                'images.*.image' => 'Tệp phải là hình ảnh',
                'images.*.mimes' => 'Sai định dạng',
                'images.*.max' => 'Kích thước hình ảnh không được vượt quá 5MB',
                'videos.*.file' => 'Tệp video không hợp lệ.',
                'videos.*.mimes' => 'Sai định dạng video.',
                'videos.*.max' => 'Kích thước video không được vượt quá 10MB',
            ]);

            if ($validator->fails()) {
                return responseJson(null, 400, $validator->errors());
            }

            $postData = $validator->validated();

            $maxTotalSize = 15 * 1024 * 1024;
            $totalSize = 0;

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $totalSize += $file->getSize();
                }
            }

            if ($request->hasFile('videos')) {
                foreach ($request->file('videos') as $file) {
                    $totalSize += $file->getSize();
                }
            }

            if ($totalSize > $maxTotalSize) {
                return responseJson(null, 400, 'Tổng kích thước các tệp tin không được vượt quá 15MB.');
            }

            if ($request->background_id != null) {
                $background = Background::find($request->background_id);
                if (!$background) {
                    return responseJson(null, 404, 'Background không tồn tại');
                }
                $postData['background_id'] = $request->background_id;
            }

            if (($request->hasFile('images') || $request->hasFile('videos')) && !empty($postData['background_id'])) {
                return responseJson(null, 400, 'Bài viết có ảnh hoặc video không được có background.');
            }

            if (!$request->hasFile('images') && !$request->hasFile('videos')) {
                if (empty($postData['content']) && empty($postData['background_id'])) {
                    return responseJson(null, 400, 'Bài viết phải có nội dung hoặc nội dung và background khi không có ảnh hoặc video.');
                }
                if (!empty($postData['background_id']) && empty($postData['content'])) {
                    return responseJson(null, 400, 'Bài viết có background phải có nội dung.');
                }
            }

            $post = Post::create(array_merge(
                $postData,
                ['owner_id' => $user->id]
            ));

            $images = [];
            $videos = [];

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    if ($file->isValid()) {
                        $result = $file->storeOnCloudinary('post_images');
                        $imagePublicId = $result->getPublicId();
                        $imageUrl = "{$result->getSecurePath()}?public_id={$imagePublicId}";

                        $postImage = PostImage::create([
                            'user_id' => $user->id,
                            'post_id' => $post->id,
                            'url' => $imageUrl,
                        ]);

                        $images[] = $postImage;
                    }
                }
            }

            if ($request->hasFile('videos')) {
                foreach ($request->file('videos') as $file) {
                    if ($file->isValid()) {
                        $result = $file->storeOnCloudinary('post_videos');
                        $videoPublicId = $result->getPublicId();
                        $videoUrl = "{$result->getSecurePath()}?public_id={$videoPublicId}";

                        $postVideo = PostVideos::create([
                            'user_id' => $user->id,
                            'post_id' => $post->id,
                            'url' => $videoUrl,
                        ]);

                        $videos[] = $postVideo;
                    }
                }
            }

            $post->images = $images;
            $post->videos = $videos;

            return responseJson($post, 201, 'Bài đăng đã được tạo thành công');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
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

        $post = Post::with([
                'owner:id,first_name,last_name,avatar,gender,is_verified',
                'background',
                'images',
                'videos'
            ])
            ->withCount(['comments', 'reactions', 'shares'])
            ->where('id', $id)
            ->whereHas('owner', function ($query) {
                $query->where('is_banned', false);
            })
            ->first();

        if (!$post) {
            return responseJson(null, 404, 'Bài đăng không tồn tại');
        }

        $friendIds = Friendship::where(function ($query) use ($user) {
            $query->where('friend_id', $user->id)
                  ->orWhere('owner_id', $user->id);
        })
        ->where('status', 'accepted')
        ->pluck('owner_id', 'friend_id')->toArray();

        if ($post->privacy === 'PRIVATE' && $post->owner_id !== $user->id) {
            return responseJson(null, 403, 'Bạn không có quyền truy cập bài viết này');
        }

        if ($post->privacy === 'FRIEND' && !in_array($post->owner_id, $friendIds) && $post->owner_id !== $user->id) {
            return responseJson(null, 403, 'Bạn không có quyền truy cập bài viết này');
        }

        $reactionCounts = $post->reactions()
            ->select('type', DB::raw('COUNT(*) as count'))
            ->groupBy('type')
            ->orderByDesc('count')
            ->limit(3)
            ->pluck('count', 'type')
            ->toArray();

        $topReactions = [];
        foreach ($reactionCounts as $type => $count) {
            $topReactions[] = [
                'type' => $type,
                'count' => $count,
            ];
        }
        $post->top_reactions = $topReactions;

        $currentUserReaction = $post->reactions()->where('owner_id', $user->id)->select('type')->first();
        $post->current_user_reaction = $currentUserReaction ? $currentUserReaction->type : null;

        if ($post->post_type === 'SHARE') {
            $share = Share::where('owner_post_id', $post->id)->first();
            if ($share) {
                $originalPost = Post::with(['owner:id,first_name,last_name,avatar,gender', 'videos'])
                ->find($share->post_id);

                $reactionCounts = $originalPost->reactions()
                ->select('type', DB::raw('COUNT(*) as count'))
                ->groupBy('type')
                ->orderByDesc('count')
                ->limit(3)
                ->pluck('count', 'type')
                ->toArray();

                $topReactions = [];
                foreach ($reactionCounts as $type => $count) {
                    $topReactions[] = [
                        'type' => $type,
                        'count' => $count,
                    ];
                }
                $originalPost->top_reactions = $topReactions;

                $currentUserReaction = $originalPost->reactions()->where('owner_id', $user->id)->select('type')->first();
                $originalPost->current_user_reaction = $currentUserReaction ? $currentUserReaction->type : null;

                if ($originalPost) {
                    $post->original_post = $originalPost;
                }
            }
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

        $post = Post::findOrFail($id);
        if (!$post) {
            return responseJson(null, 404, 'Bài đăng không tồn tại');
        }

        if ($post->owner_id !== $user->id) {
            return responseJson(null, 403, 'Bạn không có quyền chỉnh sửa bài đăng này');
        }

        $validator = Validator::make($request->all(), [
            'content' => 'nullable|string|max:5000',
            'privacy' => 'in:PUBLIC,PRIVATE,FRIEND',
            'post_type' => 'in:AVATAR_CHANGE,COVER_CHANGE,STATUS,SHARE',
            'images.*' => 'nullable|file|image|mimes:jpeg,png,jpg,webp|max:5120',
            'videos.*' => 'nullable|file|mimes:mp4,mov,avi|max:10240',
        ], [
            'content.string' => 'Nội dung bài viết phải là một chuỗi ký tự.',
            'content.max' => 'Nội dung bài viết không được vượt quá :max ký tự.',
            'privacy.in' => 'Quyền riêng tư không hợp lệ.',
            'post_type.in' => 'Loại bài viết không hợp lệ.',
            'images.*.file' => 'Tệp hình ảnh không hợp lệ.',
            'images.*.image' => 'Tệp phải là hình ảnh.',
            'images.*.mimes' => 'Sai định dạng.',
            'images.*.max' => 'Kích thước hình ảnh không được vượt quá 5MB.',
            'videos.*.file' => 'Tệp video không hợp lệ.',
            'videos.*.mimes' => 'Sai định dạng video.',
            'videos.*.max' => 'Kích thước video không được vượt quá 10MB.',
        ]);

        if ($validator->fails()) {
            return responseJson(null, 400, $validator->errors());
        }

        $postData = $validator->validated();

        $maxTotalSize = 15 * 1024 * 1024;
            $totalSize = 0;

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $totalSize += $file->getSize();
            }
        }

        if ($request->hasFile('videos')) {
            foreach ($request->file('videos') as $file) {
                $totalSize += $file->getSize();
            }
        }

        if ($totalSize > $maxTotalSize) {
            return responseJson(null, 400, 'Tổng kích thước các tệp tin không được vượt quá 15MB.');
        }

        if ($request->background_id != null) {
            $background = Background::find($request->background_id);
            if (!$background) {
                return responseJson(null, 404, 'Background không tồn tại');
            }

            if ($post->images()->exists()) {
                return responseJson(null, 400, 'Bài đăng đã có ảnh không thể cập nhật nền.');
            }

            $postData['background_id'] = $request->background_id;
        }


        if (($request->hasFile('images') || $request->hasFile('videos')) && empty($postData['background_id'])) {
            PostImage::where('post_id', $post->id)->delete();
            PostVideos::where('post_id', $post->id)->delete();
        } elseif ($request->hasFile('images') || $request->hasFile('videos')) {
            return responseJson(null, 400, 'Bài đăng có ảnh hoặc video không được có background.');
        }

        if (!$request->hasFile('images') && !$request->hasFile('videos')) {
            if (empty($postData['content']) && empty($postData['background_id'])) {
                return responseJson(null, 400, 'Bài viết phải có nội dung hoặc nền khi không có ảnh hoặc video.');
            }
            if (!empty($postData['background_id']) && empty($postData['content'])) {
                return responseJson(null, 400, 'Bài viết có nền phải có nội dung.');
            }
        }

        $post->update($postData);

        $images = $post->images;
        $videos = $post->videos;

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                if ($file->isValid()) {
                    $result = $file->storeOnCloudinary('post_images');
                    $imagePublicId = $result->getPublicId();
                    $imageUrl = "{$result->getSecurePath()}?public_id={$imagePublicId}";

                    $postImage = PostImage::updateOrCreate(
                        ['post_id' => $post->id, 'url' => $imageUrl],
                        ['user_id' => $user->id, 'post_id' => $post->id, 'url' => $imageUrl]
                    );

                    $images[] = $postImage;
                }
            }
        }

        if ($request->hasFile('videos')) {
            foreach ($request->file('videos') as $file) {
                if ($file->isValid()) {
                    $result = $file->storeOnCloudinary('post_videos');
                    $videoPublicId = $result->getPublicId();
                    $videoUrl = "{$result->getSecurePath()}?public_id={$videoPublicId}";

                    $postVideo = PostVideos::updateOrCreate(
                        ['post_id' => $post->id, 'url' => $videoUrl],
                        ['user_id' => $user->id, 'post_id' => $post->id, 'url' => $videoUrl]
                    );

                    $videos[] = $postVideo;
                }
            }
        }

        $post->images = $images;
        $post->videos = $videos;

        return responseJson($post, 200, 'Bài đăng đã được cập nhật thành công');
    } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
        return responseJson(null, 404, 'Người dùng chưa xác thực!');
    }
}



    public function destroy($id)
{
    try {
        $user = auth()->userOrFail();

        $post = Post::findOrFail($id);

        if ($user->id !== $post->owner_id && $user->role !== 'admin') {
            return responseJson(null, 403, 'Bạn không có quyền xóa bài đăng này');
        }

        if ($post->images) {
            foreach ($post->images as $image) {
                $publicId = getPublicIdFromAvatarUrl($image->url);
                Cloudinary::destroy($publicId);

                $image->delete();
            }
        }

        $post->delete();
        return responseJson(null, 200, 'Bài đăng đã được xóa');
    } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
        return responseJson(null, 404, 'Người dùng chưa xác thực!');
    } catch (\Exception $e) {
        return responseJson(null, 500, 'Đã xảy ra lỗi: ' . $e->getMessage());
    }
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

public function removeAllFromArchive()
{
    try {
        $user = auth()->user();
        if (!$user) {
            return responseJson(null, 401, 'Chưa xác thực người dùng');
        }

        $deletedCount = Archive::where('user_id', $user->id)->delete();

        if ($deletedCount === 0) {
            return responseJson(null, 404, 'Không có bài đăng nào để xóa');
        }

        return responseJson(null, 200, 'Tất cả bài đăng đã lưu đã được xóa');
    } catch (\Exception $e) {
        return responseJson(null, 500, 'Đã xảy ra lỗi khi xóa tất cả bài đăng khỏi mục đã lưu: ' . $e->getMessage());
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
                                 $query->where('is_banned', false);
                             })
                             ->with(['post.owner:id,first_name,last_name,avatar,gender,is_verified','post.background', 'post.images'])
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
                $postOwner = $post->owner;

                $notification = Notification::where('owner_id', $postOwner->id)
                    ->where('emitter_id', $user->id)
                    ->where('type', 'post_like')
                    ->where('icon', $existingReaction->type)
                    ->first();

                if ($notification) {
                    $notification->delete();
                    $this->NotificationAdded->pusherNotificationDeleted($notification->id, $postOwner->id);
                }

                $existingReaction->update(['type' => $reactionType]);

                if (!$postOwner) {
                    return responseJson(null, 404, 'Chủ sở hữu bài viết không tồn tại');
                }

                if ($postOwner->id != $user->id) {
                    $notification = Notification::create([
                        'owner_id' => $postOwner->id,
                        'emitter_id' => $user->id,
                        'type' => 'post_like',
                        'content' => "reactionpost",
                        'read' => false,
                        'icon' => $reactionType,
                        'target_id' => $post->id
                    ]);

                    $notification->user = [
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'avatar' => $user->avatar
                    ];

                    $this->NotificationAdded->pusherNotificationAdded($notification, $postOwner->id);
                }

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
                    'content' => "reactionpost",
                    'read' => false,
                    'icon' => $reactionType,
                    'target_id' => $post->id
                ]);

                $notification->user = [
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'avatar' => $user->avatar
                ];

                $this->NotificationAdded->pusherNotificationAdded($notification, $postOwner->id);
            }

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

            $post = Post::find($postId);
            $postOwner = $post->owner;

            $reaction = Reaction::where('owner_id', $user->id)
                                ->where('post_id', $postId)
                                ->first();

            if ($reaction) {
                $notification = Notification::where('owner_id', $postOwner->id)
                            ->where('emitter_id', $user->id)
                            ->where('type', 'post_like')
                            ->where('icon', $reaction->type)
                            ->first();

                if ($notification) {
                    $notification->delete();
                    $this->NotificationAdded->pusherNotificationDeleted($notification->id, $postOwner->id);
                }

                $reaction->delete();
                return responseJson(null, 200, 'Bỏ trạng thái thả cảm xúc cho bài viết thành công');
            }

            return responseJson(null, 404, 'Không tìm thấy trạng thái thả cảm xúc');
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
            if (!$post) {
                return responseJson(null, 404, 'Bài viết không tồn tại');
            }

            $validator = Validator::make($request->all(), [
                'content' => 'required|string|max:300',
                'post_image_comment_id' => 'nullable|exists:post_images,id',
                'post_video_comment_id' => 'nullable|exists:post_videos,id',
            ], [
                'content.required' => 'Nội dung bình luận không được để trống.',
                'content.string' => 'Nội dung bài viết phải là một chuỗi ký tự.',
                'content.max' => 'Nội dung bài viết không được vượt quá :max ký tự.',
                'post_image_comment_id.exists' => 'Hình ảnh được chọn không tồn tại.',
                'post_video_comment_id.exists' => 'Video được chọn không tồn tại.',
            ]);

            if ($validator->fails()) {
                return responseJson(null, 400, $validator->errors());
            }

            $commentData = [
                'post_id' => $post->id,
                'owner_id' => $user->id,
                'content' => $request->content,
            ];

            if ($request->filled('post_image_comment_id')) {
                $commentData['post_image_comment_id'] = $request->post_image_comment_id;
            }

            if ($request->filled('post_video_comment_id')) {
                $commentData['post_video_comment_id'] = $request->post_video_comment_id;
            }

            $comment = Comment::create($commentData);

            $postOwner = $post->owner;

            if ($postOwner->id != $user->id) {
                $notification = Notification::create([
                    'owner_id' => $postOwner->id,
                    'emitter_id' => $user->id,
                    'type' => 'post_comment',
                    'content' => "commentpost",
                    'read' => false,
                    'icon' => "COMMENT",
                    'target_id' => $post->id
                ]);

                $notification->user = [
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'avatar' => $user->avatar
                ];

                $this->NotificationAdded->pusherNotificationAdded($notification, $postOwner->id);
            }

            return responseJson($comment, 201, 'Bình luận đã được tạo thành công');
        } catch (\Exception $e) {
            return responseJson(null, 500, 'Đã xảy ra lỗi khi tạo bình luận: ' . $e->getMessage());
        }
    }

    public function getPostComments($postId, Request $request)
{
    try {
        $user = auth()->user();
        if (!$user) {
            return responseJson(null, 401, 'Chưa xác thực người dùng');
        }

        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        $comments = Comment::where('post_id', $postId)
            ->whereNull('post_image_comment_id')
            ->whereNull('post_video_comment_id')
            ->with(['owner:id,first_name,last_name,avatar,gender,is_verified'])
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

public function getPostImageComments($postImageId, Request $request)
{
    try {
        $user = auth()->user();
        if (!$user) {
            return responseJson(null, 401, 'Chưa xác thực người dùng');
        }

        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        $comments = Comment::where('post_image_comment_id', $postImageId)
            ->with(['owner:id,first_name,last_name,avatar,gender,is_verified'])
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

        return responseJson($response, 200, 'Danh sách bình luận của hình ảnh');
    } catch (\Exception $e) {
        return responseJson(null, 500, 'Đã xảy ra lỗi khi lấy danh sách bình luận: ' . $e->getMessage());
    }
}

public function getPostVideoComments($postVideoId, Request $request)
{
    try {
        $user = auth()->user();
        if (!$user) {
            return responseJson(null, 401, 'Chưa xác thực người dùng');
        }

        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        $comments = Comment::where('post_video_comment_id', $postVideoId)
            ->with(['owner:id,first_name,last_name,avatar,gender,is_verified'])
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

        return responseJson($response, 200, 'Danh sách bình luận của video');
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
            ->whereNull('post_image_comment_id')
            ->whereNull('post_video_comment_id')
            ->with(['owner'])
            ->orderBy('created_at', 'asc')
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

    public function sharePost(Request $request, $postId)
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
                'owner_id' => $user->id,
                'content' => $request->content,
                'privacy' => $request->privacy,
                'post_type' => 'SHARE',
            ]);

            $share = Share::create([
                'owner_id' => $user->id,
                'post_id' => $postId,
                'owner_post_id' => $sharedPost->id,
            ]);

            $post = $share->post;
            $postOwner = $post->owner;

            if ($postOwner->id != $user->id) {
                $notification = Notification::create([
                    'owner_id' => $postOwner->id,
                    'emitter_id' => $user->id,
                    'type' => 'your_post_shared',
                    'content' => "sharepost",
                    'read' => false,
                    'icon' => 'SHARE',
                    'target_id' => $sharedPost->id
                ]);

                $notification->user = [
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'avatar' => $user->avatar
                ];
                $this->NotificationAdded->pusherNotificationAdded($notification, $postOwner->id);
            }


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

            $posts = Post::with(['owner:id,first_name,last_name,avatar,gender', 'background', 'images'])
                ->withCount(['comments', 'shares'])
                ->where('content', 'like', '%' . $query . '%')
                ->where('privacy', 'PUBLIC')
                ->whereHas('owner', function ($query) {
                    $query->where('is_banned', false);
                })
                ->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page)
                ->through(function ($post) use ($user) {
                    $currentUserReaction = $post->reactions()->where('owner_id', $user->id)->select('type')->first();
                    $post->current_user_reaction = $currentUserReaction ? $currentUserReaction->type : null;

                    $reactionCounts = $post->reactions()
                        ->select('type', DB::raw('COUNT(*) as count'))
                        ->groupBy('type')
                        ->orderByDesc('count')
                        ->limit(3)
                        ->pluck('count', 'type')
                        ->toArray();

                    $topReactions = [];
                    foreach ($reactionCounts as $type => $count) {
                        $topReactions[] = [
                            'type' => $type,
                            'count' => $count,
                        ];
                    }
                    $post->top_reactions = $topReactions;

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
                ->with('owner')
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

public function getReactionCounts($postId)
{
    try {
        $user = auth()->user();
        if (!$user) {
            return responseJson(null, 401, 'Chưa xác thực người dùng');
        }

        $post = Post::find($postId);
        if (!$post) {
            return responseJson(null, 404, 'Bài đăng không tồn tại hoặc đã bị xóa');
        }

        $reactionCounts = $post->reactions()
            ->select('type', DB::raw('COUNT(*) as count'))
            ->groupBy('type')
            ->pluck('count', 'type');

        return responseJson($reactionCounts, 200, 'Lấy số lượng phản ứng thành công');
    } catch (\Exception $e) {
        return responseJson(null, 500, 'Đã xảy ra lỗi khi lấy số lượng phản ứng: ' . $e->getMessage());
    }
}


    public function getReactionsByType($postId, $reactionType)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return responseJson(null, 401, 'Chưa xác thực người dùng');
            }

            $post = Post::find($postId);
            if (!$post) {
                return responseJson(null, 404, 'Bài đăng không tồn tại hoặc đã bị xóa');
            }

            $reactions = $post->reactions()
                ->where('type', $reactionType)
                ->with('owner')
                ->paginate(10);

            $reactionDetails = [
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

            return responseJson($reactionDetails, 200, 'Lấy thông tin reaction thành công');
        } catch (\Exception $e) {
            return responseJson(null, 500, 'Đã xảy ra lỗi khi lấy thông tin reaction: ' . $e->getMessage());
        }
    }

    public function getAllReactions($postId)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return responseJson(null, 401, 'Chưa xác thực người dùng');
            }

            $post = Post::find($postId);
            if (!$post) {
                return responseJson(null, 404, 'Bài đăng không tồn tại hoặc đã bị xóa');
            }

            if (!$post) {
                return responseJson(null, 401, 'Bài đăng không tồn tại hoặc đã bị xóa.');
            }

            $reactions = $post->reactions()
                ->with('owner')
                ->paginate(10);

            $reactionDetails = [
                'users' => $reactions->map(function ($reaction) {
                    return [
                        'id' => $reaction->owner->id,
                        'name' => $reaction->owner->last_name . ' ' . $reaction->owner->first_name,
                        'avatar' => $reaction->owner->avatar,
                        'reaction_type' => $reaction->type,
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

            return responseJson($reactionDetails, 200, 'Lấy tất cả thông tin reaction thành công');
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
