<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Support\Facades\DB;
use App\Models\ConversationParticipant;
use App\Models\MessageImage;
use App\Models\MessagesDeletedBy;
use App\Models\MessagesSeenBy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

use MessageSent;

class ChatController extends Controller
{
    private $MessageSent;

    public function __construct(
    ) {
        $this->MessageSent = new MessageSent();
    }

    public function createConversation() {
        try{
            $user = auth()->userOrFail();

            $conversationData = request()->only(['name', 'type', 'targets_id']);

            $validatorConversation = Validator::make($conversationData, [
                'name' => 'nullable',
                'type' => 'nullable|in:individual,group',
                'targets_id' => 'required|array|min:1',
                'targets_id.*' => 'required|exists:users,id',
            ], chatValidatorMessages());

            if($validatorConversation->fails()){
                return responseJson(null, 400, $validatorConversation->errors());
            }

            $conversationType = $validatorConversation->getData()['type'] ?? null;
            $conversationName = $validatorConversation->getData()['name'] ?? null;


            if($conversationType == 'group' && !$conversationName ){
                return responseJson(null, 400, 'Vui lòng nhập tên nhóm chat!');
            }

            $conversationTargetsId = $validatorConversation->getData()['targets_id'];

            foreach ($conversationTargetsId as $targetId) {
                if($targetId == $user->id){
                    return responseJson(null, 400, 'Bạn không thể trò chuyện với chính mình!');
                }
            }


            if (!$conversationType || $conversationType == 'individual') {
                $target_id = $conversationTargetsId[0];

                if(!$target_id){
                    return responseJson(null, 400, 'Vui lý nhập thông tin người tham gia đoạn chat cá nhân!');
                }

                $existingConversationIds = DB::table('conversation_participants')
                    ->where('user_id', $target_id)
                    ->pluck('conversation_id')
                    ->toArray();

                $conversationExists = DB::table('conversations')
                    ->whereIn('id', $existingConversationIds)
                    ->where('type', 'individual')
                    ->whereExists(function ($query) use ($user) {
                        $query->select(DB::raw(1))
                            ->from('conversation_participants')
                            ->where('user_id', $user->id)
                            ->whereRaw('conversation_participants.conversation_id = conversations.id');
                    })
                    ->exists();

                if ($conversationExists) {
                    return responseJson(null, 400, 'Đoạn chat cá nhân với người này đã được tạo từ trước!');
                }
            };


            $secret_key = Str::uuid()->toString();

            $conversation = Conversation::create(array_merge(
                $validatorConversation->validated(),
                ['creator_id' => $user->id, 'secret_key' => $secret_key]
            ));

            $conversationParticipantsCreated = DB::table('conversation_participants')
            ->where('user_id', $user->id)
            ->where('conversation_id', $conversation->id)
            ->get();

            if(!$conversationParticipantsCreated->isEmpty()){
                return responseJson(null, 400, 'Cuộc đối thoại và những người tham gia đã được tạo từ trước!');
            }

            $participants = [];

            $myConversationParticipant = ConversationParticipant::create([
                'user_id' => $user->id,
                'conversation_id' => $conversation->id,
            ]);

            $participants[] = $myConversationParticipant;

            foreach ($conversationData['targets_id'] as $targetId) {
                $conversationParticipants = ConversationParticipant::create([
                    'user_id' => $targetId,
                    'conversation_id' => $conversation->id,
                ]);
                $participants[] = $conversationParticipants;
            }

            return responseJson([
                'conversation' => $conversation,
                'conversation_participants' => $participants
            ], 200, 'Cuộc đối thoại mới đã được tạo thành công!');


        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, "Người dùng chưa xác thực!");
        }
    }

    public function createMessage(Request $request)
{
    try {
        $user = auth()->userOrFail();
        $data = $request->only(['conversation_id', 'content', 'images']);

        $validator = Validator::make($data, [
            'conversation_id' => 'required|exists:conversations,id',
            'content' => 'nullable|string|max:400',
            'images.*' => 'nullable|file|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], chatValidatorMessages());

        if ($validator->fails()) {
            return responseJson(null, 400, $validator->errors());
        }

        $hasContent = !empty(trim($data['content'] ?? ''));
        $hasImages = $request->hasFile('images') && !empty($request->file('images'));

        if(!$hasContent && !$hasImages){
            return responseJson(null, 400, 'Phải có nội dung hoặc hình ảnh');
        }

        $conversationId = $data['conversation_id'];

        $message = Message::create(array_merge(
            $validator->validated(),
            ['user_id' => $user->id]
        ));

        $images = [];

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                if ($file->isValid()) {
                    $result = $file->storeOnCloudinary('message_images');
                    $imagePublicId = $result->getPublicId();
                    $imageUrl = "{$result->getSecurePath()}?public_id={$imagePublicId}";

                    $res = MessageImage::create([
                        'message_id' => $message->id,
                        'url' => $imageUrl,
                    ]);

                    $images[] = $res;
                }
            }
        }

        $conversation = Conversation::findOrFail($conversationId);

        $partners = ConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', '!=', $user->id)
            ->pluck('user_id');

        $message->load('user');
        $message->images = $images;

        $this->MessageSent->pusherMessageSent($conversation->secret_key, $message);

        foreach ($partners as $partnerId) {
            $this->MessageSent->pusherConversationIdGetNewMessage($partnerId, [
                'conversation_id' => $conversationId,
                'content' => $message->content
            ]);
        }

        $conversation->update(['last_message' => $message->content]);

        return responseJson($message, 200, 'Tạo tin nhắn thành công!');

    } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
        return responseJson(null, 401, "Người dùng chưa xác thực!");
    } catch (\Exception $e) {
        return responseJson(null, 500, "Có lỗi xảy ra: " . $e->getMessage());
    }
    }

    public function getMyConversations(Request $request) {
        try {
            $user = auth()->userOrFail();
            $userId = $user->id;

            $q = strtolower($request->q) ?? '';
            $type = $request->type;

            $conversationParticipants = DB::table('conversation_participants')
            ->where('user_id', $userId)
            ->select('conversation_participants.conversation_id');

            if (!empty($q) && $type == 'individual') {
                $conversationParticipants->whereIn('conversation_id', function ($query) use ($q, $userId) {
                    $query->select('conversation_id')
                        ->from('conversation_participants as cp')
                        ->join('users', 'users.id', '=', 'cp.user_id')
                        ->where('cp.user_id', '!=', $userId)
                        ->where(function ($innerQuery) use ($q) {
                            $innerQuery->where(DB::raw('LOWER(users.first_name)'), 'like', '%' . $q . '%')
                                       ->orWhere(DB::raw('LOWER(users.last_name)'), 'like', '%' . $q . '%');
                    });
                });
            }



            $conversations = Conversation::whereIn('id', $conversationParticipants)
            ->when($type, function ($query) use ($type, $q) {
                if ($type == 'group') {
                    $query->where('name', 'like', '%' . $q . '%');
                } else {
                    $query->where('type', $type);
                }
            }, function ($query) {
                $query->whereNotNull('type');
            })
            ->whereExists(function ($query) use ($userId) {
                $query->select(DB::raw(1))
                    ->from('messages')
                    ->whereColumn('messages.conversation_id', 'conversations.id')
                    ->whereNotIn('messages.id', function ($subQuery) use ($userId) {
                        $subQuery->select('message_id')
                            ->from('messages_deleted_by')
                            ->where('user_id', $userId);
                    });
            })
            ->orderBy('updated_at')
            ->get();


            $conversations->each(function ($conversation) use ($userId) {
                $conversation->load('participants');
                $conversation['partners'] = $conversation->participants
                ->reject(function ($participant) use ($userId) {
                    return $participant->user_id === $userId;
                })
                ->map(function ($participant) {
                    return [
                        'id' => $participant->user->id,
                        'first_name' => $participant->user->first_name,
                        'last_name' => $participant->user->last_name,
                        'avatar' => $participant->user->avatar
                    ];
                })->values()->all();
                unset($conversation->participants);
            });

            return responseJson($conversations, 200, 'Truy vấn các cuộc đối thoại của bạn thành công!');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return responseJson(null, 404, "Người dùng chưa xác thực!");
        }
    }

    public function getMessagesByConversationId($conversationId) {
        try{
            $user = auth()->userOrFail();
            $userId = $user->id;

            $page = request()->query('page', 1);
            $perPage = request()->query('per_page', 20);


            $conversationParticipants = DB::table('conversation_participants')
            ->where('user_id', $userId)
            ->where('conversation_id', $conversationId)
            ->first();


            if(!$conversationParticipants){
                return responseJson(null, 400, 'Bạn chưa tham gia cuộc đối thoại này nên không thể lấy tin nhắn từ nó!');
            }

            $messages = Message::where('conversation_id', $conversationParticipants->conversation_id)
                ->whereDoesntHave('deleted_by', function($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->latest()
                ->with(['user', 'seen_by', 'images'])
                ->paginate($perPage, ['*'], 'page', $page);

            if($messages->isEmpty()){
                return responseJson($messages, 200, 'Không có tin nhắn trong cuộc đối thoại này!');
            }

            $response = [
                'messages' => $messages->items(),
                'page_info' => [
                    'total' => $messages->total(),
                    'total_page' => (int) ceil($messages->total() / $messages->perPage()),
                    'current_page' => $messages->currentPage(),
                    'next_page' => $messages->currentPage() < $messages->lastPage() ? $messages->currentPage() + 1 : null,
                    'per_page' => $messages->perPage(),
                ],
            ];

            return responseJson($response, 200, 'Lấy tin nhắn trong cuộc đối thoại thành công');

        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, "Người dùng chưa xác thực!");
        }
    }

    public function getSecretKey($conversationId) {
        try{
            auth()->userOrFail();

            $conversation = DB::table('conversations')
            ->where('id', $conversationId)
            ->first();

            if(!$conversation){
                return responseJson(null, 400, 'Secret key không đúng!');
            }
            return responseJson($conversation->secret_key);

        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, "Người dùng chưa xác thực!");
        }
    }

    public function getConversationParticipants(Request $request) {
        try{
            auth()->userOrFail();

            $conversationIds = explode(',', $request->input('ids'));

            $conversationParticipants = ConversationParticipant::whereIn('conversation_id', $conversationIds)
            ->with('user')
            ->get();

            if($conversationParticipants->isEmpty()){
                return responseJson(null, 400, 'Truy vấn người tham gia cuộc trò chuyện không thành công!');
            }
            return responseJson($conversationParticipants, 200, 'Truy vấn người tham gia cuộc trò chuyện thành công!');

        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, "Người dùng chưa xác thực!");
        }
    }

    public function markMessageAsRead(Request $request) {
        try{
            $user = auth()->userOrFail();
            $userId = $user->id;

            $data = $request->only(['message_ids']);

            $validator = Validator::make($data, [
                'message_ids' => 'required',
                'message_ids.*' => 'exists:messages,id',
            ], [
                'message_ids.required' => 'Vui lòng nhập message_id',
                'message_ids.*.exists' => 'Không tìm thấy tin nhắn!',
            ]);

            if ($validator->fails()) {
                return responseJson(null, 400, $validator->errors()->first());
            }

            $messageIds = $validator->validated()['message_ids'];

            foreach($messageIds as $messageId){

                MessagesSeenBy::create([
                    'user_id' => $userId,
                    'message_id' => $messageId
                ]);

                // $this->MessageSent->pusherMessageIsRead($messageId, $seen);
            }

            return responseJson(null, 200);

        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, "Người dùng chưa xác thực!");
        }
    }

    public function deleteConversation($conversationId){
        try{
            $user = auth()->userOrFail();

            $userId = $user->id;

            $messages = Message::where('conversation_id', $conversationId)->get();


            foreach($messages as $message){
                MessagesDeletedBy::create([
                    'user_id' => $userId,
                    'message_id' => $message->id
                ]);
            }

           return responseJson(null, 200, 'Đã xóa cuộc đối thoại này!');

        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, "Người dùng chưa xác thực!");
        }
    }

    public function getMessageImages($conversationId) {
        try{
            auth()->userOrFail();

            $messageImages = MessageImage::select('message_images.*')
            ->join('messages', 'messages.id', '=', 'message_images.message_id')
            ->where('messages.conversation_id', $conversationId)
            ->limit(9)
            ->get();


           return responseJson($messageImages, 200);

        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, "Người dùng chưa xác thực!");
        }
    }

}
