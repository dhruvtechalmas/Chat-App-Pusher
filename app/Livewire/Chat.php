<?php

namespace App\Livewire;

use App\Events\MessageSent;
use App\Events\UnreadMessage;
use App\Models\ChatMessage;
use App\Models\Group;
use App\Models\GroupMessage;
use App\Models\MutedChat;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Chat extends Component
{
    public $users = [];
    public $selectedUser;
    public $groupName;
    public $selectedUsers = [];
    public $groups = [];
    public $newMessage;
    public $messages;
    public $authId;
    public $loginId;
    public $unreadCounts = [];
    public $isMuted = false;
    public $selectedGroup = null;
    public $isGroupChat = false;
    public $showGroupPopup = false;

    public function mount()
    {
        $this->users = User::where('id', '!=', auth()->id())->latest()->get(); //latest users show karo

        $this->groups = auth()->user()->groups()->latest()->get(); //latest groups show karo


        //ki agar koi user already select hai toh usko show karo warna first user ka chat show karo

        $selectedUserId = session('selected_user_id');

        if ($selectedUserId) {

            $selectedUser = $this->users->where('id', $selectedUserId)->first();

            if ($selectedUser) {

                $this->users = $this->users->sortByDesc(function ($user) use ($selectedUserId) {

                    return $user->id == $selectedUserId;

                });

                $this->selectedUser = $selectedUser;
            }
        } else {

            $this->selectedUser = $this->users->first(); // First user ka chat show karo
        }


        $this->loadMessages();

        $this->authId = Auth::id();

        $this->loginId = Auth::id();

        foreach ($this->users as $user) {

            foreach ($this->users as $user) {

                // CHECK MUTE
                $isMuted = MutedChat::where('user_id', auth()->id())
                    ->where('muted_user_id', $user->id)
                    ->exists();

                // Agar muted hai to unread 0
                if ($isMuted) {

                    $this->unreadCounts[$user->id] = 0;

                } else {

                    $this->unreadCounts[$user->id] =
                        ChatMessage::where('sender_id', $user->id)
                            ->where('receiver_id', Auth::id())
                            ->where('is_read', false)
                            ->count();
                }
            }
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

        })->latest()->get()->reverse();// databsse newest messages show karo

    }

    public function selectUser($userId)
    {
        $this->isGroupChat = false;

        $this->selectedGroup = null;

        $this->selectedUser = User::find($userId);

        $this->isMuted = MutedChat::where('user_id', auth()->id())
            ->where('muted_user_id', $userId)
            ->exists();

        // SAVE SELECTED USER
        session([
            'selected_user_id' => $userId //when refresh page still selected user show 
        ]);

        ChatMessage::where('sender_id', $userId)->where('receiver_id', Auth::id())->where('is_read', false)->update(['is_read' => true]);

        $this->unreadCounts[$userId] = 0;

        $this->loadMessages();
    }

    public function submit()
    {

        if ($this->isGroupChat) {

            GroupMessage::create([
                'group_id' => $this->selectedGroup->id,
                'sender_id' => auth()->id(),
                'message' => $this->newMessage,
            ]);

            $this->loadGroupMessages();

            $this->newMessage = '';

            return;
        }

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

        // Latest chat top pe lao
        $this->moveUserToTop($this->selectedUser->id);

        // Clear input
        $this->newMessage = '';

        \Log::info('Broadcasting Event');

        // Broadcast to other users only
        broadcast(new MessageSent($message))->toOthers();

        $unreadMessageCount = ChatMessage::where('sender_id', Auth::id())
            ->where('receiver_id', $this->selectedUser->id)
            ->where('is_read', false)
            ->count();

        broadcast(new UnreadMessage(
            Auth::id(),
            $this->selectedUser->id,
            $unreadMessageCount
        ));
    }


    public function getListeners() //dynamic event listeners for user specific channels
    {
        return [
            "echo-private:chat.{$this->loginId},MessageSent" => 'newChatMessageNotification',

            "echo-private:unread-channel.{$this->loginId},UnreadMessage" => 'updateUnreadCount',
        ];
    }

    public function updateUnreadCount($data)
    {
        $senderId = $data['sender_id'];

        // Check if sender is muted
        $isMuted = MutedChat::where('user_id', auth()->id())
            ->where('muted_user_id', $senderId)
            ->exists();

        if ($isMuted) {
            return;
        }

        // Agar same chat already open hai
        if ($this->selectedUser && $this->selectedUser->id == $senderId) {

            ChatMessage::where('sender_id', $senderId)
                ->where('receiver_id', Auth::id())
                ->where('is_read', false)
                ->update([
                    'is_read' => true
                ]);

            $this->unreadCounts[$senderId] = 0;

            return;
        }

        // Otherwise badge show karo
        $this->unreadCounts[$senderId] = $data['unreadMessageCount'];

        // Latest chat top pe lao
        $this->users = $this->users->sortByDesc(function ($user) use ($senderId) {

            return $user->id == $senderId;

        });
    }

    // Jab new message aaye toh agar current selected user ka message hai toh usko show karo otherwise badge update karo
    public function newChatMessageNotification($message)
    {
        $messageModel = ChatMessage::find($message['id']);

        // Sirf current selected user ka message show karo
        if ($this->selectedUser && $this->selectedUser->id == $messageModel->sender_id) {

            $this->messages->push($messageModel);

            // Read mark
            $messageModel->update([
                'is_read' => true
            ]);

            $this->unreadCounts[$messageModel->sender_id] = 0;
        }
    }

    public function moveUserToTop($userId)
    {
        $selected = $this->users->firstWhere('id', $userId);

        if (!$selected) {
            return;
        }

        // Remove old position
        $this->users = $this->users->reject(function ($user) use ($userId) {
            return $user->id == $userId;
        });

        // Add on top
        $this->users->prepend($selected);
    }

    public function muteUser()
    {
        $mute = MutedChat::where('user_id', auth()->id())
            ->where('muted_user_id', $this->selectedUser->id)
            ->first();

        // UNMUTE
        if ($mute) {

            $mute->delete();

            $this->isMuted = false;

        } else {

            // MUTE
            MutedChat::create([
                'user_id' => auth()->id(),
                'muted_user_id' => $this->selectedUser->id,
            ]);

            $this->isMuted = true;
        }
    }

    public function createGroup()
    {
        if (!$this->groupName) {

            return;
        }

        // CREATE GROUP

        $group = Group::create([
            'name' => $this->groupName,
            'created_by' => auth()->id(),
        ]);

        // ADD USERS

        $group->users()->attach($this->selectedUsers);

        // ADD LOGIN USER

        $group->users()->attach(auth()->id());

        // RELOAD GROUPS

        $this->groups = auth()->user()->groups;

        // AUTO OPEN CREATED GROUP

        $this->selectedGroup = $group;

        $this->isGroupChat = true;

        $this->selectedUser = null;

        $this->loadGroupMessages();

        // CLEAR

        $this->groupName = '';

        $this->selectedUsers = [];

        // CLOSE POPUP

        $this->showGroupPopup = false;

        session()->flash('success', 'Group Created Successfully');
    }

    public function selectGroup($groupId)
    {
        $this->selectedGroup = Group::find($groupId);

        $this->isGroupChat = true;

        $this->selectedUser = null;

        $this->loadGroupMessages();
    }

    public function loadGroupMessages()
    {
        $this->messages = GroupMessage::where('group_id', $this->selectedGroup->id)
            ->latest()
            ->get()
            ->reverse();
    }


    public function render()
    {
        return view('livewire.chat');
    }
}
?>