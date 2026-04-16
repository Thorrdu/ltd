<?php

namespace Database\Seeders;

use App\Models\StockItem;
use App\Models\Weapon;
use Illuminate\Database\Seeder;

/**
 * Seeds the master catalog of saleable/stockable items.
 *
 * Phase 2 uses this table purely as a catalog for /ventes (no quantity tracking yet).
 * Phase 3 will populate quantity_in_stock / quantity_external and wire stock_movements.
 *
 * Keep the sort_order grouped by category so the Tom Select optgroups stay readable.
 */
class StockItemSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedWeapons();
        $this->seedAmmo();
        $this->seedMelee();
        $this->seedDrugs();
    }

    private function seedWeapons(): void
    {
        $order = 10;
        foreach (Weapon::orderBy('sort_order')->get() as $w) {
            StockItem::updateOrCreate(
                ['slug' => 'stock_weapon_' . $w->slug],
                [
                    'category' => 'weapon_finished',
                    'name' => $w->name,
                    'weapon_id' => $w->id,
                    'default_sell_price' => $w->sell_price,
                    'default_purchase_price' => $w->reference_purchase_price,
                    'is_active' => (bool) $w->is_active,
                    'sort_order' => $order,
                ]
            );
            $order += 10;
        }
    }

    private function seedAmmo(): void
    {
        // Prices are placeholders; real values will be tuned via the settings panel.
        $items = [
            ['slug' => 'ammo_45acp',    'name' => '.45 ACP',   'price' => 15,  'weight' => 15],
            ['slug' => 'ammo_9mm',      'name' => '9mm',       'price' => 10,  'weight' => 7],
            ['slug' => 'ammo_50ae',     'name' => '.50 AE',    'price' => 45,  'weight' => 45],
            ['slug' => 'ammo_12gauge',  'name' => '12 Gauge',  'price' => 40,  'weight' => 38],
            ['slug' => 'ammo_556x45',   'name' => '5.56x45',   'price' => 20,  'weight' => 4],
            ['slug' => 'ammo_50bmg',    'name' => '.50 BMG',   'price' => 120, 'weight' => 50],
            ['slug' => 'ammo_762x51',   'name' => '7.62x51',   'price' => 25,  'weight' => 9],
            ['slug' => 'ammo_762x39',   'name' => '7.62x39',   'price' => 20,  'weight' => 8],
        ];
        $order = 10;
        foreach ($items as $it) {
            StockItem::updateOrCreate(
                ['slug' => $it['slug']],
                [
                    'category' => 'ammo',
                    'name' => $it['name'],
                    'default_sell_price' => $it['price'],
                    'unit_weight_g' => $it['weight'],
                    'is_active' => true,
                    'sort_order' => $order,
                ]
            );
            $order += 10;
        }
    }

    private function seedMelee(): void
    {
        // Prices from the game sheet (sell = purchase x 1.5, configurable in settings later).
        $items = [
            ['slug' => 'melee_switchblade',   'name' => 'Switchblade',       'buy' => 20000, 'sell' => 30000],
            ['slug' => 'melee_knife',         'name' => 'Knife',             'buy' => 20000, 'sell' => 30000],
            ['slug' => 'melee_machete',       'name' => 'Machete',           'buy' => 20000, 'sell' => 30000],
            ['slug' => 'melee_katana',        'name' => 'Katana BXLife',     'buy' => 25000, 'sell' => 37500],
            ['slug' => 'melee_batte',         'name' => 'Batte',             'buy' => 12000, 'sell' => 18000],
            ['slug' => 'melee_queuebillard',  'name' => 'Queue de billard',  'buy' => 12000, 'sell' => 18000],
            ['slug' => 'melee_golfclub',      'name' => 'Golf Club',         'buy' => 12000, 'sell' => 18000],
            ['slug' => 'melee_piedbiche',     'name' => 'Pied de biche',     'buy' => 15000, 'sell' => 22500],
            ['slug' => 'melee_hammer',        'name' => 'Hammer',            'buy' => 15000, 'sell' => 22500],
            ['slug' => 'melee_cleanglaise',   'name' => 'Cle anglaise',      'buy' => 15000, 'sell' => 22500],
        ];
        $order = 10;
        foreach ($items as $it) {
            StockItem::updateOrCreate(
                ['slug' => $it['slug']],
                [
                    'category' => 'melee',
                    'name' => $it['name'],
                    'default_purchase_price' => $it['buy'],
                    'default_sell_price' => $it['sell'],
                    'is_active' => true,
                    'sort_order' => $order,
                ]
            );
            $order += 10;
        }
    }

    private function seedDrugs(): void
    {
        // Prices reflect the "sell Orga" mid-range when available, else the sac price.
        // Staff min price is stored elsewhere; the number here is the default proposal.
        $items = [
            // Weed (sold per sac)
            ['slug' => 'drug_weed_bluedream',   'name' => 'Weed - Blue Dream',   'price' => 100,  'notes' => 'Blue Dream'],
            ['slug' => 'drug_weed_whitewidow',  'name' => 'Weed - White Widow',  'price' => 65,   'notes' => 'White Widow'],
            ['slug' => 'drug_weed_purple',      'name' => 'Weed - Purple',       'price' => 45,   'notes' => 'Purple'],
            ['slug' => 'drug_weed_ogkush',      'name' => 'Weed - OG Kush',      'price' => 30,   'notes' => 'OG Kush'],
            // Cook
            ['slug' => 'drug_cook',             'name' => 'Cook',                'price' => 325,  'notes' => 'Vente orga 300-350'],
            // Amphetamine
            ['slug' => 'drug_amph_basse',       'name' => 'Amphetamine (basse)',   'price' => 400,  'notes' => ''],
            ['slug' => 'drug_amph_moyen',       'name' => 'Amphetamine (moyen)',   'price' => 750,  'notes' => 'Vente orga 700-800'],
            ['slug' => 'drug_amph_haute',       'name' => 'Amphetamine (haute)',   'price' => 850,  'notes' => 'Vente orga 800-900'],
            // Methamphetamine
            ['slug' => 'drug_meth_basse',       'name' => 'Methamphetamine (basse)', 'price' => 550,  'notes' => ''],
            ['slug' => 'drug_meth_moyen',       'name' => 'Methamphetamine (moyen)', 'price' => 900,  'notes' => ''],
            ['slug' => 'drug_meth_haute',       'name' => 'Methamphetamine (haute)', 'price' => 1350, 'notes' => 'Vente orga 1300-1400'],
            // Designer drugs
            ['slug' => 'drug_lsd',              'name' => 'LSD',                 'price' => 3250, 'notes' => 'Vente orga 3000-3500'],
            ['slug' => 'drug_mdma',             'name' => 'MDMA',                'price' => 2250, 'notes' => 'Vente orga 2000-2500'],
            ['slug' => 'drug_lean',             'name' => 'LEAN',                'price' => 1800, 'notes' => 'Vente orga 1600-2000'],
        ];
        $order = 10;
        foreach ($items as $it) {
            StockItem::updateOrCreate(
                ['slug' => $it['slug']],
                [
                    'category' => 'drug',
                    'name' => $it['name'],
                    'default_sell_price' => $it['price'],
                    'is_active' => true,
                    'sort_order' => $order,
                    'notes' => $it['notes'] ?: null,
                ]
            );
            $order += 10;
        }
    }
}
