<?php

namespace App\Livewire;

use App\Events\MessageSent;
use App\Events\UnreadMessage;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Chat extends Component
{
    public $users = [];
    public $selectedUser;
    public $newMessage;
    public $messages;
    public $authId;
    public $loginId;
    public $unreadCounts = [];

    public function mount()
    {
        $this->users = User::where('id', '!=', auth()->id())->latest()->get();

        $this->selectedUser = $this->users->first();

        $this->loadMessages();

        $this->authId = Auth::id();

        $this->loginId = Auth::id();

        foreach ($this->users as $user) {

            $this->unreadCounts[$user->id] = ChatMessage::where('sender_id', $user->id)->where('receiver_id', Auth::id())->where('is_read', false)->count();
        }



    }

    public function loadMessages()
    {
        if (!$this->selectedUser)
            return;


        $this->messages = ChatMessage::where(function ($query) {

            $query->where('sender_id', auth()->id())
                ->where('receiver_id', $this->selectedUser->id);

        })->orWhere(function ($query) {

            $query->where('sender_id', $this->selectedUser->id)
                ->where('receiver_id', auth()->id());

        })->latest()->get()->reverse();

    }

    public function selectUser($userId)
    {
        $this->selectedUser = User::find($userId);

        ChatMessage::where('sender_id', $userId)->where('receiver_id', Auth::id())->where('is_read', false)->update(['is_read' => true]);

        $this->unreadCounts[$userId] = 0;

        $this->loadMessages();
    }

    public function submit()
    {
        if (!$this->newMessage || !$this->selectedUser) {
            return;
        }

        $message = ChatMessage::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $this->selectedUser->id,
            'message' => $this->newMessage,
        ]);


        // Load relationships
        $message->load('sender', 'receiver');

        // Push message instantly in UI
        $this->messages->push($message);


        // Clear input
        $this->newMessage = '';

        \Log::info('Broadcasting Event');

        // Broadcast to other users only
        broadcast(new MessageSent($message))->toOthers();

        $unreadMessageCount = ChatMessage::where('sender_id', Auth::id())->where('receiver_id', $this->selectedUser->id)->where('is_read', false)->count();

        broadcast(new UnreadMessage(
            Auth::id(),
            $this->selectedUser->id,
            $unreadMessageCount
        ));
    }


    public function getListeners()
    {
        return [
            "echo-private:chat.{$this->loginId},MessageSent" => 'newChatMessageNotification',

            "echo-private:unread-channel.{$this->loginId},UnreadMessage" => 'updateUnreadCount',
        ];
    }

    public function updateUnreadCount($data)
    {
        $senderId = $data['sender_id'];

        $this->unreadCounts[$senderId] = $data['unreadMessageCount'];

        // Move latest chat to top
        $this->users = $this->users->sortByDesc(function ($user) use ($senderId) {

            return $user->id == $senderId;

        });
    }


    public function newChatMessageNotification($message)
    { //for listening to the event and updating the chat in real time

        $this->messages->push(
            ChatMessage::find($message['id'])
        );
    }

    public function render()
    {
        return view('livewire.chat');
    }
}
?>