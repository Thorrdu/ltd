<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // ── Raw materials ────────────────────────────────────
            ['group' => 'raw_materials', 'key' => 'iron_ore_price', 'label' => 'Prix minerai de fer (par unité)', 'type' => 'integer', 'value' => '30', 'description' => 'Prix d\'achat du minerai de fer', 'sort_order' => 1],
            ['group' => 'raw_materials', 'key' => 'metal_per_ore', 'label' => 'Métal obtenu par minerai', 'type' => 'integer', 'value' => '5', 'description' => 'Nombre de pièces de métal par minerai transformé', 'sort_order' => 2],
            ['group' => 'raw_materials', 'key' => 'polymere_per_petrole', 'label' => 'Polymère obtenu par pétrole', 'type' => 'integer', 'value' => '5', 'description' => 'Nombre de polymères par unité de pétrole transformée', 'sort_order' => 3],
            ['group' => 'raw_materials', 'key' => 'polymere_cost', 'label' => 'Prix polymère (achat direct)', 'type' => 'integer', 'value' => '4500', 'description' => 'Prix d\'achat d\'un polymère en direct', 'sort_order' => 4],
            ['group' => 'raw_materials', 'key' => 'gunpowder_price', 'label' => 'Prix poudre à canon (par unité)', 'type' => 'integer', 'value' => '100', 'description' => 'Prix d\'achat de la poudre à canon', 'sort_order' => 5],

            // ── Pieces / craft rates ─────────────────────────────
            ['group' => 'pieces', 'key' => 'craft_corp_price', 'label' => 'Prix craft corp de pistolet', 'type' => 'integer', 'value' => '15000', 'description' => 'Coût de fabrication d\'un corp de pistolet', 'sort_order' => 1],
            ['group' => 'pieces', 'key' => 'craft_piece_price', 'label' => 'Prix craft pièce d\'arme', 'type' => 'integer', 'value' => '5000', 'description' => 'Coût de fabrication d\'une pièce (ressort, canon, poignée)', 'sort_order' => 2],
            ['group' => 'pieces', 'key' => 'ressort_metal_rate', 'label' => 'Métal par ressort', 'type' => 'integer', 'value' => '1', 'description' => 'Nombre de pièces de métal pour un ressort', 'sort_order' => 3],
            ['group' => 'pieces', 'key' => 'ressort_ore_rate', 'label' => 'Minerai par ressort', 'type' => 'integer', 'value' => '3', 'description' => 'Nombre de minerais par ressort (si craft direct)', 'sort_order' => 4],
            ['group' => 'pieces', 'key' => 'plans_per_item', 'label' => 'Utilisations par plan', 'type' => 'integer', 'value' => '4', 'description' => 'Nombre de crafts possibles avec un plan physique', 'sort_order' => 5],

            // ── Ammo recipes ─────────────────────────────────────
            ['group' => 'ammo_recipes', 'key' => 'ammo_yield_per_craft', 'label' => 'Munitions par craft', 'type' => 'integer', 'value' => '10', 'description' => 'Nombre de munitions produites par craft', 'sort_order' => 1],
            ['group' => 'ammo_recipes', 'key' => 'ammo_fragments_per_fer', 'label' => 'Fragments par unité de fer', 'type' => 'integer', 'value' => '2', 'description' => 'Nombre de fragments obtenus par unité de fer', 'sort_order' => 2],

            // ── Sell multipliers ─────────────────────────────────
            ['group' => 'multipliers', 'key' => 'ammo_sell_threshold', 'label' => 'Seuil poudre vente munitions', 'type' => 'integer', 'value' => '50', 'description' => 'Seuil de coût poudre pour déterminer le markup', 'sort_order' => 1],
            ['group' => 'multipliers', 'key' => 'ammo_markup_small', 'label' => 'Markup munitions (petit calibre)', 'type' => 'float', 'value' => '2', 'description' => 'Multiplicateur de prix de vente petits calibres', 'sort_order' => 2],
            ['group' => 'multipliers', 'key' => 'ammo_markup_large', 'label' => 'Markup munitions (gros calibre)', 'type' => 'float', 'value' => '1.5', 'description' => 'Multiplicateur de prix de vente gros calibres', 'sort_order' => 3],
            ['group' => 'multipliers', 'key' => 'melee_sell_multiplier', 'label' => 'Multiplicateur armes blanches', 'type' => 'float', 'value' => '1.5', 'description' => 'Multiplicateur de prix de vente par défaut pour armes blanches', 'sort_order' => 4],

            // ── Cotisations ──────────────────────────────────────
            ['group' => 'cotisations', 'key' => 'cotisation_prospect', 'label' => 'Cotisation prospect (par semaine)', 'type' => 'integer', 'value' => '2000', 'description' => 'Montant hebdomadaire pour les prospects', 'sort_order' => 1],
            ['group' => 'cotisations', 'key' => 'cotisation_member', 'label' => 'Cotisation membre (par semaine)', 'type' => 'integer', 'value' => '5000', 'description' => 'Montant hebdomadaire pour les membres', 'sort_order' => 2],
            ['group' => 'cotisations', 'key' => 'cotisation_officer', 'label' => 'Cotisation officier (par semaine)', 'type' => 'integer', 'value' => '10000', 'description' => 'Montant hebdomadaire pour les officiers et au-dessus', 'sort_order' => 3],
        ];

        foreach ($settings as $s) {
            Setting::updateOrCreate(['key' => $s['key']], $s);
        }
    }
}
