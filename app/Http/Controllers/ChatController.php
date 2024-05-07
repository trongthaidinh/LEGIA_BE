<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ConversationParticipant;
use App\Models\Message;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    public function createConversation() {
        try{
            $user = auth()->userOrFail();

            $conversationData = request()->only(['name', 'type', 'targets_id']);

            $validatorConversation = Validator::make($conversationData, [
                'name' => 'nullable',
                'type' => 'nullable|in:individual,group',
                'targets_id' => 'required|array|min:1',
                'targets_id.*' => 'required|string|exists:users,id',
            ], chatValidatorMessages());

            if($validatorConversation->fails()){
                return responseJson(null, 400, $validatorConversation->errors());
            }

            foreach ($conversationData['targets_id'] as $targetId) {
                if($targetId == $user->id){
                    return responseJson(null, 400, 'Bạn không thể trò chuyện với chính mình!');
                }
            }

            $conversation = Conversation::create(array_merge(
                $validatorConversation->validated(),
                ['creator_id' => $user->id]
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
                'conversation_id' => 'required|string|exists:conversations,id',
                'content' => 'required|string|max:400',
            ], chatValidatorMessages());

            if($validator->fails()){
                return responseJson(null, 400, $validator->errors());
            }

            $message = Message::create(array_merge(
                $validator->validated(),
                ['user_id' => $user->id]
            ));

            return responseJson($message, 200, 'Tạo tin nhắn thành công!');

        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, "Người dùng chưa xác thực!");
        }
    }

    public function getMyConversations()
    {
        try {
            $user = auth()->userOrFail();

            $conversationParticipants = ConversationParticipant::where('user_id', $user->id)
            ->get();

            if ($conversationParticipants->isEmpty()) {
                return responseJson(null, 400, 'Bạn chưa tham gia cuộc đối thoại nào!');
            }

            $conversations = Conversation::whereIn('id', $conversationParticipants
            ->pluck('conversation_id'))
            ->get();

            if ($conversations->isEmpty()) {
                return responseJson(null, 400, 'Bạn chưa tham gia cuộc đối thoại nào!');
            }

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
            ->orderBy('created_at', 'desc')
            ->get();

            if($messages->isEmpty()){
                return responseJson(null, 400, 'Không có tin nhắn trong cuộc đối thoại này!');
            }

            return responseJson($messages, 200, 'Lấy tin nhắn trong cuộc đối thoại thành công');

        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, "Người dùng chưa xác thực!");
        }
    }
}
