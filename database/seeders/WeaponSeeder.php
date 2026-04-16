<?php

namespace Database\Seeders;

use App\Models\Weapon;
use App\Models\WeaponStock;
use Illuminate\Database\Seeder;

class WeaponSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * Grille RP (prix max) : SNS 70k … Cal.50 220k. Vente : ancres SNS 60k, Cal .50 200k, autres interpolés.
         * Achat de référence : uniquement SNS (arme non craftée, acquise 30k). Les craftées n’ont qu’un prix de vente.
         *
         * Production : après déploiement, exécuter les migrations puis ce seeder (ou db:seed) pour
         * sell_price / reference_purchase_price — sinon le simulateur affiche vente / marge vides.
         */
        $weapons = [
            ['name' => 'SNS', 'slug' => 'sns', 'craft_time_seconds' => 15, 'sell_price' => 60000, 'reference_purchase_price' => 30000, 'recipe_plans' => 1, 'recipe_ressort' => 1, 'recipe_canon' => 1, 'recipe_poignee' => 1, 'recipe_corp' => 1, 'recipe_metal' => 5, 'recipe_polymere' => 5, 'sort_order' => 1],
            ['name' => 'WN 29 Pistol', 'slug' => 'wn29', 'craft_time_seconds' => 15, 'sell_price' => 80000, 'reference_purchase_price' => null, 'recipe_plans' => 1, 'recipe_ressort' => 1, 'recipe_canon' => 1, 'recipe_poignee' => 1, 'recipe_corp' => 1, 'recipe_metal' => 10, 'recipe_polymere' => 5, 'sort_order' => 2],
            ['name' => 'Ceramic Pistol', 'slug' => 'ceramic', 'craft_time_seconds' => 15, 'sell_price' => 70000, 'reference_purchase_price' => null, 'recipe_plans' => 1, 'recipe_ressort' => 1, 'recipe_canon' => 1, 'recipe_poignee' => 1, 'recipe_corp' => 1, 'recipe_metal' => 5, 'recipe_polymere' => 5, 'sort_order' => 3],
            ['name' => 'Pistol', 'slug' => 'pistol', 'craft_time_seconds' => 15, 'sell_price' => 110000, 'reference_purchase_price' => null, 'recipe_plans' => 1, 'recipe_ressort' => 1, 'recipe_canon' => 1, 'recipe_poignee' => 1, 'recipe_corp' => 1, 'recipe_metal' => 5, 'recipe_polymere' => 10, 'sort_order' => 4],
            ['name' => 'Heavy Pistol', 'slug' => 'heavy', 'craft_time_seconds' => 25, 'sell_price' => 160000, 'reference_purchase_price' => null, 'recipe_plans' => 1, 'recipe_ressort' => 2, 'recipe_canon' => 1, 'recipe_poignee' => 1, 'recipe_corp' => 1, 'recipe_metal' => 10, 'recipe_polymere' => 10, 'sort_order' => 5],
            ['name' => 'Cal .50', 'slug' => 'cal50', 'craft_time_seconds' => 30, 'sell_price' => 200000, 'reference_purchase_price' => null, 'recipe_plans' => 1, 'recipe_ressort' => 2, 'recipe_canon' => 1, 'recipe_poignee' => 1, 'recipe_corp' => 1, 'recipe_metal' => 10, 'recipe_polymere' => 15, 'sort_order' => 6],
        ];

        foreach ($weapons as $w) {
            Weapon::updateOrCreate(['slug' => $w['slug']], $w);
        }

        // Raw materials
        $rawMaterials = [
            ['name' => 'Minerai de fer', 'slug' => 'minerai', 'sort_order' => 1],
            ['name' => 'Pétrole', 'slug' => 'petrole', 'sort_order' => 2],
        ];

        foreach ($rawMaterials as $rm) {
            WeaponStock::updateOrCreate(['slug' => $rm['slug']], array_merge($rm, ['category' => 'raw_material']));
        }

        // Intermediate pieces
        $pieces = [
            ['name' => 'Ressort', 'slug' => 'ressort', 'sort_order' => 1],
            ['name' => 'Canon', 'slug' => 'canon', 'sort_order' => 2],
            ['name' => 'Poignée', 'slug' => 'poignee', 'sort_order' => 3],
            ['name' => 'Corp de pistolet', 'slug' => 'corp', 'sort_order' => 4],
            ['name' => 'Pièce de métal', 'slug' => 'metal', 'sort_order' => 5],
            ['name' => 'Polymère', 'slug' => 'polymere', 'sort_order' => 6],
        ];

        foreach ($pieces as $p) {
            WeaponStock::updateOrCreate(['slug' => $p['slug']], array_merge($p, ['category' => 'piece']));
        }

        // Plans (per weapon) - quantity = uses available (1 physical plan = 4 uses)
        $order = 1;
        foreach (Weapon::orderBy('sort_order')->get() as $weapon) {
            WeaponStock::updateOrCreate(
                ['slug' => 'plan_' . $weapon->slug],
                ['category' => 'plan', 'weapon_id' => $weapon->id, 'name' => 'Plan ' . $weapon->name, 'sort_order' => $order++]
            );
        }

        // Finished weapons (per weapon)
        $order = 1;
        foreach (Weapon::orderBy('sort_order')->get() as $weapon) {
            WeaponStock::updateOrCreate(
                ['slug' => 'weapon_' . $weapon->slug],
                ['category' => 'finished_weapon', 'weapon_id' => $weapon->id, 'name' => $weapon->name . ' (finie)', 'sort_order' => $order++]
            );
        }
    }
}
