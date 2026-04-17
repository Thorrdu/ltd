<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['group' => 'raw_materials', 'key' => 'iron_ore_price',    'label' => 'Prix minerai de fer (par unité)',   'type' => 'integer', 'value' => '30',   'description' => 'Prix d\'achat du minerai de fer',                                  'sort_order' => 1],
            ['group' => 'raw_materials', 'key' => 'ore_per_metal',     'label' => 'Minerais pour 1 pièce de métal',    'type' => 'integer', 'value' => '5',    'description' => 'Nombre de minerais nécessaires pour crafter 1 pièce de métal',     'sort_order' => 2],
            ['group' => 'raw_materials', 'key' => 'petrole_per_polymere', 'label' => 'Pétroles pour 1 polymère',        'type' => 'integer', 'value' => '5',    'description' => 'Nombre de pétroles nécessaires pour crafter 1 polymère',           'sort_order' => 3],
            ['group' => 'raw_materials', 'key' => 'polymere_cost',     'label' => 'Prix polymère (achat direct)',      'type' => 'integer', 'value' => '4500', 'description' => 'Prix d\'achat d\'un polymère en direct (tunnel)',                  'sort_order' => 4],
            ['group' => 'raw_materials', 'key' => 'gunpowder_price',   'label' => 'Prix poudre à canon (par unité)',   'type' => 'integer', 'value' => '100',  'description' => 'Prix d\'achat de la poudre à canon',                               'sort_order' => 5],
            ['group' => 'raw_materials', 'key' => 'fragments_per_ore', 'label' => 'Fragments par minerai',              'type' => 'integer', 'value' => '2',    'description' => '1 minerai de fer donne 2 fragments de métal',                     'sort_order' => 6],

            ['group' => 'pieces', 'key' => 'craft_corp_price',  'label' => 'Prix craft corp de pistolet',    'type' => 'integer', 'value' => '15000', 'description' => 'Coût de fabrication d\'un corp de pistolet',                'sort_order' => 1],
            ['group' => 'pieces', 'key' => 'craft_piece_price', 'label' => 'Prix craft pièce d\'arme',       'type' => 'integer', 'value' => '5000',  'description' => 'Coût de fabrication d\'une pièce (ressort, canon, poignée)', 'sort_order' => 2],
            ['group' => 'pieces', 'key' => 'ressort_metal_rate','label' => 'Pièces de métal par ressort',    'type' => 'integer', 'value' => '1',     'description' => '1 ressort = 1 pièce de métal + 3 minerais',                'sort_order' => 3],
            ['group' => 'pieces', 'key' => 'ressort_ore_rate',  'label' => 'Minerais par ressort',           'type' => 'integer', 'value' => '3',     'description' => 'Minerais consommés en plus de la pièce de métal',           'sort_order' => 4],
            ['group' => 'pieces', 'key' => 'plans_per_item',    'label' => 'Utilisations par plan',          'type' => 'integer', 'value' => '4',     'description' => 'Nombre de crafts possibles avec un plan physique',          'sort_order' => 5],

            ['group' => 'ammo_recipes', 'key' => 'ammo_yield_per_craft', 'label' => 'Munitions par craft', 'type' => 'integer', 'value' => '10', 'description' => 'Nombre de munitions produites par craft', 'sort_order' => 1],

            ['group' => 'cotisations', 'key' => 'cotisation_prospect', 'label' => 'Cotisation prospect (par semaine)', 'type' => 'integer', 'value' => '2000',  'description' => 'Montant hebdomadaire pour les prospects',          'sort_order' => 1],
            ['group' => 'cotisations', 'key' => 'cotisation_member',   'label' => 'Cotisation membre (par semaine)',   'type' => 'integer', 'value' => '5000',  'description' => 'Montant hebdomadaire pour les membres',            'sort_order' => 2],
            ['group' => 'cotisations', 'key' => 'cotisation_officer',  'label' => 'Cotisation officier (par semaine)', 'type' => 'integer', 'value' => '10000', 'description' => 'Montant hebdomadaire pour les officiers et au-dessus', 'sort_order' => 3],

            ['group' => 'rankings', 'key' => 'rankings.eligible_categories', 'label' => 'Categories eligibles pour le classement', 'type' => 'json', 'value' => '["drug","weapon_finished","ammo","melee","drug_raw","misc"]', 'description' => 'Categories de stock_items qui comptent dans le classement', 'sort_order' => 1],
            ['group' => 'rankings', 'key' => 'rankings.criteria',             'label' => 'Critere de classement',                  'type' => 'string', 'value' => 'revenue', 'description' => 'revenue = CA, count = nb ventes, quantity = qte totale', 'sort_order' => 2],
        ];

        $keys = array_column($settings, 'key');

        Setting::whereNotIn('key', $keys)->delete();

        foreach ($settings as $s) {
            Setting::updateOrCreate(['key' => $s['key']], $s);
        }
    }
}
