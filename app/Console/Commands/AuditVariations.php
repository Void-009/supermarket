<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Models\PosItemAlias;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Read-only report on variation identity: which sibling groups would be
 * display-deduped (same parent + normalized name + cost), how many
 * parent/child pairs share a name (these are left alone), and the current
 * tombstone / alias counts. Writes nothing.
 */
class AuditVariations extends Command
{
    protected $signature = 'pos:audit-variations {--parent= : Limit the report to one parent pos_item_id}';

    protected $description = 'Report duplicate and same-name variation groups without changing anything';

    public function handle(): int
    {
        $query = Item::whereNotNull('parent_item_number')
            ->whereNull('pos_missing_since')
            ->orderBy('parent_item_number');

        if ($parent = $this->option('parent')) {
            $query->where('parent_item_number', $parent);
        }

        $variations = $query->get();

        $groups = $variations->groupBy(fn (Item $v) => $v->parent_item_number.'|'.Item::variationGroupKey($v));

        $duplicates = $groups->filter(fn (Collection $g) => $g->count() > 1);

        $parentIds = $variations->pluck('parent_item_number')->unique()->values();
        $parents = Item::whereIn('pos_item_id', $parentIds)->get()->keyBy('pos_item_id');

        $parentChildSameName = 0;
        foreach ($variations->groupBy('parent_item_number') as $parentId => $siblings) {
            $parent = $parents->get($parentId);
            if (! $parent) {
                continue;
            }
            $parentKey = Item::normalizedNameKey($parent->name);
            if ($siblings->contains(fn (Item $v) => Item::normalizedNameKey($v->name) === $parentKey)) {
                $parentChildSameName++;
            }
        }

        $this->info("Variations scanned (live): {$variations->count()}");
        $this->info('Families: '.$variations->pluck('parent_item_number')->unique()->count());
        $this->info("Duplicate groups (same parent + name + cost): {$duplicates->count()}");
        $this->info('Parent/child pairs sharing a name (never deduped): '.$parentChildSameName);
        $this->info('Tombstoned variations: '.Item::whereNotNull('pos_missing_since')->count());
        $this->info('Aliases recorded: '.PosItemAlias::count());

        if ($duplicates->isEmpty()) {
            $this->info('No duplicate groups found.');

            return Command::SUCCESS;
        }

        $this->newLine();
        $this->info('Duplicate groups — the FIRST id is the winner dedupe would keep:');

        foreach ($duplicates as $group) {
            $winner = $group->sortByDesc(fn (Item $v) => [
                $v->last_modified?->timestamp ?? 0,
                (int) $v->id,
            ])->first();

            $this->line("  parent {$winner->parent_item_number} | {$winner->name} | cost {$winner->cost_price}");
            $this->line("    KEEP  {$winner->pos_item_id} (id {$winner->id}, last_modified ".($winner->last_modified ?? 'n/a').')');
            foreach ($group->reject(fn (Item $v) => $v->id === $winner->id) as $loser) {
                $this->line("    HIDE  {$loser->pos_item_id} (id {$loser->id}, last_modified ".($loser->last_modified ?? 'n/a').')');
            }
        }

        return Command::SUCCESS;
    }
}
