@extends('layouts.admin')

@section('title', 'Orders')
@section('header', 'Orders')

@section('content')
<div x-data="bulkManager()">
    {{-- Page Header --}}
    <div class="mb-6">
        <h2 class="text-xl sm:text-2xl font-bold text-gray-900" style="font-family:'Poppins',sans-serif;">Orders</h2>
        <p class="text-sm text-gray-500 mt-1">Manage customer orders.</p>
    </div>

    @include('components.admin-search-filter')

    {{-- Status Filter Tabs --}}
    <div class="flex flex-wrap gap-2 mb-6">
        @php
            $tabs = [
                '' => 'All',
                'new' => 'New',
                'processing' => 'Processing',
                'paid' => 'Paid',
                'shipped' => 'Shipped',
                'delivered' => 'Delivered',
                'cancelled' => 'Cancelled',
            ];
        @endphp
        @foreach($tabs as $key => $label)
            @php
                $active = request('status') === $key;
                $count = $key === '' ? $totalAll : ($counts[$key] ?? 0);
            @endphp
            <a href="{{ route('admin.orders.index', $key ? ['status' => $key] : []) }}"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 text-sm font-medium rounded-xl transition-colors {{ $active ? 'bg-emerald-500 text-white shadow-sm' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
                {{ $label }}
                <span id="order-count-{{ $key === '' ? 'all' : $key }}" class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-semibold rounded-full {{ $active ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500' }}">{{ $count }}</span>
            </a>
        @endforeach
    </div>

    {{-- Bulk Actions Bar --}}
    <div x-show="selectedIds.length > 0" x-cloak x-transition
         class="mb-4 bg-emerald-50 border border-emerald-200 rounded-xl px-5 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="text-sm font-semibold text-emerald-700" x-text="selectedIds.length + ' order(s) selected'"></span>
            <button @click="selectedIds = []; allSelected = false" class="text-xs text-emerald-600 hover:text-emerald-800 underline font-medium">Deselect All</button>
        </div>
        <div class="flex items-center gap-2">
            <button @click="bulkAction('cancel')" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-amber-700 bg-amber-100 hover:bg-amber-200 rounded-lg transition-colors">
                <i data-lucide="x-circle" class="w-3.5 h-3.5"></i> Cancel Selected
            </button>
            <button @click="bulkAction('delete')" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-red-700 bg-red-100 hover:bg-red-200 rounded-lg transition-colors">
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Delete Selected
            </button>
        </div>
    </div>

    {{-- Orders Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[900px]">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-2.5 py-3 w-10">
                            <input type="checkbox" @change="toggleAll($event)" :checked="allSelected" class="w-4 h-4 text-emerald-500 border-gray-300 rounded focus:ring-emerald-500 cursor-pointer">
                        </th>
                        <th class="px-2.5 py-3 text-left font-medium text-gray-500">Order</th>
                        <th class="px-2.5 py-3 text-left font-medium text-gray-500">Customer</th>
                        <th class="px-2.5 py-3 text-left font-medium text-gray-500">Phone</th>
                        <th class="px-2.5 py-3 text-left font-medium text-gray-500">Items</th>
                        <th class="px-2.5 py-3 text-left font-medium text-gray-500">Total</th>
                        <th class="px-2.5 py-3 text-left font-medium text-gray-500">Status</th>
                        <th class="px-2.5 py-3 text-left font-medium text-gray-500">Processed By</th>
                        <th class="px-2.5 py-3 text-left font-medium text-gray-500">Date</th>
                        <th class="px-2.5 py-3 text-center font-medium text-gray-500">Action</th>
                    </tr>
                </thead>
                <tbody id="orders-body" class="divide-y divide-gray-50">
                    @include('admin.orders._rows', ['orders' => $orders])
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
        <div class="border-t border-gray-100 px-6 py-4">
            {{ $orders->links('components.pagination') }}
        </div>
        @endif
    </div>
</div>

<script>
function bulkManager() {
    return {
        selectedIds: [],
        allSelected: false,
        csrfToken: document.querySelector('meta[name="csrf-token"]').content,

        toggleAll(e) {
            if (e.target.checked) {
                this.selectedIds = (window.__orderPageIds || @js($orders->pluck('id')->toArray())).slice();
                this.allSelected = true;
            } else {
                this.selectedIds = [];
                this.allSelected = false;
            }
        },

        syncAllSelected() {
            const ids = window.__orderPageIds || [];
            this.allSelected = ids.length > 0
                && ids.every((id) => this.selectedIds.map(Number).includes(Number(id)));
        },

        async bulkAction(action) {
            if (!confirm(action === 'delete' ? 'Delete selected orders? This cannot be undone.' : 'Cancel selected orders?')) return;
            try {
                const res = await fetch('{{ route("admin.orders.bulk") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ action, ids: this.selectedIds }),
                });
                const data = await res.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'An error occurred.');
                }
            } catch (e) {
                alert('An error occurred. Please try again.');
            }
        },
    };
}
</script>

<script>
    // Keep the orders table live: listen on the admin.orders channel and also
    // poll, so new orders appear even when the websocket server or the queue
    // worker is unavailable.
    (function () {
        const endpoint = '{{ route('admin.orders.live') }}';
        const statusFilter = @json((string) request('status', ''));
        const searchFilter = @json((string) request('search', ''));
        const currentPage = {{ (int) request('page', 1) }};
        let latestId = {{ (int) $latestId }};
        let lastFingerprint = null;
        let initialized = false;
        let refreshing = false;

        let audioCtx = null;
        function playSound() {
            try {
                const Ctx = window.AudioContext || window.webkitAudioContext;
                if (!Ctx) return;
                if (!audioCtx) audioCtx = new Ctx();
                if (audioCtx.state === 'suspended') audioCtx.resume();
                const now = audioCtx.currentTime;
                [0, 0.18].forEach(function (offset, i) {
                    const osc = audioCtx.createOscillator();
                    const gain = audioCtx.createGain();
                    osc.type = 'sine';
                    osc.frequency.value = i === 0 ? 880 : 1320;
                    gain.gain.setValueAtTime(0.0001, now + offset);
                    gain.gain.exponentialRampToValueAtTime(0.35, now + offset + 0.02);
                    gain.gain.exponentialRampToValueAtTime(0.0001, now + offset + 0.16);
                    osc.connect(gain).connect(audioCtx.destination);
                    osc.start(now + offset);
                    osc.stop(now + offset + 0.18);
                });
            } catch (e) {}
        }

        function updateCounts(data) {
            const values = Object.assign({ all: data.total_all }, data.counts || {});
            Object.keys(values).forEach(function (key) {
                const el = document.getElementById('order-count-' + key);
                if (!el) return;
                const next = Number(values[key]).toLocaleString();
                if (el.textContent !== next) el.textContent = next;
            });
        }

        function refreshPageIds() {
            window.__orderPageIds = Array.from(
                document.querySelectorAll('#orders-body input[type="checkbox"]')
            ).map(function (cb) { return parseInt(cb.value, 10); })
             .filter(function (id) { return !Number.isNaN(id); });
        }

        function fingerprint(data) {
            const counts = Object.keys(data.counts || {}).sort().map(function (key) {
                return key + ':' + data.counts[key];
            }).join(',');
            return [data.latest_id, data.total_all, counts, data.rows_version].join('|');
        }

        function renderRows(data) {
            const tbody = document.getElementById('orders-body');
            if (!tbody) return;

            const nextFingerprint = fingerprint(data);

            if (initialized && nextFingerprint === lastFingerprint) {
                refreshPageIds();
                return;
            }

            // Never wipe the table while someone is ticking rows.
            if (tbody.querySelector('input[type="checkbox"]:checked')) return;

            initialized = true;
            lastFingerprint = nextFingerprint;
            tbody.innerHTML = data.rows_html || '';

            if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                window.Alpine.initTree(tbody);
            }
            if (typeof lucide !== 'undefined') lucide.createIcons();
            refreshPageIds();
        }

        function notify(order) {
            if (!order) return;
            if (typeof showToast === 'function') {
                showToast('New order #' + order.id + ' from ' + (order.customer_name || 'a customer'), 'success');
            }
            playSound();
        }

        async function refresh(notifyNew) {
            if (refreshing || document.hidden) return;
            refreshing = true;
            try {
                const params = new URLSearchParams();
                if (statusFilter) params.set('status', statusFilter);
                if (searchFilter) params.set('search', searchFilter);
                if (currentPage > 1) params.set('page', currentPage);

                const url = endpoint + (params.toString() ? '?' + params.toString() : '');
                const res = await fetch(url, {
                    headers: { 'Accept': 'application/json' },
                    cache: 'no-store',
                });
                if (!res.ok) return;
                const data = await res.json();

                updateCounts(data);
                renderRows(data);

                if (Number(data.latest_id) > latestId) {
                    latestId = Number(data.latest_id);
                    if (notifyNew) notify(data.latest_order);
                }
            } catch (e) {
                // Silent: polling will retry.
            } finally {
                refreshing = false;
            }
        }

        function hookEcho() {
            if (typeof window.Echo === 'undefined') {
                setTimeout(hookEcho, 500);
                return;
            }

            window.Echo.private('admin.orders').listen('.OrderCreated', function (e) {
                const order = e.order || e;
                const id = Number(order.id) || 0;
                if (id > latestId) {
                    latestId = id;
                    notify(order);
                }
                refresh(false);
            });
        }

        function start() {
            refreshPageIds();
            refresh(false);
            hookEcho();
            setInterval(function () { refresh(true); }, 5000);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', start);
        } else {
            start();
        }

        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) refresh(false);
        });
    })();

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>
@endsection
