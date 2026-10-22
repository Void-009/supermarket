@extends('layouts.admin')

@section('title', 'Dashboard')
@section('header', 'Dashboard')

@section('content')
<div class="space-y-8">

    {{-- Welcome --}}
    <div>
        <h2 class="text-xl sm:text-2xl font-bold text-gray-900 break-words" style="font-family:'Poppins',sans-serif;">Welcome back, {{ Auth::user()->name ?? 'Admin' }}</h2>
        <p class="text-sm text-gray-500 mt-1">Here's what's happening with your store today.</p>
    </div>

    {{-- POS Agent Connection Status --}}
    @php
        $agentOnline = $agentStatus && $agentStatus->last_seen_at && $agentStatus->last_seen_at->gte(now()->subMinutes(2));
        $lastSeenLabel = $agentStatus?->last_seen_at ? $agentStatus->last_seen_at->diffForHumans() : 'Never';
        $lastBatchLabel = $lastBatch ? $lastBatch->created_at->format('M d, Y g:i A') : 'Never received';
        $failedBatches24h = \App\Models\PosSyncBatch::where('status', 'failed')
            ->where('created_at', '>=', now()->subHours(24))
            ->count();
    @endphp
    <div x-data="posAgentStatus()" x-init="init()" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <span class="relative flex h-3 w-3 flex-shrink-0">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75" :class="online ? 'bg-emerald-400' : 'bg-red-400'"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3" :class="online ? 'bg-emerald-500' : 'bg-red-500'"></span>
                </span>
                <div>
                    <p class="text-sm font-semibold" :class="online ? 'text-emerald-700' : 'text-red-600'">
                        POS Agent: <span x-text="online ? 'ONLINE' : 'OFFLINE'"></span>
                    </p>
                    <p class="text-xs text-gray-500">
                        Last seen: <span x-text="lastSeen"></span>
                        <template x-if="version"><span x-text="' (v' + version + ')'"></span></template>
                        &middot; Last batch: <span x-text="lastBatch"></span>
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <template x-if="failedBatches > 0">
                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-red-600 bg-red-50 border border-red-100 rounded-lg px-3 py-2">
                        <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>
                        <span x-text="failedBatches + ' failed batch(es) in the last 24h'"></span>
                    </span>
                </template>
                <a :href="syncUrl" class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-2 hover:bg-emerald-100 transition-colors">
                    <i data-lucide="radio" class="w-3.5 h-3.5"></i> View POS Sync
                </a>
            </div>
        </div>
    </div>

    {{-- Stat Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <a href="{{ route('admin.items.index') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 block hover:border-emerald-300 hover:shadow-md transition-all min-h-[110px]">
            <div class="flex items-center justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-500">Total Items</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-900 mt-1 tabular-nums leading-tight fit-value">{{ number_format($totalItems) }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i data-lucide="package" class="w-6 h-6 text-blue-600"></i>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.orders.index') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 block hover:border-emerald-300 hover:shadow-md transition-all min-h-[110px]">
            <div class="flex items-center justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-500">Total Orders</p>
                    <p id="total-orders-count" class="text-xl sm:text-2xl font-bold text-gray-900 mt-1 tabular-nums leading-tight fit-value">{{ number_format($totalOrders) }}</p>
                </div>
                <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i data-lucide="shopping-cart" class="w-6 h-6 text-emerald-600"></i>
                </div>
            </div>
        </a>

        @if(Auth::user()->hasPermission('view_revenue'))
        <a href="{{ route('admin.revenue.index') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 block hover:border-emerald-300 hover:shadow-md transition-all min-h-[110px]">
            <div class="flex items-center justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-500">Total Revenue</p>
                    <p id="total-revenue-amount" class="text-xl sm:text-2xl font-bold text-gray-900 mt-1 tabular-nums leading-tight fit-value">₦{{ number_format($totalRevenue, 2) }}</p>
                </div>
                <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i data-lucide="banknote" class="w-6 h-6 text-emerald-600"></i>
                </div>
            </div>
        </a>
        @endif

        <a href="{{ route('admin.orders.index', ['status' => 'new']) }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 block hover:border-emerald-300 hover:shadow-md transition-all min-h-[110px]">
            <div class="flex items-center justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-500">Pending Orders</p>
                    <p id="pending-orders-count" class="text-xl sm:text-2xl font-bold text-gray-900 mt-1 tabular-nums leading-tight fit-value">{{ number_format($pendingOrders) }}</p>
                </div>
                <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i data-lucide="clock" class="w-6 h-6 text-emerald-600"></i>
                </div>
            </div>
        </a>
    </div>

    {{-- Items missing images --}}
    @if(($missingImageCount ?? 0) > 0 && (Auth::user()->hasPermission('modify_items') || Auth::user()->isSuperadmin() || Auth::user()->isAdmin()))
    <a href="{{ route('admin.items.missing-images') }}" class="group flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-amber-50 border border-amber-200 rounded-xl px-5 py-4 hover:bg-amber-100 transition-colors">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center flex-shrink-0">
                <i data-lucide="image-off" class="w-5 h-5 text-amber-600"></i>
            </div>
            <div>
                <p class="text-sm font-semibold text-amber-900">{{ number_format($missingImageCount) }} {{ \Illuminate\Support\Str::plural('item', $missingImageCount) }} without an image</p>
                <p class="text-xs text-amber-700 mt-0.5">Add photos so customers know exactly what they are ordering.</p>
            </div>
        </div>
        <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-amber-800 group-hover:text-amber-900 whitespace-nowrap">
            Review items <i data-lucide="arrow-right" class="w-4 h-4"></i>
        </span>
    </a>
    @endif

    {{-- Recent Orders --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-6 py-5 border-b border-gray-100">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900" style="font-family:'Poppins',sans-serif;">Recent Orders</h3>
                <a href="{{ route('admin.orders.index') }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700 transition-colors">View all</a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[600px]">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="text-left px-6 py-3 font-medium text-gray-500">Order ID</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-500">Customer</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-500">Amount</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-500">Status</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-500">Date</th>
                        <th class="text-right px-6 py-3 font-medium text-gray-500">Action</th>
                    </tr>
                </thead>
                <tbody id="recent-orders-body" class="divide-y divide-gray-50">
                    @include('admin.partials.recent-orders-rows', ['recentOrders' => $recentOrders])
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function posAgentStatus() {
        return {
            online: @json($agentOnline),
            lastSeen: @json($lastSeenLabel),
            version: @json($agentStatus?->version),
            lastBatch: @json($lastBatchLabel),
            failedBatches: @json($failedBatches24h),
            syncUrl: '{{ route('admin.pos-sync.index') }}',
            _timer: null,
            init() {
                this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
                this.poll();
                this._timer = setInterval(() => this.poll(), 30000);
            },
            destroy() {
                if (this._timer) clearInterval(this._timer);
            },
            async poll() {
                try {
                    const res = await fetch('{{ route('admin.pos-sync.status') }}', { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) return;
                    const data = await res.json();
                    this.online = !!data.agent_online;
                    this.version = data.version || null;
                    this.lastSeen = data.last_seen_at ? this.formatDate(data.last_seen_at) : 'Never';
                    this.lastBatch = data.last_batch ? this.formatDate(data.last_batch.created_at) : 'Never received';
                    this.failedBatches = data.failed_batches_24h || 0;
                } catch (e) {}
            },
            formatDate(iso) {
                if (!iso) return 'Never';
                const d = new Date(iso);
                return d.toLocaleString(undefined, { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
            }
        };
    }

    function fitCardValues() {
        document.querySelectorAll('.fit-value').forEach(function (el) {
            el.style.whiteSpace = 'nowrap';
            el.style.fontSize = '';
            var parent = el.parentElement;
            if (!parent) return;
            var max = parent.clientWidth;
            var size = parseFloat(window.getComputedStyle(el).fontSize) || 24;
            var min = 11;
            while (el.scrollWidth > max && size > min) {
                size -= 0.5;
                el.style.fontSize = size + 'px';
            }
        });
    }
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
        fitCardValues();
        window.addEventListener('resize', fitCardValues);
        setTimeout(fitCardValues, 300);
    });

    // Live new-order feed. Uses Echo for instant updates and a light polling
    // fallback so the page still updates when Reverb or the queue worker is
    // not running.
    (function () {
        const endpoint = '{{ route('admin.dashboard.live') }}';
        let latestId = {{ (int) ($latestId ?? 0) }};
        let lastFingerprint = null;
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

        function setText(id, value) {
            const el = document.getElementById(id);
            if (!el || el.textContent === value) return;
            el.textContent = value;
            if (typeof fitCardValues === 'function') fitCardValues();
        }

        function notify(order) {
            if (!order) return;
            if (typeof showToast === 'function') {
                showToast('New order #' + order.id + ' from ' + (order.customer_name || 'a customer'), 'success');
            }
            playSound();
        }

        function render(data) {
            const tbody = document.getElementById('recent-orders-body');
            if (!tbody) return;

            const fingerprint = [
                data.latest_id,
                data.total_orders,
                data.pending_orders,
                data.recent_version,
            ].join('|');
            if (fingerprint === lastFingerprint) return;
            lastFingerprint = fingerprint;

            tbody.innerHTML = data.recent_html || '';
            if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                window.Alpine.initTree(tbody);
            }
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        async function refresh(notifyNew) {
            if (refreshing || document.hidden) return;
            refreshing = true;
            try {
                const res = await fetch(endpoint, {
                    headers: { 'Accept': 'application/json' },
                    cache: 'no-store',
                });
                if (!res.ok) return;
                const data = await res.json();

                setText('total-orders-count', Number(data.total_orders).toLocaleString());
                setText('pending-orders-count', Number(data.pending_orders).toLocaleString());
                setText('total-revenue-amount', '₦' + Number(data.total_revenue).toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                }));
                render(data);

                if (Number(data.latest_id) > latestId) {
                    latestId = Number(data.latest_id);
                    if (notifyNew) notify(data.latest_order);
                }
            } catch (e) {
                // Silent: polling will try again on the next tick.
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

        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) refresh(false);
        });

        refresh(false);
        hookEcho();
        setInterval(function () { refresh(true); }, 5000);
    })();
</script>
@endsection
