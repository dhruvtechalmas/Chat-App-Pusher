<?php

namespace App\Livewire;

use App\Events\MessageSent;
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

    public function mount()
    {
        $this->users = User::where('id', '!=', auth()->id())->latest()->get();

        $this->selectedUser = $this->users->first();

        $this->loadMessages();

        $this->authId = Auth::id();

        $this->loginId = Auth::id();

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

        $this->loadMessages();
    }

    public function submit()
    {
        if (!$this->newMessage)
            return;

        $message = ChatMessage::create([

            'sender_id' => Auth::id(),

            'receiver_id' => $this->selectedUser->id,

            'message' => $this->newMessage,

        ]);


        $this->messages->push($message);

        $this->newMessage = '';

        broadcast(new MessageSent($message));
    }

    public function getListeners()
    {
        return [
            "echo-private:chat.{$this->loginId}, MessageSent" => 'newChatMessageNotification',
        ];
    }

    public function newChatMessageNotification($message)
    { //for listening to the event and updating the chat in real time

        if ($message['sender_id'] == $this->selectedUser->id) {
            $messageObj = ChatMessage::find($message['id']);
            $this->messages->push($messageObj);
        }
    }

    public function render()
    {
        return view('livewire.chat');
    }
}
?>