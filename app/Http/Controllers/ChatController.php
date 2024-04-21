<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ConversationParticipant;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    public function createConversation() {
        try{
            $user = auth()->userOrFail();

            $conversationData = request()->only(['name', 'type', 'target_id']);

            $validatorConversation = Validator::make($conversationData, [
                'name' => 'nullable',
                'type' => 'nullable|in:individual,group',
                'target_id' => 'required|array|min:1',
                'target_id.*' => 'required|string|exists:users,id',
            ], chatValidatorMessages());

            if($validatorConversation->fails()){
                return responseJson(null, 400, $validatorConversation->errors());
            }

            foreach ($conversationData['target_id'] as $targetId) {
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

            if($conversationParticipantsCreated->isEmpty()){
                $participants = [];

                $myConversationParticipant = ConversationParticipant::create([
                    'user_id' => $user->id,
                    'conversation_id' => $conversation->id,
                ]);

                $participants[] = $myConversationParticipant;

                foreach ($conversationData['target_id'] as $targetId) {
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
            }

            return responseJson(null, 400, 'Cuộc đối thoại và những người tham gia đã được tạo từ trước!');


        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, 'Không tìm thấy người dùng!');
        }
    }
}
