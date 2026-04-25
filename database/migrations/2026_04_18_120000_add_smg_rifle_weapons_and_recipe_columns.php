<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute les colonnes recette crosse / corp_smg / corp_rifle sur weapons,
 * insere les nouvelles armes (Micro SMG, Mini SMG, Tec 9, AKU, Pompe),
 * met a jour la recette + prix d'achat de l'AK-47,
 * et cree les stock_items correspondants.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Nouvelles colonnes recette
        Schema::table('weapons', function (Blueprint $table) {
            $table->unsignedTinyInteger('recipe_crosse')->default(0)->after('recipe_corp');
            $table->unsignedTinyInteger('recipe_corp_smg')->default(0)->after('recipe_crosse');
            $table->unsignedTinyInteger('recipe_corp_rifle')->default(0)->after('recipe_corp_smg');
        });

        // 2. Inserer les nouvelles armes
        $now = now();
        $newWeapons = [
            [
                'name' => 'Micro SMG',
                'slug' => 'micro_smg',
                'craft_time_seconds' => 30,
                'sell_price' => null,
                'reference_purchase_price' => 300000,
                'price_min' => null,
                'price_max' => null,
                'recipe_plans' => 1,
                'recipe_ressort' => 2,
                'recipe_canon' => 1,
                'recipe_poignee' => 1,
                'recipe_corp' => 0,
                'recipe_crosse' => 1,
                'recipe_corp_smg' => 1,
                'recipe_corp_rifle' => 0,
                'recipe_metal' => 15,
                'recipe_polymere' => 20,
                'sort_order' => 8,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Mini SMG',
                'slug' => 'mini_smg',
                'craft_time_seconds' => 30,
                'sell_price' => null,
                'reference_purchase_price' => 300000,
                'price_min' => null,
                'price_max' => null,
                'recipe_plans' => 1,
                'recipe_ressort' => 2,
                'recipe_canon' => 1,
                'recipe_poignee' => 1,
                'recipe_corp' => 0,
                'recipe_crosse' => 1,
                'recipe_corp_smg' => 1,
                'recipe_corp_rifle' => 0,
                'recipe_metal' => 15,
                'recipe_polymere' => 20,
                'sort_order' => 9,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Tec 9',
                'slug' => 'tec9',
                'craft_time_seconds' => 30,
                'sell_price' => null,
                'reference_purchase_price' => 325000,
                'price_min' => null,
                'price_max' => null,
                'recipe_plans' => 1,
                'recipe_ressort' => 2,
                'recipe_canon' => 1,
                'recipe_poignee' => 1,
                'recipe_corp' => 0,
                'recipe_crosse' => 1,
                'recipe_corp_smg' => 1,
                'recipe_corp_rifle' => 0,
                'recipe_metal' => 15,
                'recipe_polymere' => 20,
                'sort_order' => 10,
                'is_active' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'AKU',
                'slug' => 'aku',
                'craft_time_seconds' => 45,
                'sell_price' => null,
                'reference_purchase_price' => 500000,
                'price_min' => null,
                'price_max' => null,
                'recipe_plans' => 0,
                'recipe_ressort' => 5,
                'recipe_canon' => 1,
                'recipe_poignee' => 1,
                'recipe_corp' => 0,
                'recipe_crosse' => 1,
                'recipe_corp_smg' => 0,
                'recipe_corp_rifle' => 1,
                'recipe_metal' => 25,
                'recipe_polymere' => 35,
                'sort_order' => 11,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Pompe',
                'slug' => 'pompe',
                'craft_time_seconds' => 50,
                'sell_price' => null,
                'reference_purchase_price' => 600000,
                'price_min' => null,
                'price_max' => null,
                'recipe_plans' => 1,
                'recipe_ressort' => 4,
                'recipe_canon' => 1,
                'recipe_poignee' => 1,
                'recipe_corp' => 0,
                'recipe_crosse' => 1,
                'recipe_corp_smg' => 0,
                'recipe_corp_rifle' => 1,
                'recipe_metal' => 30,
                'recipe_polymere' => 50,
                'sort_order' => 12,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('weapons')->insert($newWeapons);

        // 3. Mettre a jour AK-47 : recette + prix d'achat
        DB::table('weapons')->where('slug', 'ak47')->update([
            'reference_purchase_price' => 800000,
            'recipe_corp' => 0,
            'recipe_crosse' => 1,
            'recipe_corp_rifle' => 1,
        ]);

        // 4. Creer les stock_items weapon_finished pour chaque nouvelle arme
        $weaponIds = DB::table('weapons')
            ->whereIn('slug', ['micro_smg', 'mini_smg', 'tec9', 'aku', 'pompe'])
            ->pluck('id', 'slug');

        $finishedOrder = DB::table('stock_items')
            ->where('category', 'weapon_finished')
            ->max('sort_order') ?? 0;

        foreach ($newWeapons as $w) {
            $finishedOrder++;
            DB::table('stock_items')->insert([
                'category' => 'weapon_finished',
                'slug' => 'weapon_' . $w['slug'],
                'name' => $w['name'],
                'weapon_id' => $weaponIds[$w['slug']],
                'default_sell_price' => $w['sell_price'],
                'default_purchase_price' => $w['reference_purchase_price'],
                'is_sellable' => true,
                'is_active' => true,
                'sort_order' => $finishedOrder,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 5. Creer le plan Micro SMG (plan_micro_smg n'existe pas encore)
        $planOrder = DB::table('stock_items')
            ->where('category', 'weapon_plan')
            ->max('sort_order') ?? 0;

        // plan_micro_smg → nouveau
        if (!DB::table('stock_items')->where('slug', 'plan_micro_smg')->exists()) {
            DB::table('stock_items')->insert([
                'category' => 'weapon_plan',
                'slug' => 'plan_micro_smg',
                'name' => 'Plan Micro SMG',
                'weapon_id' => $weaponIds['micro_smg'],
                'default_sell_price' => 10000,
                'is_sellable' => true,
                'is_active' => true,
                'sort_order' => $planOrder + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 6. Renommer les plans existants pour respecter la convention plan_{weapon_slug}
        //    et lier via weapon_id
        //    plan_mini_smg      → ok (deja correct pour mini_smg)
        //    plan_machine_pistol → plan_tec9
        //    plan_fusil_pompe   → plan_pompe
        $planMappings = [
            'plan_mini_smg'       => ['weapon_id' => $weaponIds['mini_smg'] ?? null, 'new_slug' => null],
            'plan_machine_pistol' => ['weapon_id' => $weaponIds['tec9'] ?? null, 'new_slug' => 'plan_tec9', 'new_name' => 'Plan Tec 9'],
            'plan_fusil_pompe'    => ['weapon_id' => $weaponIds['pompe'] ?? null, 'new_slug' => 'plan_pompe', 'new_name' => 'Plan Pompe'],
        ];

        foreach ($planMappings as $planSlug => $mapping) {
            if (!$mapping['weapon_id']) continue;
            $update = ['weapon_id' => $mapping['weapon_id']];
            if (!empty($mapping['new_slug'])) {
                $update['slug'] = $mapping['new_slug'];
                $update['name'] = $mapping['new_name'];
            }
            DB::table('stock_items')
                ->where('slug', $planSlug)
                ->update($update);
        }

        // 7. Mettre a jour le stock_item weapon_ak47 avec le nouveau prix d'achat
        DB::table('stock_items')
            ->where('slug', 'weapon_ak47')
            ->update(['default_purchase_price' => 800000]);
    }

    public function down(): void
    {
        // Supprimer les stock_items crees
        DB::table('stock_items')->whereIn('slug', [
            'weapon_micro_smg', 'weapon_mini_smg', 'weapon_tec9',
            'weapon_aku', 'weapon_pompe', 'plan_micro_smg',
        ])->delete();

        // Retirer weapon_id et restaurer slugs des plans relies
        DB::table('stock_items')
            ->where('slug', 'plan_mini_smg')
            ->update(['weapon_id' => null]);
        DB::table('stock_items')
            ->where('slug', 'plan_tec9')
            ->update(['weapon_id' => null, 'slug' => 'plan_machine_pistol', 'name' => 'Plan Machine Pistol']);
        DB::table('stock_items')
            ->where('slug', 'plan_pompe')
            ->update(['weapon_id' => null, 'slug' => 'plan_fusil_pompe', 'name' => 'Plan Fusil a pompe']);

        // Supprimer les nouvelles armes
        DB::table('weapons')->whereIn('slug', [
            'micro_smg', 'mini_smg', 'tec9', 'aku', 'pompe',
        ])->delete();

        // Restaurer AK-47
        DB::table('weapons')->where('slug', 'ak47')->update([
            'reference_purchase_price' => null,
            'recipe_corp' => 1,
            'recipe_crosse' => 0,
            'recipe_corp_rifle' => 0,
        ]);

        DB::table('stock_items')
            ->where('slug', 'weapon_ak47')
            ->update(['default_purchase_price' => null]);

        // Supprimer les colonnes
        Schema::table('weapons', function (Blueprint $table) {
            $table->dropColumn(['recipe_crosse', 'recipe_corp_smg', 'recipe_corp_rifle']);
        });
    }
};
