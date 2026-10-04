{{--
    The stylesheet is shared with the email and PDF rendering, so the modal can
    never drift from the receipt the customer was emailed. It keeps the
    `receipt-style` id because the print window below looks it up by that.
--}}
<x-receipt-styles />

<div x-data="receiptModal()" x-init="init()">
    <div x-show="show" x-cloak @keydown.escape.window="close()" class="fixed inset-0 z-[80] flex items-center justify-center p-4" style="display:none;">
        <div class="absolute inset-0 bg-black/60" @click="close()"></div>
            <div x-show="show" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[92vh] flex flex-col overflow-hidden">
                <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100 shrink-0 bg-white">
                    <h3 class="font-bold text-gray-900" style="font-family:'Poppins',sans-serif;">{{ __('messages.receipt') }} — #<span x-text="orderId"></span></h3>
                    <button @click="close()" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-200 rounded-lg transition-colors" aria-label="Close">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                <div class="flex-1 min-h-0 overflow-y-auto p-4" style="background:#eef2f6;" x-html="html"></div>
            <div class="flex items-center gap-3 px-5 py-3 border-t border-gray-100 shrink-0 bg-gray-50">
                <button @click="print()" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-emerald-500 hover:bg-emerald-600 rounded-xl transition-colors">
                    <i data-lucide="printer" class="w-4 h-4"></i> {{ __('messages.print') }}
                </button>
                <button @click="close()" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 rounded-xl transition-colors">
                    {{ __('messages.close') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function openOrderReceipt(orderId) {
        window.dispatchEvent(new CustomEvent('open-receipt', { detail: { id: orderId } }));
    }

    function receiptModal() {
        return {
            show: false,
            orderId: null,
            html: '',

            init() {
                window.addEventListener('open-receipt', (e) => {
                    const id = e.detail.id;
                    const el = document.getElementById('receipt-' + id);
                    if (!el) return;
                    this.orderId = id;
                    this.html = el.innerHTML;
                    this.show = true;
                    document.body.style.overflow = 'hidden';
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                });
            },

            close() {
                this.show = false;
                document.body.style.overflow = '';
            },

            print() {
                if (!this.html || !this.orderId) return;
                const styles = document.getElementById('receipt-style');
                const css = styles ? styles.textContent : '';
                const win = window.open('', '_blank', 'width=820,height=900');
                if (!win) return;
                // White body: the shared stylesheet whitens .rd-page/.rd-body
                // for @media print, but this pop-up body has neither class.
                win.document.write(
                    '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">' +
                    '<title>Order #' + this.orderId + ' - M-Delight Singer Stores</title>' +
                    '<style>html,body{margin:0;padding:0;background:#ffffff;}' +
                    'body{padding:16px;-webkit-print-color-adjust:exact;print-color-adjust:exact;}' +
                    css + '</style></head><body>' + this.html + '</body></html>'
                );
                win.document.close();
                win.focus();
                setTimeout(() => { win.print(); }, 500);
            },
        };
    }
</script>
