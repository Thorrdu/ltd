<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Creates stock_items that are referenced in the CSV import but may not exist
 * on production (because seeders were not re-run after additions).
 */
return new class extends Migration
{
    public function up(): void
    {
        $items = [
            // weapon_finished
            ['slug' => 'weapon_machine_pistol', 'name' => 'Machine Pistol', 'category' => 'weapon_finished', 'is_sellable' => true, 'default_sell_price' => 0, 'is_active' => true],
            // weapon_piece
            ['slug' => 'corp_smg', 'name' => 'Corps de SMG', 'category' => 'weapon_piece', 'is_sellable' => false, 'default_sell_price' => null, 'is_active' => true],
            ['slug' => 'corp_rifle', 'name' => 'Corps de fusil', 'category' => 'weapon_piece', 'is_sellable' => false, 'default_sell_price' => null, 'is_active' => true],
            ['slug' => 'crosse', 'name' => 'Crosse', 'category' => 'weapon_piece', 'is_sellable' => false, 'default_sell_price' => null, 'is_active' => true],
            ['slug' => 'suppressor', 'name' => 'Suppresseur', 'category' => 'weapon_piece', 'is_sellable' => false, 'default_sell_price' => null, 'is_active' => true],
            ['slug' => 'tactical_suppressor', 'name' => 'Suppresseur tactique', 'category' => 'weapon_piece', 'is_sellable' => false, 'default_sell_price' => null, 'is_active' => true],
            // weapon_plan
            ['slug' => 'plan_aku', 'name' => 'Plan AKU', 'category' => 'weapon_plan', 'is_sellable' => true, 'default_sell_price' => 10000, 'is_active' => true],
            ['slug' => 'plan_pompe', 'name' => 'Plan Pompe', 'category' => 'weapon_plan', 'is_sellable' => true, 'default_sell_price' => 10000, 'is_active' => true],
            ['slug' => 'plan_mg', 'name' => 'Plan MG', 'category' => 'weapon_plan', 'is_sellable' => true, 'default_sell_price' => 10000, 'is_active' => true],
            ['slug' => 'plan_combat_pdw', 'name' => 'Plan Combat PDW', 'category' => 'weapon_plan', 'is_sellable' => true, 'default_sell_price' => 10000, 'is_active' => true],
            ['slug' => 'plan_combat_pistol', 'name' => 'Plan Combat Pistol', 'category' => 'weapon_plan', 'is_sellable' => true, 'default_sell_price' => 10000, 'is_active' => true],
            ['slug' => 'plan_machine_pistol', 'name' => 'Plan Machine Pistol', 'category' => 'weapon_plan', 'is_sellable' => true, 'default_sell_price' => 10000, 'is_active' => true],
            ['slug' => 'plan_fusil_pompe', 'name' => 'Plan Fusil a pompe', 'category' => 'weapon_plan', 'is_sellable' => true, 'default_sell_price' => 10000, 'is_active' => true],
            ['slug' => 'plan_mini_smg', 'name' => 'Plan Mini SMG', 'category' => 'weapon_plan', 'is_sellable' => true, 'default_sell_price' => 10000, 'is_active' => true],
            ['slug' => 'plan_ak_complet', 'name' => 'Plan AK complet', 'category' => 'weapon_plan', 'is_sellable' => true, 'default_sell_price' => 10000, 'is_active' => true],
            // drugs
            ['slug' => 'drug_joint_ogkush', 'name' => 'Joint (OG Kush)', 'category' => 'drug', 'is_sellable' => true, 'default_sell_price' => 500, 'is_active' => true],
            ['slug' => 'drug_sac_meth', 'name' => 'Sac de meth', 'category' => 'drug', 'is_sellable' => true, 'default_sell_price' => 5000, 'is_active' => true],
            ['slug' => 'drug_brique_weed', 'name' => 'Brique de weed', 'category' => 'drug', 'is_sellable' => true, 'default_sell_price' => 0, 'is_active' => true],
            ['slug' => 'drug_brique_cocaine', 'name' => 'Brique de cocaine', 'category' => 'drug', 'is_sellable' => true, 'default_sell_price' => 0, 'is_active' => true],
            ['slug' => 'drug_sachet_weed', 'name' => 'Sachet de weed', 'category' => 'drug', 'is_sellable' => true, 'default_sell_price' => 0, 'is_active' => true],
            ['slug' => 'drug_joint_purple', 'name' => 'Joint (Purple)', 'category' => 'drug', 'is_sellable' => true, 'default_sell_price' => 500, 'is_active' => true],
            ['slug' => 'drug_poudre_cafe', 'name' => 'Poudre de cafe', 'category' => 'drug', 'is_sellable' => false, 'default_sell_price' => null, 'is_active' => true],
            // electronic
            ['slug' => 'elec_clavier', 'name' => 'Clavier', 'category' => 'electronic', 'is_sellable' => false, 'default_sell_price' => null, 'is_active' => true],
            ['slug' => 'elec_laptop', 'name' => 'Laptop', 'category' => 'electronic', 'is_sellable' => false, 'default_sell_price' => null, 'is_active' => true],
            // melee
            ['slug' => 'melee_badminton', 'name' => 'Badminton bat', 'category' => 'melee', 'is_sellable' => false, 'default_sell_price' => null, 'is_active' => true],
            // misc
            ['slug' => 'misc_badge_presse', 'name' => 'Badge de presse', 'category' => 'misc', 'is_sellable' => false, 'default_sell_price' => null, 'is_active' => true],
            ['slug' => 'misc_portefeuille', 'name' => 'Portefeuille', 'category' => 'misc', 'is_sellable' => false, 'default_sell_price' => null, 'is_active' => true],
            ['slug' => 'misc_carte_identite', 'name' => "Carte d'identite", 'category' => 'misc', 'is_sellable' => false, 'default_sell_price' => null, 'is_active' => true],
            ['slug' => 'misc_permis_conduire', 'name' => 'Permis de conduire', 'category' => 'misc', 'is_sellable' => false, 'default_sell_price' => null, 'is_active' => true],
            ['slug' => 'misc_bong', 'name' => 'Bong', 'category' => 'misc', 'is_sellable' => false, 'default_sell_price' => null, 'is_active' => true],
            ['slug' => 'misc_peinture', 'name' => 'Peinture', 'category' => 'misc', 'is_sellable' => false, 'default_sell_price' => null, 'is_active' => true],
            ['slug' => 'misc_tablette_chocolat', 'name' => 'Tablette de chocolat', 'category' => 'misc', 'is_sellable' => false, 'default_sell_price' => null, 'is_active' => true],
            ['slug' => 'misc_bombonne_gaz', 'name' => 'Bombonne a gaz', 'category' => 'misc', 'is_sellable' => false, 'default_sell_price' => null, 'is_active' => true],
        ];

        $now = now();
        foreach ($items as $item) {
            $exists = DB::table('stock_items')->where('slug', $item['slug'])->exists();
            if (!$exists) {
                DB::table('stock_items')->insert(array_merge($item, [
                    'quantity'   => 0,
                    'sort_order' => 999,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }
    }

    public function down(): void
    {
        // Items will remain — no destructive rollback
    }
};
