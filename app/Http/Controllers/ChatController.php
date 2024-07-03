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
                'targets_id.*' => 'required|number|exists:users,id',
            ], chatValidatorMessages());

            if($validatorConversation->fails()){
                return responseJson(null, 400, $validatorConversation->errors());
            }

            foreach ($conversationData['targets_id'] as $targetId) {
                if($targetId == $user->id){
                    return responseJson(null, 400, 'Bạn không thể trò chuyện với chính mình!');
                }
            }

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


            $message = Message::create(array_merge(
                $validator->validated(),
                ['user_id' => $user->id]
            ));

            $conversation =  DB::table('conversations')
            ->where('id', $message->conversation_id)
            ->first();

            $this->MessageSent->pusherMessageSent($conversation->secret_key, $message);

            return responseJson($message, 200, 'Tạo tin nhắn thành công!');

        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, "Người dùng chưa xác thực!");
        }
    }

    public function getMyConversations(Request $request) {
        try {
            $user = auth()->userOrFail();

            $q = strtolower($request->q) ?? '';

            $conversationParticipants = DB::table('conversation_participants')
            ->join('users', 'users.id', '=', 'conversation_participants.user_id')
            ->where(function ($query) use ($q) {
                $query->where(DB::raw('LOWER(users.first_name)'), 'like', '%' . $q . '%')
                      ->orWhere(DB::raw('LOWER(users.last_name)'), 'like', '%' . $q . '%');
            })
            ->select('conversation_participants.conversation_id')
            ->distinct()
            ->get()
            ->pluck('conversation_id');

            $conversations = Conversation::whereIn('id', $conversationParticipants)->get();

            if ($conversations->isEmpty()) {
                return responseJson(null, 400, 'Không có cuộc đối thoại hợp lệ!');
            }

            $conversations->each(function ($conversation) use ($user) {
                $conversation->load('participants');
                $conversation['partners'] = $conversation->participants
                ->reject(function ($participant) use ($user) {
                    return $participant->user_id === $user->id;
                })
                ->map(function ($participant) {
                    return [
                        'user_id' => $participant->user->id,
                        'first_name' => $participant->user->first_name,
                        'last_name' => $participant->user->last_name,
                        'avatar' => $participant->user->avatar
                    ];
                })->values()->all();;
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

            $conversationParticipants = DB::table('conversation_participants')
            ->where('user_id', $user->id)
            ->where('conversation_id', $conversationId)
            ->first();

            if(!$conversationParticipants){
                return responseJson(null, 400, 'Bạn chưa tham gia cuộc đối thoại này nên không thể lấy tin nhắn từ nó!');
            }

            $messages = DB::table('messages')
            ->where('conversation_id', $conversationParticipants->conversation_id)
            ->get();

            if($messages->isEmpty()){
                return responseJson(null, 400, 'Không có tin nhắn trong cuộc đối thoại này!');
            }

            return responseJson($messages, 200, 'Lấy tin nhắn trong cuộc đối thoại thành công');

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

}
