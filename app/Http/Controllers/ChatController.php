<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Support\Facades\DB;
use App\Models\ConversationParticipant;
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

            if($conversationData['type'] === 'group' && !$conversationData['name'] ){
                return responseJson(null, 400, 'Vui lòng nhập tên nhóm chat!');
            }

            foreach ($conversationData['targets_id'] as $targetId) {
                if($targetId == $user->id){
                    return responseJson(null, 400, 'Bạn không thể trò chuyện với chính mình!');
                }
            }

            $conversationType = $validatorConversation->getData()['type'];

            if (!$conversationType || $conversationType == 'individual' ) {
                $target_id = $validatorConversation->getData()['targets_id'][0]->id;

                if(!$target_id){
                    return responseJson(null, 400, 'Vui lý nhập thông tin người tham gia đoạn chat cá nhân!');
                }

                $conversationParticipantPartners = DB::table('conversation_participants')
                ->where('user_id', $target_id)
                ->get();

                foreach ($conversationParticipantPartners as $conversationParticipantPartner) {

                    $conversation_id = $conversationParticipantPartner->conversation_id;

                    $conversationParticipantPartnerAndMe = DB::table('conversation_participants')
                        ->where('user_id', $user->id)
                        ->where('conversation_id', $conversation_id)
                        ->first();

                    if ($conversationParticipantPartnerAndMe) {
                        return responseJson(null, 400, 'Đoạn chat cá nhân đã được tạo từ trước!');
                    }
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

    public function createMessage(){
        try {
            $user = auth()->userOrFail();

            $data = request()->only(['conversation_id', 'content']);

            $validator = Validator::make($data, [
                'conversation_id' => 'required|exists:conversations,id',
                'content' => 'required|string|max:400',
            ], chatValidatorMessages());

            if($validator->fails()){
                return responseJson(null, 400, $validator->errors());
            }

            $conversationId = $data['conversation_id'];

            $message = Message::create(array_merge(
                $validator->validated(),
                ['user_id' => $user->id]
            ));

            $conversation =  DB::table('conversations')
            ->where('id', $message->conversation_id)
            ->first();

            $partners = DB::table('conversation_participants')
            ->where('conversation_id', $conversationId)
            ->where('user_id', '!=', $user->id)
            ->get();

            $this->MessageSent->pusherMessageSent($conversation->secret_key, $message);

            foreach ($partners as $partner) {
                $this->MessageSent->pusherConversationIdGetNewMessage($partner->user_id, [
                    'conversation_id' => $conversationId,
                    'content' => $message->content
                ]);
            }

            DB::table('conversations')
                ->where('id', $conversationId)
                ->update(['last_message' => $message->content]);

            return responseJson($message, 200, 'Tạo tin nhắn thành công!');

        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, "Người dùng chưa xác thực!");
        }
    }

    public function getMyConversations(Request $request) {
        try {
            $user = auth()->userOrFail();
            $userId = $user->id;

            $q = strtolower($request->q) ?? '';

            $conversationParticipants = DB::table('conversation_participants')
            ->where('user_id', $userId)
            ->select('conversation_participants.conversation_id');

            if (!empty($q)) {
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
            ->whereHas('messages', function ($query) use ($userId) {
                $query->where('deleted_by', '!=', $userId)
                      ->orWhereNull('deleted_by');
            })
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
            $perPage = request()->query('per_page', 10);


            $conversationParticipants = DB::table('conversation_participants')
            ->where('user_id', $userId)
            ->where('conversation_id', $conversationId)
            ->first();


            if(!$conversationParticipants){
                return responseJson(null, 400, 'Bạn chưa tham gia cuộc đối thoại này nên không thể lấy tin nhắn từ nó!');
            }
            $messages = DB::table('messages')
                ->where('conversation_id', $conversationParticipants->conversation_id)
                ->where(function($query) use ($userId) {
                    $query->where('deleted_by', '!=', $userId)
                        ->orWhereNull('deleted_by');
                })
                ->latest()
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

    public function markMessageAsRead(Request $request, $messageId) {
        $data = $request->only(['secret_key']);

        $validator = Validator::make($data, [
            'secret_key' => 'required|string|exists:conversations,secret_key',
        ], chatValidatorMessages());

        if ($validator->fails()) {
            return responseJson(null, 400, $validator->errors()->first());
        }

        try{
            $user = auth()->userOrFail();
            $message = Message::find($messageId);
            if(!$message){
                return responseJson(null, 400, 'Không tìm thấy tin nhắn!');
            }
            $message->read_at = now();

            $this->messageSent->pusherMessageIsRead($data['secret_key'], $message);

            $message->save();

            return responseJson($message, 200, 'Thành công!');

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
                if($message->deleted_by != null && $message->deleted_by != $userId){
                    $message->delete();
                }else{
                    $message->deleted_by = $userId;
                    $message->save();
                }
            }

           return responseJson(null, 200, 'Đã xóa cuộc đối thoại này!');

        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, "Người dùng chưa xác thực!");
        }
    }

}
