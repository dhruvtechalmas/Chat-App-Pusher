<div x-data="{ open: @entangle('showGroupPopup') }">

    <div class="relative mb-6 w-full">

        <flux:heading size="xl" level="1">
            {{ __('Chat') }}
        </flux:heading>

        <flux:separator variant="subtle" />

        <!-- GROUP POPUP -->

        <div x-show="open" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">

            <div class="bg-white p-5 rounded-lg w-96" @click.stop>

                <h2 class="text-lg font-bold mb-4">
                    Create Group
                </h2>

                <!-- SUCCESS -->

                @if (session()->has('success'))

                    <div class="bg-green-100 text-green-700 p-2 rounded mb-3">

                        {{ session('success') }}

                    </div>

                @endif

                <!-- GROUP NAME -->

                <input type="text" wire:model="groupName" placeholder="Group Name"
                    class="w-full border p-2 rounded mb-3">

                <!-- USERS -->

                <div class="max-h-40 overflow-y-auto">

                    @foreach($users as $user)

                        <label class="flex items-center gap-2 mb-2">

                            <input type="checkbox" wire:model="selectedUsers" value="{{ $user->id }}">

                            {{ $user->name }}

                        </label>

                    @endforeach

                </div>

                <!-- BUTTONS -->

                <div class="flex gap-2 mt-4">

                    <button type="button" wire:click="createGroup" class="bg-blue-500 text-white px-4 py-2 rounded">

                        Create

                    </button>

                    <button type="button" wire:click="$set('showGroupPopup', false)" class="bg-gray-300 px-4 py-2 rounded">

                        Cancel

                    </button>

                </div>

            </div>

        </div>

    </div>



    <div class="flex h-[550px] text-sm border rounded-xl shadow overflow-hidden bg-white">

        <!-- Users -->
        <div class="w-1/4 border-r bg-gray-50">

            <div class="p-4 font-bold text-gray-700 border-b">
                Users

                <button wire:click="$set('showGroupPopup', true)"
                    class="bg-blue-600 hover:bg-blue-700 text-white text-xs px-2 py-1 rounded transition float-right">
                    Create Group
                </button>
            </div>



            <div class="divide-y">

                <!-- GROUPS -->

                @foreach($groups as $group)

                                <div wire:click="selectGroup({{ $group->id }})" class="p-3 cursor-pointer hover:bg-blue-100 transition
                     {{ $selectedGroup && $selectedGroup->id === $group->id ? 'bg-blue-200' : '' }}">

                                    <div class="text-gray-800 font-medium">
                                        👥 {{ $group->name }}
                                    </div>

                                    <div class="text-xs text-gray-500">
                                        Group Chat
                                    </div>

                                </div>

                @endforeach

                @forelse ($users as $user)

                    <div wire:click="selectUser({{ $user->id }})" wire:key="user-{{ $user->id }}"
                        class="p-3 cursor-pointer hover:bg-blue-100 transition
                                                                {{ $selectedUser && $selectedUser->id === $user->id ? 'bg-blue-200' : '' }}">

                        <div class="flex items-center justify-between">

                            <div>

                                <div class="text-gray-800 font-medium">
                                    {{ $user->name }}
                                </div>

                                <div class="text-xs text-gray-500">
                                    {{ $user->email }}
                                </div>

                            </div>

                            @if(isset($unreadCounts[$user->id]) && $unreadCounts[$user->id] > 0)

                                <div
                                    class="bg-red-500 text-white text-xs min-w-[20px] h-5 rounded-full flex items-center justify-center px-1">
                                    {{ $unreadCounts[$user->id] }}
                                </div>

                            @endif

                        </div>

                    </div>

                @empty

                    <div class="p-3 text-gray-500">
                        No users found.
                    </div>

                @endforelse

            </div>

        </div>

        <!-- Right Chat Section -->
        <div class="w-3/4 flex flex-col">

            <!-- Header -->
            <div class="p-4 border-b bg-gray-50">

                <div class="text-lg font-semibold text-gray-800">
                    {{ $isGroupChat ? $selectedGroup?->name : $selectedUser?->name }}
                </div>

                <div class="text-xs text-gray-500">
                    @if($isGroupChat)

                        <div class="text-xs text-gray-500">
                            Group Chat
                        </div>

                    @else

                        <div class="text-xs text-gray-500">
                            {{ $selectedUser?->email }}
                        </div>

                    @endif
                </div>

                <button wire:click="muteUser" class="text-xl">

                    {{ $isMuted ? '🔕' : '🔔' }}

                </button>
            </div>

            <!-- Messages -->
            <div id="chat-box" class="flex-1 p-4 overflow-y-auto bg-gray-50 flex flex-col gap-3">

                @forelse ($messages as $message)

                    <div wire:key="message-{{ $message->id }}"
                        class="{{ $message->sender_id == auth()->id() ? 'flex justify-end' : 'flex justify-start' }}">

                        <div
                            class="max-w-xs px-4 py-2 rounded-2xl shadow break-words
                                                                                            {{ $message->sender_id == auth()->id() ? 'bg-blue-600 text-white' : 'bg-gray-200 text-black'}}">

                            {{ $message->message }}

                        </div>

                    </div>

                @empty

                    <div class="text-gray-500 text-sm">
                        No messages yet.
                    </div>

                @endforelse

            </div>


            <!-- Input -->
            <form wire:submit="submit" class="p-4 border-t bg-white flex items-center gap-2">

                <input type="text" wire:model="newMessage"
                    class="flex-1 border border-gray-300 rounded-full px-4 py-2 text-sm focus:outline-none focus:ring focus:ring-blue-300"
                    placeholder="Type your message...">

                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-full transition">
                    Send
                </button>

            </form>

        </div>

    </div>

</div>

<script>
    document.addEventListener('livewire:initialized', () => {

        const chatBox = document.getElementById('chat-box');

        function scrollToBottom() {
            setTimeout(() => {
                chatBox.scrollTo({
                    top: chatBox.scrollHeight,
                    behavior: 'smooth'
                });
            }, 100);
        }

        // Initial scroll
        scrollToBottom();

        // Every Livewire update
        Livewire.hook('morphed', () => {
            scrollToBottom();
        });

    });

</script>