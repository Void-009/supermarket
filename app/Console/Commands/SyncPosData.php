<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Models\SyncLog;
use App\Services\PosSyncService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class SyncChunkFilter implements IReadFilter
{
    private int $startRow = 0;

    private int $endRow = 0;

    private bool $readAll = false;

    private array $neededCols;

    public function __construct(array $neededCols)
    {
        $this->neededCols = array_map('strtoupper', $neededCols);
    }

    public function setRows(int $start, int $chunkSize): void
    {
        $this->startRow = $start;
        $this->endRow = $start + $chunkSize - 1;
    }

    public function readAll(): void
    {
        $this->readAll = true;
    }

    public function readCell($columnAddress, $row, $worksheetName = ''): bool
    {
        if ($row === 1) {
            return true;
        }
        if (! $this->readAll && ($row < $this->startRow || $row > $this->endRow)) {
            return false;
        }

        return in_array(strtoupper($columnAddress), $this->neededCols);
    }
}

class SyncPosData extends Command
{
    protected $signature = 'pos:sync {--mode=all} {--log-id=}';

    protected $description = 'Synchronize POS item data from Excel exports';

    private const NEEDED_COLS = ['A', 'B', 'C', 'D', 'G', 'P', 'R', 'S', 'U', 'V', 'W', 'X', 'AC', 'AX'];

    public function handle(): int
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);

        $mode = $this->option('mode');
        $validModes = ['all', 'price', 'qty', 'new'];

        if (! in_array($mode, $validModes)) {
            $this->error("Invalid mode: {$mode}. Must be one of: ".implode(', ', $validModes));

            return Command::FAILURE;
        }

        $path = (string) config('pos.export_path');

        $modeLabel = [
            'all' => 'Full sync',
            'price' => 'Price sync',
            'qty' => 'Quantity sync',
            'new' => 'New items sync',
        ];

        $logId = $this->option('log-id');
        $log = $logId ? SyncLog::findOrFail($logId) : SyncLog::create([
            'file_name' => basename($path),
            'mode' => $mode,
            'status' => 'in_progress',
        ]);

        if (! file_exists($path)) {
            $log->update([
                'status' => 'failed',
                'error_message' => "Export file not found: {$path}",
            ]);
            $this->error("Export file not found: {$path}");

            return Command::FAILURE;
        }

        try {
            $service = app(PosSyncService::class);
            $existingItems = Item::select('id', 'pos_item_id')->get()->keyBy('pos_item_id');

            $filter = new SyncChunkFilter(self::NEEDED_COLS);
            $filter->readAll();

            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $reader->setReadFilter($filter);

            $spreadsheet = $reader->load($path);
            $sheet = $spreadsheet->getActiveSheet();
            $totalRows = max(0, $sheet->getHighestRow() - 1);
            $rows = $sheet->toArray(null, true, true, true);

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet, $sheet);
            gc_collect_cycles();

            $log->update(['rows_total' => $totalRows]);
            $this->info("Starting {$modeLabel[$mode]}: {$totalRows} rows to process.");

            $rowsProcessed = 0;
            $rowsCreated = 0;
            $rowsUpdated = 0;
            $rowsFailed = 0;
            $rowsSkipped = 0;
            $errorDetails = [];

            foreach ($rows as $row) {
                $name = trim($row['C'] ?? '');
                if ($name === '' || $name === 'Item Name') {
                    continue;
                }

                if (($rowsProcessed + 1) % 500 === 0) {
                    $log->refresh();
                    if ($log->status === 'cancelling') {
                        $log->update([
                            'status' => 'cancelled',
                            'cancelled_at' => now(),
                            'rows_processed' => $rowsProcessed,
                            'rows_created' => $rowsCreated,
                            'rows_updated' => $rowsUpdated,
                            'rows_failed' => $rowsFailed,
                            'error_details' => $errorDetails ?: null,
                        ]);
                        $this->info("Sync cancelled by user at {$rowsProcessed}/{$totalRows} rows.");

                        return Command::SUCCESS;
                    }
                }

                $rowsProcessed++;

                $posItemId = trim($row['AX'] ?? '');
                if ($posItemId === '') {
                    $posItemId = 'POS-'.$rowsProcessed;
                }

                $exists = isset($existingItems[$posItemId]);

                // Legacy mode semantics: 'new' never touches existing items,
                // price/qty modes never create items.
                if ($exists && $mode === 'new') {
                    $rowsSkipped++;
                    continue;
                }
                if (! $exists && in_array($mode, ['price', 'qty'])) {
                    $rowsSkipped++;
                    continue;
                }

                try {
                    $outcome = $service->process(
                        $this->buildPayload($row, $posItemId, $mode, $exists)
                    );

                    switch ($outcome['result']) {
                        case 'created':
                            $rowsCreated++;
                            $existingItems[$posItemId] = true;
                            break;
                        case 'updated':
                        case 'deactivated':
                            $rowsUpdated++;
                            break;
                        default:
                            $rowsSkipped++;
                    }
                } catch (\Exception $e) {
                    $rowsFailed++;
                    $errorDetails[] = [
                        'row' => $rowsProcessed + 1,
                        'item' => $name,
                        'pos_id' => $posItemId,
                        'action' => $exists ? 'update' : 'create',
                        'error' => $e->getMessage(),
                    ];
                }

                $this->updateProgress($log, $rowsProcessed, $rowsCreated, $rowsUpdated, $rowsFailed, $totalRows);
            }

            unset($rows);
            gc_collect_cycles();

            try {
                $service->syncCategories();
            } catch (\Exception $e) {
                $this->warn("Category maintenance failed: {$e->getMessage()}");
            }

            $log->update([
                'rows_processed' => $rowsProcessed,
                'rows_created' => $rowsCreated,
                'rows_updated' => $rowsUpdated,
                'rows_failed' => $rowsFailed,
                'status' => 'success',
                'error_details' => $errorDetails ?: null,
            ]);

            $this->info("{$modeLabel[$mode]} complete: {$rowsProcessed} processed, {$rowsCreated} created, {$rowsUpdated} updated, {$rowsSkipped} skipped, {$rowsFailed} failed.");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'rows_processed' => $rowsProcessed ?? 0,
                'rows_created' => $rowsCreated ?? 0,
                'rows_updated' => $rowsUpdated ?? 0,
                'rows_failed' => $rowsFailed ?? 0,
                'error_details' => $errorDetails ?? null,
            ]);

            $this->error("Sync failed: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }

    private function buildPayload(array $row, string $posItemId, string $mode, bool $exists): array
    {
        $payload = [
            'action' => $exists ? 'update' : 'create',
            'pos_item_id' => $posItemId,
            'last_modified' => $this->parseDate($row['U'] ?? null),
            'barcodes' => $this->buildBarcodeList($row),
        ];

        if (in_array($mode, ['all', 'new'])) {
            $payload['name'] = trim($row['C'] ?? '');
            $payload['display_name'] = trim($row['D'] ?? '') ?: null;
            $payload['category'] = trim($row['G'] ?? '') ?: 'Uncategorized';
            $payload['cost_price'] = round((float) ($row['R'] ?? 0), 2);
            $payload['selling_price'] = round((float) ($row['S'] ?? 0), 2);
            $payload['promo_price'] = ($row['V'] ?? '') !== '' && $row['V'] !== null
                ? round((float) $row['V'], 2)
                : null;
            $payload['promo_start_at'] = $this->parseDate($row['W'] ?? null);
            $payload['promo_end_at'] = $this->parseDate($row['X'] ?? null);
            $payload['quantity'] = (int) ($row['AC'] ?? 0);
            $payload['inactive'] = $this->toBool($row['P'] ?? '');
        } elseif ($mode === 'price') {
            $payload['cost_price'] = round((float) ($row['R'] ?? 0), 2);
            $payload['selling_price'] = round((float) ($row['S'] ?? 0), 2);
            $payload['promo_price'] = ($row['V'] ?? '') !== '' && $row['V'] !== null
                ? round((float) $row['V'], 2)
                : null;
            $payload['promo_start_at'] = $this->parseDate($row['W'] ?? null);
            $payload['promo_end_at'] = $this->parseDate($row['X'] ?? null);
        } elseif ($mode === 'qty') {
            $payload['quantity'] = (int) ($row['AC'] ?? 0);
            $payload['inactive'] = $this->toBool($row['P'] ?? '');
        }

        return $payload;
    }

    private function buildBarcodeList(array $row): array
    {
        $list = [];

        $itemNumbers = trim($row['A'] ?? '');
        if ($itemNumbers !== '') {
            foreach (explode('|', $itemNumbers) as $val) {
                $val = trim($val);
                if ($val === '') {
                    continue;
                }
                $list[] = 'item_number|'.$val;
            }
        }

        $productId = trim($row['B'] ?? '');
        if ($productId !== '') {
            $list[] = 'product_id|'.$productId;
        }

        return $list;
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === '0') {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function toBool(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'TRUE', 'True', 'yes', 'Yes', 'YES'], true);
    }

    private function updateProgress(SyncLog $log, int $processed, int $created, int $updated, int $failed, int $total): void
    {
        if ($processed % 100 === 0 || $processed === $total) {
            $log->updateQuietly([
                'rows_processed' => $processed,
                'rows_created' => $created,
                'rows_updated' => $updated,
                'rows_failed' => $failed,
            ]);
        }
    }
}
