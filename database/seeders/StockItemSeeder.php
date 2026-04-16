<?php

namespace Database\Seeders;

use App\Models\StockItem;
use App\Models\Weapon;
use Illuminate\Database\Seeder;

/**
 * Seeds the UNIFIED stock catalog for the whole application.
 *
 * Categories seeded here :
 *  - raw_material   : minerai, petrole, fragments, poudre (tous vendables)
 *  - weapon_piece   : ressort, canon, poignee, corp, metal, polymere (tous vendables)
 *  - weapon_plan    : plans par arme (quantity = uses, 4 uses = 1 plan physique)
 *  - weapon_finished: armes finies (stock), lie au Weapon
 *  - ammo           : munitions (prix = 2x prix sans fer)
 *  - melee          : armes blanches (prix tableau RP)
 *  - drug           : drogues finies (vente orga)
 *
 * Regle : tous les items peuvent etre vendus (is_sellable = true),
 * leur prix par defaut est configurable en DB.
 */
class StockItemSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedRawMaterials();
        $this->seedWeaponPieces();
        $this->seedWeaponPlans();
        $this->seedWeaponFinished();
        $this->seedAmmo();
        $this->seedMelee();
        $this->seedDrugs();
    }

    private function seedRawMaterials(): void
    {
        $items = [
            ['slug' => 'minerai',        'name' => 'Minerai de fer',      'price' => 30,   'weight' => 500, 'sort_order' => 1],
            ['slug' => 'fragment_metal', 'name' => 'Fragment de métal',   'price' => 15,   'weight' => 250, 'sort_order' => 2],
            ['slug' => 'petrole',        'name' => 'Pétrole',             'price' => 900,  'weight' => 500, 'sort_order' => 3],
            ['slug' => 'poudre',         'name' => 'Poudre à canon',      'price' => 100,  'weight' => 100, 'sort_order' => 4],
        ];
        foreach ($items as $it) {
            StockItem::updateOrCreate(['slug' => $it['slug']], [
                'category'               => 'raw_material',
                'name'                   => $it['name'],
                'default_sell_price'     => $it['price'],
                'default_purchase_price' => $it['price'],
                'unit_weight_g'          => $it['weight'],
                'is_sellable'            => true,
                'is_active'              => true,
                'sort_order'             => $it['sort_order'],
            ]);
        }
    }

    private function seedWeaponPieces(): void
    {
        $pieces = [
            ['slug' => 'metal',    'name' => 'Pièce de métal',   'price' => 150,  'sort_order' => 1],
            ['slug' => 'polymere', 'name' => 'Polymère',          'price' => 4500, 'sort_order' => 2],
            ['slug' => 'ressort',  'name' => 'Ressort',           'price' => 5000, 'sort_order' => 3],
            ['slug' => 'canon',    'name' => 'Canon',             'price' => 5000, 'sort_order' => 4],
            ['slug' => 'poignee',  'name' => 'Poignée',           'price' => 5000, 'sort_order' => 5],
            ['slug' => 'corp',     'name' => 'Corp de pistolet',  'price' => 15000,'sort_order' => 6],
        ];
        foreach ($pieces as $p) {
            StockItem::updateOrCreate(['slug' => $p['slug']], [
                'category'               => 'weapon_piece',
                'name'                   => $p['name'],
                'default_sell_price'     => $p['price'],
                'default_purchase_price' => $p['price'],
                'is_sellable'            => true,
                'is_active'               => true,
                'sort_order'             => $p['sort_order'],
            ]);
        }
    }

    private function seedWeaponPlans(): void
    {
        $order = 1;
        foreach (Weapon::orderBy('sort_order')->get() as $w) {
            StockItem::updateOrCreate(['slug' => 'plan_' . $w->slug], [
                'category'           => 'weapon_plan',
                'weapon_id'          => $w->id,
                'name'               => 'Plan ' . $w->name,
                'default_sell_price' => 10000,
                'is_sellable'        => true,
                'is_active'          => true,
                'sort_order'         => $order++,
            ]);
        }
    }

    private function seedWeaponFinished(): void
    {
        $order = 1;
        foreach (Weapon::orderBy('sort_order')->get() as $w) {
            StockItem::updateOrCreate(['slug' => 'weapon_' . $w->slug], [
                'category'               => 'weapon_finished',
                'weapon_id'              => $w->id,
                'name'                   => $w->name,
                'default_sell_price'     => $w->sell_price,
                'default_purchase_price' => $w->reference_purchase_price,
                'is_sellable'            => true,
                'is_active'              => (bool) $w->is_active,
                'sort_order'             => $order++,
            ]);
        }
    }

    /**
     * Munitions : prix de vente = 2 x prix sans fer (poudre seule).
     * Prix sans fer = poudre_recette x 100 EUR / 10 munitions par craft = poudre_recette x 10.
     * Donc prix de vente par munition = poudre_recette x 20.
     */
    private function seedAmmo(): void
    {
        $items = [
            ['slug' => 'ammo_9mm',     'name' => '9mm',      'price' => 100, 'weight' => 7],
            ['slug' => 'ammo_38lc',    'name' => '.38 LC',   'price' => 300, 'weight' => 10],
            ['slug' => 'ammo_45acp',   'name' => '.45 ACP',  'price' => 100, 'weight' => 15],
            ['slug' => 'ammo_50ae',    'name' => '.50 AE',   'price' => 200, 'weight' => 45],
            ['slug' => 'ammo_556x45',  'name' => '5.56x45',  'price' => 400, 'weight' => 4],
            ['slug' => 'ammo_762x39',  'name' => '7.62x39',  'price' => 400, 'weight' => 8],
            ['slug' => 'ammo_12gauge', 'name' => '12 Gauge', 'price' => 600, 'weight' => 38],
            ['slug' => 'ammo_762x51',  'name' => '7.62x51',  'price' => 400, 'weight' => 9],
            ['slug' => 'ammo_50bmg',   'name' => '.50 BMG',  'price' => 400, 'weight' => 50],
        ];
        $order = 10;
        foreach ($items as $it) {
            StockItem::updateOrCreate(['slug' => $it['slug']], [
                'category'           => 'ammo',
                'name'               => $it['name'],
                'default_sell_price' => $it['price'],
                'unit_weight_g'      => $it['weight'],
                'is_sellable'        => true,
                'is_active'          => true,
                'sort_order'         => $order,
            ]);
            $order += 10;
        }
    }

    private function seedMelee(): void
    {
        $items = [
            ['slug' => 'melee_switchblade',  'name' => 'Switchblade',       'buy' => 20000, 'sell' => 30000],
            ['slug' => 'melee_knife',        'name' => 'Knife',             'buy' => 20000, 'sell' => 30000],
            ['slug' => 'melee_machete',      'name' => 'Machete',           'buy' => 20000, 'sell' => 30000],
            ['slug' => 'melee_katana',       'name' => 'Katana BXLife',     'buy' => 25000, 'sell' => 37500],
            ['slug' => 'melee_batte',        'name' => 'Batte',             'buy' => 12000, 'sell' => 18000],
            ['slug' => 'melee_queuebillard', 'name' => 'Queue de billard',  'buy' => 12000, 'sell' => 18000],
            ['slug' => 'melee_golfclub',     'name' => 'Golf Club',         'buy' => 12000, 'sell' => 18000],
            ['slug' => 'melee_piedbiche',    'name' => 'Pied de biche',     'buy' => 15000, 'sell' => 22500],
            ['slug' => 'melee_hammer',       'name' => 'Hammer',            'buy' => 15000, 'sell' => 22500],
            ['slug' => 'melee_cleanglaise',  'name' => 'Clé anglaise',      'buy' => 15000, 'sell' => 22500],
        ];
        $order = 10;
        foreach ($items as $it) {
            StockItem::updateOrCreate(['slug' => $it['slug']], [
                'category'               => 'melee',
                'name'                   => $it['name'],
                'default_purchase_price' => $it['buy'],
                'default_sell_price'     => $it['sell'],
                'is_sellable'            => true,
                'is_active'              => true,
                'sort_order'             => $order,
            ]);
            $order += 10;
        }
    }

    private function seedDrugs(): void
    {
        $items = [
            ['slug' => 'drug_weed_bluedream',  'name' => 'Weed - Blue Dream',       'price' => 100,  'notes' => 'Blue Dream'],
            ['slug' => 'drug_weed_whitewidow', 'name' => 'Weed - White Widow',      'price' => 65,   'notes' => 'White Widow'],
            ['slug' => 'drug_weed_purple',     'name' => 'Weed - Purple',           'price' => 45,   'notes' => 'Purple'],
            ['slug' => 'drug_weed_ogkush',     'name' => 'Weed - OG Kush',          'price' => 30,   'notes' => 'OG Kush'],
            ['slug' => 'drug_cook',            'name' => 'Cook',                    'price' => 325,  'notes' => 'Vente orga 300-350'],
            ['slug' => 'drug_amph_basse',      'name' => 'Amphétamine (basse)',     'price' => 400,  'notes' => null],
            ['slug' => 'drug_amph_moyen',      'name' => 'Amphétamine (moyen)',     'price' => 750,  'notes' => 'Vente orga 700-800'],
            ['slug' => 'drug_amph_haute',      'name' => 'Amphétamine (haute)',     'price' => 850,  'notes' => 'Vente orga 800-900'],
            ['slug' => 'drug_meth_basse',      'name' => 'Méthamphétamine (basse)', 'price' => 550,  'notes' => null],
            ['slug' => 'drug_meth_moyen',      'name' => 'Méthamphétamine (moyen)', 'price' => 900,  'notes' => null],
            ['slug' => 'drug_meth_haute',      'name' => 'Méthamphétamine (haute)', 'price' => 1350, 'notes' => 'Vente orga 1300-1400'],
            ['slug' => 'drug_lsd',             'name' => 'LSD',                     'price' => 3250, 'notes' => 'Vente orga 3000-3500'],
            ['slug' => 'drug_mdma',            'name' => 'MDMA',                    'price' => 2250, 'notes' => 'Vente orga 2000-2500'],
            ['slug' => 'drug_lean',            'name' => 'LEAN',                    'price' => 1800, 'notes' => 'Vente orga 1600-2000'],
        ];
        $order = 10;
        foreach ($items as $it) {
            StockItem::updateOrCreate(['slug' => $it['slug']], [
                'category'           => 'drug',
                'name'               => $it['name'],
                'default_sell_price' => $it['price'],
                'is_sellable'        => true,
                'is_active'          => true,
                'sort_order'         => $order,
                'notes'              => $it['notes'],
            ]);
            $order += 10;
        }
    }
}
