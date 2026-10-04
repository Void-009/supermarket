<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RunPosSyncArtisan;
use App\Models\SyncLog;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermission('view_sync')) {
            abort(403, 'You do not have permission to view sync.');
        }

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
        })->latest()->paginate(20)->withQueryString();
        $inProgressLog = SyncLog::where('status', 'in_progress')
            ->orWhere('status', 'cancelling')
            ->latest()
            ->first();

        return view('admin.sync.index', compact('logs', 'inProgressLog'));
    }

    public function trigger(Request $request)
    {
        if (!auth()->user()->hasPermission('view_sync')) {
            abort(403, 'You do not have permission to trigger sync.');
        }

        $mode = $request->input('mode', 'all');
        $validModes = ['all', 'price', 'qty', 'new'];

        if (!in_array($mode, $validModes)) {
            $message = "Invalid sync mode: {$mode}";
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return redirect()->route('admin.sync.index')->with('error', $message);
        }

        $inProgress = SyncLog::where('status', 'in_progress')->exists();
        if ($inProgress) {
            $message = 'A sync is already running. Please wait for it to finish.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 409);
            }
            return redirect()->route('admin.sync.index')->with('error', $message);
        }

        $log = SyncLog::create([
            'file_name' => basename((string) config('pos.export_path')),
            'mode' => $mode,
            'status' => 'in_progress',
        ]);

        RunPosSyncArtisan::dispatch($log->id, $mode)->onQueue('pos-sync');

        $modeLabels = [
            'all'  => 'Full sync',
            'price' => 'Price sync',
            'qty'   => 'Quantity sync',
            'new'   => 'New items sync',
        ];

        $message = "{$modeLabels[$mode]} started in the background. Refresh to see progress.";

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'log_id' => $log->id]);
        }

        return redirect()->route('admin.sync.index')->with('success', $message);
    }

    public function cancel(Request $request, SyncLog $log)
    {
        if (!auth()->user()->hasPermission('view_sync')) {
            abort(403);
        }

        if ($log->status !== 'in_progress') {
            return response()->json(['success' => false, 'message' => 'Sync is not running.'], 422);
        }

        $log->update(['status' => 'cancelling']);

        return response()->json(['success' => true, 'message' => 'Cancellation requested. The sync will stop shortly.']);
    }

    public function status(Request $request, SyncLog $log)
    {
        if (!auth()->user()->hasPermission('view_sync')) {
            abort(403);
        }

        $log->refresh();

        return response()->json([
            'id' => $log->id,
            'status' => $log->status,
            'mode' => $log->mode,
            'rows_total' => $log->rows_total,
            'rows_processed' => $log->rows_processed,
            'rows_created' => $log->rows_created,
            'rows_updated' => $log->rows_updated,
            'rows_failed' => $log->rows_failed,
            'progress' => $log->progress_percent,
            'error_message' => $log->error_message,
            'error_details' => $log->error_details,
            'cancelled_at' => $log->cancelled_at?->toISOString(),
            'created_at' => $log->created_at->toISOString(),
            'updated_at' => $log->updated_at->toISOString(),
        ]);
    }

    public function failures(Request $request, SyncLog $log)
    {
        if (!auth()->user()->hasPermission('view_sync')) {
            abort(403);
        }

        $log->refresh();

        return response()->json([
            'id' => $log->id,
            'status' => $log->status,
            'error_message' => $log->error_message,
            'error_details' => $log->error_details ?? [],
        ]);
    }

    public function bulk(Request $request)
    {
        if (!auth()->user()->hasPermission('view_sync')) {
            abort(403, 'You do not have permission to manage sync logs.');
        }

        $validated = $request->validate([
            'action' => 'required|in:delete',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:sync_logs,id',
        ]);

        $count = SyncLog::whereIn('id', $validated['ids'])
            ->where('status', '!=', 'in_progress')
            ->where('status', '!=', 'cancelling')
            ->delete();

        return response()->json([
            'success' => true,
            'message' => "{$count} sync log(s) deleted successfully.",
        ]);
    }
}
