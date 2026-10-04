<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SyncLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    private function authorizeSync(): void
    {
        abort_unless(auth()->user()->hasPermission('view_sync'), 403, 'You do not have permission to view sync.');
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeSync();

        $logs = SyncLog::when($request->filled('search'), function ($q) use ($request) {
            $search = trim($request->input('search'));
            $q->where(function ($b) use ($search) {
                $b->where('file_name', 'like', "%{$search}%")
                    ->orWhere('mode', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhere('error_message', 'like', "%{$search}%");
                $id = preg_replace('/[^0-9]/', '', $search);
                if ($id !== '') {
                    $b->orWhere('id', (int) $id);
                }
            });
        })->latest()->paginate(20);

        $inProgressLog = SyncLog::where('status', 'in_progress')
            ->orWhere('status', 'cancelling')
            ->latest()
            ->first();

        return response()->json([
            'success' => true,
            'logs' => collect($logs->items())->map(fn (SyncLog $log) => $this->presentLog($log)),
            'in_progress' => $inProgressLog ? $this->presentLog($inProgressLog) : null,
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    public function trigger(Request $request): JsonResponse
    {
        $this->authorizeSync();

        $mode = $request->input('mode', 'all');
        $validModes = ['all', 'price', 'qty', 'new'];

        if (! in_array($mode, $validModes)) {
            return response()->json(['success' => false, 'message' => "Invalid sync mode: {$mode}"], 422);
        }

        if (SyncLog::where('status', 'in_progress')->exists()) {
            return response()->json(['success' => false, 'message' => 'A sync is already running. Please wait for it to finish.'], 409);
        }

        $log = SyncLog::create([
            'file_name' => basename((string) config('pos.export_path')),
            'mode' => $mode,
            'status' => 'in_progress',
        ]);

        \App\Jobs\RunPosSyncArtisan::dispatch($log->id, $mode)->onQueue('pos-sync');

        $modeLabels = [
            'all' => 'Full sync',
            'price' => 'Price sync',
            'qty' => 'Quantity sync',
            'new' => 'New items sync',
        ];

        return response()->json([
            'success' => true,
            'message' => "{$modeLabels[$mode]} started in the background. Refresh to see progress.",
            'log_id' => $log->id,
        ]);
    }

    public function status(SyncLog $log): JsonResponse
    {
        $this->authorizeSync();

        $log->refresh();

        return response()->json([
            'success' => true,
            'log' => $this->presentLog($log),
        ]);
    }

    public function cancel(SyncLog $log): JsonResponse
    {
        $this->authorizeSync();

        if ($log->status !== 'in_progress') {
            return response()->json(['success' => false, 'message' => 'Sync is not running.'], 422);
        }

        $log->update(['status' => 'cancelling']);

        return response()->json(['success' => true, 'message' => 'Cancellation requested. The sync will stop shortly.']);
    }

    private function presentLog(SyncLog $log): array
    {
        return [
            'id' => $log->id,
            'file_name' => $log->file_name,
            'mode' => $log->mode,
            'status' => $log->status,
            'rows_total' => $log->rows_total,
            'rows_processed' => $log->rows_processed,
            'rows_created' => $log->rows_created,
            'rows_updated' => $log->rows_updated,
            'rows_failed' => $log->rows_failed,
            'progress_percent' => $log->progress_percent,
            'error_message' => $log->error_message,
            'cancelled_at' => $log->cancelled_at?->toISOString(),
            'created_at' => $log->created_at?->toISOString(),
            'updated_at' => $log->updated_at?->toISOString(),
        ];
    }
}
