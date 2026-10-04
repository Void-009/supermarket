<div x-data="adminChatWidget()" x-init="init()" x-cloak>
    
    
    <button @click="toggle()"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-4"
        class="fixed z-[80] left-4 sm:left-6 bottom-6 w-14 h-14 bg-emerald-500 hover:bg-emerald-600 rounded-full shadow-lg flex items-center justify-center transition-all hover:scale-110 group no-print relative"
        style="box-shadow: 0 4px 20px rgba(34, 197, 94, 0.4); margin-bottom: env(safe-area-inset-bottom, 0px); margin-left: env(safe-area-inset-left, 0px);"
        :class="{ 'bg-red-500 hover:bg-red-600': open }"
        title="Conversations">
        <template x-if="!open">
            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
        </template>
        <template x-if="open">
            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </template>
        <template x-if="totalUnread > 0 && !open">
            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold h-5 w-5 flex items-center justify-center rounded-full shadow" x-text="totalUnread > 99 ? '99+' : totalUnread"></span>
        </template>
        <span class="absolute -top-10 left-0 bg-gray-900 text-white text-xs font-medium px-3 py-1.5 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none" x-text="open ? 'Close' : 'Conversations'">
        </span>
    </button>

    
    <div x-show="open" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 translate-y-4 scale-95"
        
        class="fixed z-[90] left-4 sm:left-6 bottom-[6.5rem] w-[min(420px,calc(100vw-2rem))] h-[min(560px,calc(100dvh-8rem))] bg-white rounded-2xl shadow-2xl border border-gray-200 flex flex-col overflow-hidden"
        style="margin-bottom: env(safe-area-inset-bottom, 0px); margin-left: env(safe-area-inset-left, 0px);"
        @click.away="open = false">
        
        <div class="bg-emerald-500 text-white px-5 py-4 flex items-center justify-between shrink-0">
            <template x-if="view === 'list'">
                <div>
                    <h3 class="font-semibold">Conversations</h3>
                    <p class="text-xs text-emerald-100" x-text="chats.length + ' active chats'"></p>
                </div>
            </template>
            <template x-if="view === 'thread'">
                <div class="flex items-center gap-3">
                    <button @click="backToList()" class="text-white/80 hover:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                    </button>
                    <div>
                        <h3 class="font-semibold" x-text="activeChat?.customer_name || 'Chat'"></h3>
                        <p class="text-xs text-emerald-100">Conversation</p>
                    </div>
                </div>
            </template>
            <button @click="open = false" class="text-white/80 hover:text-white">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        
        <template x-if="view === 'list'">
            <div class="flex-1 overflow-y-auto bg-gray-50">
                <template x-if="chats.length === 0">
                    <div class="flex flex-col items-center justify-center h-full text-gray-400 text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="mb-3 opacity-50"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        <p>No active conversations</p>
                    </div>
                </template>
                <template x-for="chat in chats" :key="chat.id">
                    <div @click="selectChat(chat)" class="flex items-center gap-3 px-5 py-4 cursor-pointer hover:bg-white border-b border-gray-100 transition-colors">
                        <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center font-semibold text-sm shrink-0" x-text="(chat.customer_name || '?').charAt(0).toUpperCase()"></div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-sm text-gray-900 truncate" x-text="chat.customer_name"></span>
                                <span class="text-[11px] text-gray-400 shrink-0 ml-2" x-text="timeAgo(chat.created_at)"></span>
                            </div>
                            <div class="flex items-center justify-between mt-0.5">
                                <span class="text-xs text-gray-500 truncate" x-text="chat.last_message || 'No messages'"></span>
                                <template x-if="chat.unread > 0">
                                    <span class="ml-2 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full shrink-0" x-text="chat.unread"></span>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        
        <div x-show="view === 'thread'" class="flex flex-col h-full" x-cloak>
                
                <div class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50" x-ref="threadMessages">
                    <template x-for="msg in threadMessages" :key="msg.id">
                        <div class="flex" :class="msg.is_admin ? 'justify-end' : 'justify-start'">
                            <div class="max-w-[80%] px-4 py-2.5 rounded-2xl text-sm leading-relaxed break-words min-w-0"
                                :class="msg.is_admin ? 'bg-emerald-500 text-white rounded-br-md' : 'bg-white text-gray-800 rounded-bl-md shadow-sm'">
                                <template x-if="!msg.is_admin">
                                    <div class="text-[10px] font-semibold text-emerald-600 mb-0.5" x-text="msg.sender_name"></div>
                                </template>
                                <p x-text="msg.message"></p>
                                <div class="text-[10px] mt-1" :class="msg.is_admin ? 'text-emerald-100' : 'text-gray-400'" x-text="formatTime(msg.created_at)"></div>
                            </div>
                        </div>
                    </template>
                </div>

                
                <template x-if="quickResponses.length > 0">
                    <div class="px-4 py-2 border-t border-gray-100 bg-white shrink-0">
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="qr in quickResponses" :key="qr.id">
                                <button @click="useQuickResponse(qr)" class="text-xs px-3 py-1.5 bg-gray-100 hover:bg-emerald-50 hover:text-emerald-700 rounded-lg transition-colors text-gray-600" x-text="qr.title"></button>
                            </template>
                        </div>
                    </div>
                </template>

                
                <div class="border-t border-gray-200 px-4 py-3 shrink-0 bg-white">
                    <form @submit.prevent="sendReply()" class="flex gap-2">
                        <input type="text" x-model="newMessage" placeholder="Type reply..."
                            class="flex-1 min-w-0 px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors"
                            :disabled="sending">
                        <button type="submit" :disabled="!newMessage.trim() || sending"
                            class="shrink-0 whitespace-nowrap px-5 py-2.5 bg-emerald-500 text-white text-sm font-medium rounded-xl hover:bg-emerald-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="!sending">Send</span>
                            <span x-show="sending" x-cloak><svg class="animate-spin h-4 w-4 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg></span>
                        </button>
                    </form>
                    <button @click="deleteChat()" class="mt-2 text-xs text-red-400 hover:text-red-600 transition-colors">
                        Delete this conversation
                    </button>
                </div>
        </div>
    </div>

    <script>
        function adminChatWidget() {
            return {
                open: false,
                view: 'list',
                chats: [],
                activeChat: null,
                threadMessages: [],
                quickResponses: [],
                newMessage: '',
                sending: false,
                totalUnread: 0,
                csrf: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',

                init() {
                    if (window.Echo) {
                        this.fetchChats();
                        this.listenAdminChannel();
                    }
                },

                toggle() {
                    this.open = !this.open;
                    if (this.open) {
                        this.fetchChats();
                    }
                },

                fetchChats() {
                    fetch('/admin/chat')
                        .then(r => r.json())
                        .then(data => {
                            this.chats = data.chats || [];
                            this.totalUnread = this.chats.reduce((sum, c) => sum + (c.unread || 0), 0);
                        })
                        .catch(() => {
                            this.chats = this.chats || [];
                        });
                },

                listenAdminChannel() {
                    if (window.Echo) {
                        window.Echo.private('admin.chats')
                            .error(() => {})
                            .listen('.NewChatCreated', () => this.fetchChats());
                    }
                },

                listenThreadChannel() {
                    if (!this.activeChat?.id || !window.Echo) return;
                    window.Echo.leave(`chat.${this.activeChat.id}`);
                    window.Echo.private(`chat.${this.activeChat.id}`)
                        .error(() => {})
                        .listen('.NewChatMessage', (e) => {
                            if (e.chatId === this.activeChat.id) {
                                this.threadMessages.push(e.message);
                                this.$nextTick(() => this.scrollToBottom());
                            }
                            this.fetchChats();
                        });
                },

                selectChat(chat) {
                    this.activeChat = chat;
                    this.view = 'thread';
                    this.threadMessages = [];
                    this.newMessage = '';
                    this.listenThreadChannel();
                    fetch(`/admin/chat/${chat.id}`)
                        .then(r => r.json())
                        .then(data => {
                            this.threadMessages = data.messages || [];
                            this.activeChat = data.chat;
                            this.$nextTick(() => this.scrollToBottom());
                            this.fetchChats();
                            this.fetchQuickResponses();
                        })
                        .catch(() => {
                            this.threadMessages = [];
                        });
                },

                backToList() {
                    if (this.activeChat?.id && window.Echo) {
                        window.Echo.leave(`chat.${this.activeChat.id}`);
                    }
                    this.view = 'list';
                    this.activeChat = null;
                    this.threadMessages = [];
                    this.newMessage = '';
                    this.fetchChats();
                },

                fetchQuickResponses() {
                    fetch('/admin/chat/quick-responses/data')
                        .then(r => r.json())
                        .then(data => { this.quickResponses = data.responses || []; })
                        .catch(() => { this.quickResponses = []; });
                },

                useQuickResponse(qr) {
                    this.newMessage = qr.message;
                    this.$nextTick(() => this.sendReply());
                },

                sendReply() {
                    if (!this.newMessage.trim() || this.sending || !this.activeChat) return;
                    this.sending = true;
                    fetch(`/admin/chat/${this.activeChat.id}/reply`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                        body: JSON.stringify({ message: this.newMessage.trim() }),
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            this.threadMessages.push(data.message);
                            this.newMessage = '';
                            this.$nextTick(() => this.scrollToBottom());
                        }
                    })
                    .catch(() => {})
                    .finally(() => { this.sending = false; });
                },

                deleteChat() {
                    if (!this.activeChat || !confirm('Delete this conversation?')) return;
                    fetch(`/admin/chat/${this.activeChat.id}`, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': this.csrf },
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) this.backToList();
                    })
                    .catch(() => {});
                },

                scrollToBottom() {
                    const container = this.$refs.threadMessages;
                    if (container) container.scrollTop = container.scrollHeight;
                },

                formatTime(dateStr) {
                    const d = new Date(dateStr);
                    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                },

                timeAgo(dateStr) {
                    const now = new Date();
                    const d = new Date(dateStr);
                    const diff = Math.floor((now - d) / 1000);
                    if (diff < 60) return 'just now';
                    if (diff < 3600) return Math.floor(diff / 60) + 'm';
                    if (diff < 86400) return Math.floor(diff / 3600) + 'h';
                    return d.toLocaleDateString();
                }
            }
        }
    </script>
</div>
<?php /**PATH C:\Users\Smk\Desktop\supermarket\resources\views/components/admin-chat-widget.blade.php ENDPATH**/ ?>