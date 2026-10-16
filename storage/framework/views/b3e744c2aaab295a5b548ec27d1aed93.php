<?php $__env->startSection('title', 'Dashboard'); ?>
<?php $__env->startSection('header', 'Dashboard'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-8">

    
    <div>
        <h2 class="text-xl sm:text-2xl font-bold text-gray-900 break-words" style="font-family:'Poppins',sans-serif;">Welcome back, <?php echo e(Auth::user()->name ?? 'Admin'); ?></h2>
        <p class="text-sm text-gray-500 mt-1">Here's what's happening with your store today.</p>
    </div>

    
    <?php
        $agentOnline = $agentStatus && $agentStatus->last_seen_at && $agentStatus->last_seen_at->gte(now()->subMinutes(2));
        $lastSeenLabel = $agentStatus?->last_seen_at ? $agentStatus->last_seen_at->diffForHumans() : 'Never';
        $lastBatchLabel = $lastBatch ? $lastBatch->created_at->format('M d, Y g:i A') : 'Never received';
        $failedBatches24h = \App\Models\PosSyncBatch::where('status', 'failed')
            ->where('created_at', '>=', now()->subHours(24))
            ->count();
    ?>
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

    
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <a href="<?php echo e(route('admin.items.index')); ?>" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 block hover:border-emerald-300 hover:shadow-md transition-all min-h-[110px]">
            <div class="flex items-center justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-500">Total Items</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-900 mt-1 tabular-nums leading-tight fit-value"><?php echo e(number_format($totalItems)); ?></p>
                </div>
                <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i data-lucide="package" class="w-6 h-6 text-blue-600"></i>
                </div>
            </div>
        </a>

        <a href="<?php echo e(route('admin.orders.index')); ?>" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 block hover:border-emerald-300 hover:shadow-md transition-all min-h-[110px]">
            <div class="flex items-center justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-500">Total Orders</p>
                    <p id="total-orders-count" class="text-xl sm:text-2xl font-bold text-gray-900 mt-1 tabular-nums leading-tight fit-value"><?php echo e(number_format($totalOrders)); ?></p>
                </div>
                <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i data-lucide="shopping-cart" class="w-6 h-6 text-emerald-600"></i>
                </div>
            </div>
        </a>

        <?php if(Auth::user()->hasPermission('view_revenue')): ?>
        <a href="<?php echo e(route('admin.revenue.index')); ?>" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 block hover:border-emerald-300 hover:shadow-md transition-all min-h-[110px]">
            <div class="flex items-center justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-500">Total Revenue</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-900 mt-1 tabular-nums leading-tight fit-value">₦<?php echo e(number_format($totalRevenue, 2)); ?></p>
                </div>
                <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i data-lucide="banknote" class="w-6 h-6 text-emerald-600"></i>
                </div>
            </div>
        </a>
        <?php endif; ?>

        <a href="<?php echo e(route('admin.orders.index', ['status' => 'new'])); ?>" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 block hover:border-emerald-300 hover:shadow-md transition-all min-h-[110px]">
            <div class="flex items-center justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-500">Pending Orders</p>
                    <p id="pending-orders-count" class="text-xl sm:text-2xl font-bold text-gray-900 mt-1 tabular-nums leading-tight fit-value"><?php echo e(number_format($pendingOrders)); ?></p>
                </div>
                <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i data-lucide="clock" class="w-6 h-6 text-emerald-600"></i>
                </div>
            </div>
        </a>
    </div>

    
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-6 py-5 border-b border-gray-100">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900" style="font-family:'Poppins',sans-serif;">Recent Orders</h3>
                <a href="<?php echo e(route('admin.orders.index')); ?>" class="text-sm font-medium text-emerald-600 hover:text-emerald-700 transition-colors">View all</a>
            </div>
        </div>

        <?php if($recentOrders->count()): ?>
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
                    <?php $__currentLoopData = $recentOrders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">#<?php echo e($order->id); ?></td>
                        <td class="px-6 py-4 text-gray-700 max-w-[160px] break-words"><?php echo e($order->customer_name ?? 'N/A'); ?></td>
                        <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">₦<?php echo e(number_format($order->total_amount, 2)); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                                $statusColors = [
                                    'new' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'processing' => 'bg-yellow-50 text-yellow-700 border-yellow-200',
                                    'paid' => 'bg-green-50 text-green-700 border-green-200',
                                    'shipped' => 'bg-purple-50 text-purple-700 border-purple-200',
                                    'delivered' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'cancelled' => 'bg-red-50 text-red-700 border-red-200',
                                ];
                                $colorClass = $statusColors[$order->status] ?? 'bg-gray-50 text-gray-700 border-gray-200';
                            ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border <?php echo e($colorClass); ?>">
                                <?php echo e(ucfirst($order->status)); ?>

                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-500 whitespace-nowrap"><?php echo e($order->created_at->format('M d, Y')); ?></td>
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            <a href="<?php echo e(route('admin.orders.show', $order)); ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition-colors border border-emerald-200">
                                <i data-lucide="eye" class="w-3.5 h-3.5"></i> View
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div id="no-orders-msg" class="px-6 py-12 text-center">
            <i data-lucide="inbox" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
            <p class="text-sm text-gray-500">No orders yet.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
    function posAgentStatus() {
        return {
            online: <?php echo json_encode($agentOnline, 15, 512) ?>,
            lastSeen: <?php echo json_encode($lastSeenLabel, 15, 512) ?>,
            version: <?php echo json_encode($agentStatus?->version, 15, 512) ?>,
            lastBatch: <?php echo json_encode($lastBatchLabel, 15, 512) ?>,
            failedBatches: <?php echo json_encode($failedBatches24h, 15, 512) ?>,
            syncUrl: '<?php echo e(route('admin.pos-sync.index')); ?>',
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
                    const res = await fetch('<?php echo e(route('admin.pos-sync.status')); ?>', { headers: { 'Accept': 'application/json' } });
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

    // Live new-order feed: prepend to Recent Orders, bump counters, toast + sound.
    (function () {
        const statusColors = {
            'new': 'bg-blue-50 text-blue-700 border-blue-200',
            'processing': 'bg-yellow-50 text-yellow-700 border-yellow-200',
            'paid': 'bg-green-50 text-green-700 border-green-200',
            'shipped': 'bg-purple-50 text-purple-700 border-purple-200',
            'delivered': 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'cancelled': 'bg-red-50 text-red-700 border-red-200',
        };

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

        function bump(id) {
            const el = document.getElementById(id);
            if (!el) return;
            const n = parseInt(el.textContent.replace(/[^0-9]/g, ''), 10) || 0;
            el.textContent = (n + 1).toLocaleString();
            if (typeof fitCardValues === 'function') fitCardValues();
        }

        function rowHtml(o) {
            const color = statusColors[o.status] || 'bg-gray-50 text-gray-700 border-gray-200';
            const date = new Date(o.created_at).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
            return '<tr class="hover:bg-gray-50/50 transition-colors bg-emerald-50/40">'
                + '<td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">#' + o.id + '</td>'
                + '<td class="px-6 py-4 text-gray-700 max-w-[160px] break-words">' + (o.customer_name || 'N/A') + '</td>'
                + '<td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">&#8358;' + Number(o.total_amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</td>'
                + '<td class="px-6 py-4 whitespace-nowrap"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border ' + color + '">' + o.status.charAt(0).toUpperCase() + o.status.slice(1) + '</span></td>'
                + '<td class="px-6 py-4 text-gray-500 whitespace-nowrap">' + date + '</td>'
                + '<td class="px-6 py-4 text-right whitespace-nowrap"><a href="' + (o.url || '/admin/orders/' + o.id) + '" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition-colors border border-emerald-200">View</a></td>'
                + '</tr>';
        }

        function insertRow(o) {
            let tbody = document.getElementById('recent-orders-body');
            if (!tbody) {
                const empty = document.getElementById('no-orders-msg');
                if (empty) {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'overflow-x-auto';
                    wrapper.innerHTML = '<table class="w-full text-sm min-w-[600px]"><thead><tr class="border-b border-gray-100">'
                        + '<th class="text-left px-6 py-3 font-medium text-gray-500">Order ID</th>'
                        + '<th class="text-left px-6 py-3 font-medium text-gray-500">Customer</th>'
                        + '<th class="text-left px-6 py-3 font-medium text-gray-500">Amount</th>'
                        + '<th class="text-left px-6 py-3 font-medium text-gray-500">Status</th>'
                        + '<th class="text-left px-6 py-3 font-medium text-gray-500">Date</th>'
                        + '<th class="text-right px-6 py-3 font-medium text-gray-500">Action</th>'
                        + '</tr></thead><tbody id="recent-orders-body" class="divide-y divide-gray-50"></tbody></table>';
                    empty.replaceWith(wrapper);
                    tbody = document.getElementById('recent-orders-body');
                }
            }
            if (tbody) {
                tbody.insertAdjacentHTML('afterbegin', rowHtml(o));
                while (tbody.rows.length > 10) tbody.deleteRow(tbody.rows.length - 1);
            }
        }

        function hook() {
            if (typeof window.Echo === 'undefined') { setTimeout(hook, 500); return; }
            window.Echo.private('admin.orders').listen('.OrderCreated', function (e) {
                const o = e.order || e;
                insertRow(o);
                bump('total-orders-count');
                if ((o.status || 'new') === 'new') bump('pending-orders-count');
                if (typeof showToast === 'function') showToast('New order #' + o.id + ' from ' + (o.customer_name || 'a customer'), 'success');
                playSound();
            });
        }
        hook();
    })();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Smk\Desktop\supermarket\resources\views/admin/dashboard.blade.php ENDPATH**/ ?>