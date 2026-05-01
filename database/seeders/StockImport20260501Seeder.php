<?php

namespace Database\Seeders;

use App\Models\StockItem;
use App\Models\Weapon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

/**
 * Imports stock from screenshots du 1er mai 2026.
 *
 * 1. Cree les items manquants (nouveau dans le stock).
 * 2. Lit le CSV stock_import_2026-05-01.csv et met a jour toutes les quantites.
 */
class StockImport20260501Seeder extends Seeder
{
    public function run(): void
    {
        $this->createMissingItems();
        $this->importCsv();
    }

    private function createMissingItems(): void
    {
        $newItems = [
            // Drugs
            [
                'slug'               => 'drug_joint_ogkush',
                'name'               => 'Joint (OG Kush)',
                'category'           => 'drug',
                'is_sellable'        => true,
                'default_sell_price' => 500,
                'is_active'          => true,
            ],
            [
                'slug'               => 'drug_sac_meth',
                'name'               => 'Sac de meth',
                'category'           => 'drug',
                'is_sellable'        => true,
                'default_sell_price' => 5000,
                'is_active'          => true,
            ],
            // Electronics
            [
                'slug'       => 'elec_clavier',
                'name'       => 'Clavier',
                'category'   => 'electronic',
                'is_active'  => true,
            ],
            [
                'slug'       => 'elec_laptop',
                'name'       => 'Laptop',
                'category'   => 'electronic',
                'is_active'  => true,
            ],
            // Melee
            [
                'slug'       => 'melee_badminton',
                'name'       => 'Badminton bat',
                'category'   => 'melee',
                'is_active'  => true,
            ],
            // Misc
            [
                'slug'       => 'misc_badge_presse',
                'name'       => 'Badge de presse',
                'category'   => 'misc',
                'is_active'  => true,
            ],
            [
                'slug'       => 'misc_portefeuille',
                'name'       => 'Portefeuille',
                'category'   => 'misc',
                'is_active'  => true,
            ],
            [
                'slug'       => 'misc_carte_identite',
                'name'       => "Carte d'identite",
                'category'   => 'misc',
                'is_active'  => true,
            ],
            [
                'slug'       => 'misc_permis_conduire',
                'name'       => 'Permis de conduire',
                'category'   => 'misc',
                'is_active'  => true,
            ],
            [
                'slug'       => 'misc_bong',
                'name'       => 'Bong',
                'category'   => 'misc',
                'is_active'  => true,
            ],
            [
                'slug'       => 'misc_peinture',
                'name'       => 'Peinture',
                'category'   => 'misc',
                'is_active'  => true,
            ],
            [
                'slug'       => 'misc_tablette_chocolat',
                'name'       => 'Tablette de chocolat',
                'category'   => 'misc',
                'is_active'  => true,
            ],
            [
                'slug'       => 'misc_bombonne_gaz',
                'name'       => 'Bombonne a gaz',
                'category'   => 'misc',
                'is_active'  => true,
            ],
        ];

        foreach ($newItems as $item) {
            StockItem::firstOrCreate(
                ['slug' => $item['slug']],
                array_merge($item, ['quantity' => 0])
            );
        }

        // Machine Pistol (weapon_finished) — needs weapon_id link
        $machinePistol = Weapon::where('slug', 'machine_pistol')->first();
        if ($machinePistol) {
            StockItem::firstOrCreate(
                ['slug' => 'weapon_machine_pistol'],
                [
                    'name'               => 'Machine Pistol',
                    'category'           => 'weapon_finished',
                    'weapon_id'          => $machinePistol->id,
                    'is_sellable'        => true,
                    'default_sell_price' => $machinePistol->sell_price ?? 0,
                    'is_active'          => true,
                    'quantity'           => 0,
                ]
            );
        } else {
            Log::warning('StockImport20260501: Weapon "machine_pistol" not found, skipping weapon_machine_pistol stock item.');
        }
    }

    private function importCsv(): void
    {
        $path = database_path('csv/stock_import_2026-05-01.csv');
        if (!file_exists($path)) {
            $this->command?->error("CSV not found: {$path}");
            return;
        }

        $handle = fopen($path, 'r');
        $header = fgetcsv($handle, 0, ';');
        $updated = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if (count($row) < 2) continue;
            [$slug, $quantity] = $row;
            $slug = trim($slug);
            $quantity = (int) trim($quantity);

            $item = StockItem::where('slug', $slug)->first();
            if ($item) {
                $item->update(['quantity' => $quantity]);
                $updated++;
            } else {
                $skipped++;
                Log::warning("StockImport20260501: slug '{$slug}' not found in stock_items, skipped.");
            }
        }

        fclose($handle);
        $this->command?->info("Stock import done: {$updated} updated, {$skipped} skipped.");
    }
}
