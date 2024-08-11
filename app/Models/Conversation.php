<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'creator_id',
        'name',
        'type',
        'secret_key',
        'last_message'
    ];

    public function creator() {
        return $this->belongsTo(User::class, 'creator_id', 'id');
    }

    public function participants()
    {
        return $this->hasMany(ConversationParticipant::class, 'conversation_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'conversation_id', 'id');
    }

    public function messagesNotDeleted()
    {
        return $this->hasMany(Message::class, 'conversation_id', 'id')
                    ->whereDoesntHave('deleted_by');
    }

    public function partners()
    {
        return $this->participants()->where('user_id', '!=', auth()->id());
    }

    public function myUnreadMessagesCount()
    {
        $userId = auth()->id();
        return $this->messages()
            ->whereNotIn('id', function($query) use ($userId) {
                $query->select('message_id')
                      ->from('Messages_Seen_By')
                      ->where('user_id', $userId);
            })
            ->count();
    }

}
