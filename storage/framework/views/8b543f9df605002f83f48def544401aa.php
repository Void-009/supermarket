<div x-data="chatWidget()" x-init="init()" <?php if(auth()->guard()->check()): ?> x-cloak class="contents">
    
    
    <button @click="toggle()"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-4"
        class="w-14 h-14 bg-emerald-500 hover:bg-emerald-600 rounded-full shadow-lg flex items-center justify-center transition-all hover:scale-110 group no-print relative"
        style="box-shadow: 0 4px 20px rgba(34, 197, 94, 0.4);"
        :class="{ 'bg-red-500 hover:bg-red-600': open }"
        title="Live Chat">
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
        <span class="absolute -top-10 right-0 bg-gray-900 text-white text-xs font-medium px-3 py-1.5 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none" x-text="open ? 'Close' : 'Live Chat'">
        </span>
    </button>

    
    
    <div x-show="open" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 translate-y-4 scale-95"
        class="fixed right-4 sm:right-6 w-[calc(100vw-2rem)] sm:w-[360px] max-w-[360px] h-[65vh] <?php echo e(request()->routeIs('checkout.index') ? 'bottom-[20.5rem] max-h-[calc(100dvh-21rem)] lg:bottom-[14.5rem] lg:max-h-[calc(100dvh-15rem)]' : 'bottom-[14.5rem] max-h-[calc(100dvh-15rem)]'); ?> bg-white rounded-2xl shadow-2xl border border-gray-200 flex flex-col overflow-hidden z-[90]"
        @click.away="open = false">
        
        <div class="bg-emerald-500 text-white px-5 py-4 flex items-center justify-between shrink-0">
            <div>
                <h3 class="font-semibold">Live Chat</h3>
                <p class="text-xs text-emerald-100">We usually reply within minutes</p>
            </div>
            <button @click="open = false" class="text-white/80 hover:text-white">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        
        <div class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50" x-ref="messagesContainer">
            <template x-if="messages.length === 0">
                <div class="flex flex-col items-center justify-center h-full text-gray-400 text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="mb-3 opacity-50">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                    <p>Start a conversation</p>
                    <p class="text-xs">Send us a message and we'll reply shortly</p>
                </div>
            </template>
            <template x-for="msg in messages" :key="msg.id">
                <div class="flex" :class="msg.is_admin ? 'justify-start' : 'justify-end'">
                    <div class="max-w-[80%] px-4 py-2.5 rounded-2xl text-sm leading-relaxed break-words min-w-0"
                        :class="msg.is_admin ? 'bg-white text-gray-800 rounded-bl-md shadow-sm' : 'bg-emerald-500 text-white rounded-br-md'">
                        <template x-if="msg.is_admin">
                            <div class="text-[10px] font-semibold text-emerald-600 mb-0.5" x-text="msg.sender_name"></div>
                        </template>
                        <p x-text="msg.message"></p>
                        <div class="text-[10px] mt-1" :class="msg.is_admin ? 'text-gray-400' : 'text-emerald-100'" x-text="formatTime(msg.created_at)"></div>
                    </div>
                </div>
            </template>
        </div>

        
        <div class="border-t border-gray-200 px-4 py-3 shrink-0 bg-white">
            <form @submit.prevent="send()" class="flex gap-2">
                <input type="text" x-model="newMessage" placeholder="Type your message..."
                    class="flex-1 min-w-0 px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors"
                    :disabled="sending">
                <button type="submit" :disabled="!newMessage.trim() || sending"
                    class="shrink-0 whitespace-nowrap px-5 py-2.5 bg-emerald-500 text-white text-sm font-medium rounded-xl hover:bg-emerald-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!sending">Send</span>
                    <span x-show="sending" x-cloak>
                        <svg class="animate-spin h-4 w-4 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    </span>
                </button>
            </form>
        </div>
    </div>

    <script>
        function chatWidget() {
            return {
                open: false,
                messages: [],
                newMessage: '',
                chatId: null,
                sending: false,
                csrf: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',

                init() {
                    if (window.Echo) {
                        this.fetchMessages();
                    }
                },

                toggle() {
                    this.open = !this.open;
                    if (this.open) {
                        this.fetchMessages();
                    }
                },

                fetchMessages() {
                    fetch('/chat/messages')
                        .then(r => r.json())
                        .then(data => {
                            if (data.chat_id) {
                                this.chatId = data.chat_id;
                                this.messages = data.messages || [];
                                this.listenForMessages();
                            }
                            this.$nextTick(() => this.scrollToBottom());
                        })
                        .catch(() => {
                            this.messages = this.messages || [];
                        });
                },

                listenForMessages() {
                    if (this.chatId && window.Echo) {
                        window.Echo.leave(`chat.${this.chatId}`);
                        window.Echo.private(`chat.${this.chatId}`)
                            .error(() => {})
                            .listen('.NewChatMessage', (e) => {
                                this.messages.push(e.message);
                                this.$nextTick(() => this.scrollToBottom());
                            });
                    }
                },

                send() {
                    if (!this.newMessage.trim() || this.sending) return;
                    this.sending = true;
                    fetch('/chat/send', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                        body: JSON.stringify({ message: this.newMessage.trim() }),
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            if (!this.chatId) {
                                this.chatId = data.message.chat_id;
                                this.listenForMessages();
                            }
                            this.messages.push({
                                id: data.message.id,
                                message: data.message.message,
                                user_id: data.message.user_id,
                                sender_name: 'You',
                                is_admin: false,
                                created_at: data.message.created_at,
                            });
                            this.newMessage = '';
                            this.$nextTick(() => this.scrollToBottom());
                        }
                    })
                    .catch(() => {})
                    .finally(() => { this.sending = false; });
                },

                scrollToBottom() {
                    const container = this.$refs.messagesContainer;
                    if (container) container.scrollTop = container.scrollHeight;
                },

                formatTime(dateStr) {
                    const d = new Date(dateStr);
                    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                }
            }
        }
    </script>
    <?php endif; ?>
</div>
<?php /**PATH C:\Users\Smk\Desktop\supermarket\resources\views/components/chat-widget.blade.php ENDPATH**/ ?>