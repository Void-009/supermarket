@forelse($orders as $order)
    @include('admin.orders._row', ['order' => $order])
@empty
    <tr id="orders-empty">
        <td colspan="10" class="px-6 py-12 text-center">
            <i data-lucide="receipt" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
            <p class="text-sm text-gray-500">No orders found.</p>
        </td>
    </tr>
@endforelse
