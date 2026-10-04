@extends('layouts.admin')

@section('title', 'Discount Campaigns')
@section('header', 'Discount Campaigns')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-gray-500 min-w-0 break-words">Manage discount campaigns and notify interested customers.</p>
        <a href="{{ route('admin.discount-campaigns.create') }}" class="px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-semibold rounded-xl transition-colors inline-flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            New Campaign
        </a>
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    @include('components.admin-search-filter')

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        @if($campaigns->isEmpty())
            <div class="p-12 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z"/></svg>
                <p class="text-gray-500 font-medium">No discount campaigns yet.</p>
                <p class="text-sm text-gray-400 mt-1">Create your first campaign to start offering discounts.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[800px]">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="text-left px-6 py-3 font-semibold text-gray-600">Name</th>
                            <th class="text-left px-6 py-3 font-semibold text-gray-600">Discount</th>
                            <th class="text-left px-6 py-3 font-semibold text-gray-600">Items</th>
                            <th class="text-left px-6 py-3 font-semibold text-gray-600">Status</th>
                            <th class="text-left px-6 py-3 font-semibold text-gray-600">Dates</th>
                            <th class="text-left px-6 py-3 font-semibold text-gray-600">Notified</th>
                            <th class="text-right px-6 py-3 font-semibold text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($campaigns as $campaign)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 break-words">
                                <a href="{{ route('admin.discount-campaigns.show', $campaign) }}" class="font-medium text-gray-900 hover:text-emerald-600">{{ $campaign->name }}</a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-semibold text-emerald-600">{{ $campaign->discount_percentage }}%</span>
                            </td>
                            <td class="px-6 py-4 text-gray-600 whitespace-nowrap">{{ $campaign->items_count }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($campaign->isCurrentlyActive())
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-green-50 text-green-700 rounded-lg text-xs font-medium">
                                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Active
                                    </span>
                                @elseif($campaign->is_active)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-yellow-50 text-yellow-700 rounded-lg text-xs font-medium">
                                        <span class="w-1.5 h-1.5 bg-yellow-500 rounded-full"></span> Scheduled
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-gray-50 text-gray-500 rounded-lg text-xs font-medium">
                                        <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span> Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-500">
                                @if($campaign->starts_at)
                                    <div>Start: {{ $campaign->starts_at->format('M j, Y') }}</div>
                                @endif
                                @if($campaign->ends_at)
                                    <div>End: {{ $campaign->ends_at->format('M j, Y') }}</div>
                                @endif
                                @if(!$campaign->starts_at && !$campaign->ends_at)
                                    <span class="text-gray-400">No schedule</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-500 whitespace-nowrap">
                                @if($campaign->notified_at)
                                    {{ $campaign->notified_at->diffForHumans() }}
                                @else
                                    <span class="text-gray-400">Never</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.discount-campaigns.show', $campaign) }}" class="p-2 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors" title="View">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>
                                    <a href="{{ route('admin.discount-campaigns.edit', $campaign) }}" class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    <form method="POST" action="{{ route('admin.discount-campaigns.destroy', $campaign) }}" onsubmit="return confirm('Delete this campaign?')" class="inline">
                                        @csrf @method('DELETE')
                                        <button class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $campaigns->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
