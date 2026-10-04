<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use App\Models\ItemBarcode;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAdmin();
        $this->seedSettings();
        $this->seedFromExcel();
    }

    private function seedAdmin(): void
    {
        if (User::where('email', 'admin@mdelight.com')->exists()) {
            $this->command?->info('Admin user already exists.');
            return;
        }

        User::create([
            'name' => 'Admin',
            'email' => 'admin@mdelight.com',
            'password' => Hash::make('password'),
            'phone' => '08000000000',
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $this->command?->info('Admin user created: admin@mdelight.com / password');
    }

    private function seedSettings(): void
    {
        $settings = [
            'store_name' => 'M-DELIGHT SINGER STORES',
            'store_tagline' => 'Your Trusted Supermarket, Now Online',
            'store_phone' => '08000000000',
            'store_email' => 'info@mdelight.com',
            'store_address' => 'Singer Stores, Kano, Nigeria',
            'logo_path' => 'logo/mdelight logo.jpg',
            'currency' => 'NGN',
            'currency_symbol' => '₦',
            'free_delivery_threshold' => '50000',
            'delivery_fee' => '2000',
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $this->command?->info('Store settings seeded.');
    }

    private function seedFromExcel(): void
    {
        $path = config('pos.export_path');

        if (!file_exists($path)) {
            $this->command?->error("Excel file not found: {$path}");
            return;
        }

        if (Item::count() > 0) {
            $this->command?->info('Items already exist. Skipping import.');
            return;
        }

        $this->command?->info('Importing products from Excel... This may take a few minutes.');

        spl_autoload_register(function ($class) {
            $map = [
                'PhpOffice\\PhpSpreadsheet\\' => base_path('vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/'),
                'Composer\\Pcre\\' => base_path('vendor/composer/pcre/src/'),
                'MarkBaker\\Matrix\\' => base_path('vendor/markbaker/matrix/src/'),
                'MarkBaker\\Complex\\' => base_path('vendor/markbaker/complex/src/'),
            ];
            foreach ($map as $prefix => $base) {
                if (strncmp($prefix, $class, strlen($prefix)) === 0) {
                    $relativeClass = substr($class, strlen($prefix));
                    $file = $base . str_replace('\\', '/', $relativeClass) . '.php';
                    if (file_exists($file)) {
                        require $file;
                        return;
                    }
                }
            }
        });

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        $header = array_shift($rows);

        $categoryMap = [];
        $created = 0;
        $skipped = 0;
        $total = count($rows);

        foreach ($rows as $index => $row) {
            $name = trim($row['C'] ?? '');
            if (empty($name)) {
                $skipped++;
                continue;
            }

            $categoryName = trim($row['G'] ?? '');
            if (empty($categoryName)) {
                $categoryName = 'Uncategorized';
            }

            if (!isset($categoryMap[$categoryName])) {
                $category = Category::firstOrCreate(
                    ['name' => $categoryName],
                    [
                        'slug' => Str::slug($categoryName),
                        'is_active' => true,
                        'sort_order' => 0,
                    ]
                );
                $categoryMap[$categoryName] = $category->id;
            }

            $posItemId = trim($row['AX'] ?? '');
            if (empty($posItemId)) {
                $posItemId = 'POS-' . ($index + 1);
            }

            $costPrice = (float) ($row['R'] ?? 0);
            $sellingPrice = (float) ($row['S'] ?? 0);
            $quantity = (int) ($row['AC'] ?? 0);
            $displayName = trim($row['D'] ?? '') ?: null;
            $lastModified = $row['U'] ?? null;

            if ($lastModified) {
                try {
                    $lastModified = \Carbon\Carbon::parse($lastModified);
                } catch (\Exception $e) {
                    $lastModified = now();
                }
            }

            $stockStatus = 'in_stock';
            if ($quantity <= 0) {
                $stockStatus = 'out_of_stock';
            } elseif ($quantity < 5) {
                $stockStatus = 'low_stock';
            }

            $slug = Str::slug($name);
            $existingSlug = Item::where('slug', 'like', $slug . '%')->count();
            if ($existingSlug > 0) {
                $slug = $slug . '-' . ($existingSlug + 1);
            }

            try {
                $item = Item::create([
                    'pos_item_id' => $posItemId,
                    'category_id' => $categoryMap[$categoryName],
                    'name' => $name,
                    'display_name' => $displayName,
                    'slug' => $slug,
                    'cost_price' => $costPrice,
                    'selling_price' => $sellingPrice,
                    'quantity' => $quantity,
                    'is_visible_online' => $quantity > 0,
                    'stock_status' => $stockStatus,
                    'last_modified' => $lastModified,
                ]);

                $this->syncBarcodes($item->id, $row);
                $created++;
            } catch (\Exception $e) {
                $skipped++;
            }

            if (($index + 1) % 1000 === 0) {
                $this->command?->info("  Progress: " . ($index + 1) . "/{$total} rows processed...");
            }
        }

        $this->command?->info("Import complete: {$created} items created, {$skipped} skipped.");
        $this->command?->info("Categories created: " . count($categoryMap));
    }

    private function syncBarcodes(int $itemId, array $row): void
    {
        $seen = [];
        $toInsert = [];

        $itemNumbers = trim($row['A'] ?? '');
        if ($itemNumbers !== '') {
            foreach (explode('|', $itemNumbers) as $val) {
                $val = trim($val);
                if ($val !== '' && !isset($seen[$val])) {
                    $seen[$val] = true;
                    $toInsert[] = [
                        'item_id' => $itemId,
                        'barcode_type' => 'item_number',
                        'barcode_value' => $val,
                    ];
                }
            }
        }

        $productId = trim($row['B'] ?? '');
        if ($productId !== '' && !isset($seen[$productId])) {
            $seen[$productId] = true;
            $toInsert[] = [
                'item_id' => $itemId,
                'barcode_type' => 'product_id',
                'barcode_value' => $productId,
            ];
        }

        if (!empty($toInsert)) {
            ItemBarcode::insert($toInsert);
        }
    }
}
