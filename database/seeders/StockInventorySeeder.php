<?php

namespace Database\Seeders;

use App\Models\StockItem;
use Illuminate\Database\Seeder;

/**
 * Seeds the actual stock quantities observed in the organisation storage
 * (screenshots du stockage Lost MC -- 16 avril 2026).
 *
 * Deux operations :
 *  1. Met a jour la quantity des items DEJA seedes par StockItemSeeder
 *     (munitions, pieces, armes, matieres, armes blanches, drogues).
 *  2. Cree les items manquants (crosse, corps de SMG/fusils, plans hors
 *     catalogue, consommables agricoles, outils, electronique, argent propre / sale,
 *     variantes de drogues, etc.).
 *
 * Ce seeder s'execute APRES StockItemSeeder via DatabaseSeeder.
 */
class StockInventorySeeder extends Seeder
{
    public function run(): void
    {
        $this->updateExistingQuantities();
        $this->seedWeaponPiecesExtra();
        $this->seedPlansExtra();
        $this->seedDrugsExtra();
        $this->seedFarmConsumables();
        $this->seedTools();
        $this->seedElectronics();
        $this->seedMisc();
    }

    /**
     * MAJ des quantites sur les slugs deja existants. Tous les slugs listes
     * ici DOIVENT exister dans StockItemSeeder (sinon la MAJ est silencieuse).
     */
    private function updateExistingQuantities(): void
    {
        $quantities = [
            // Matieres premieres
            'poudre'         => 691,
            'fragment_metal' => 640,

            // Pieces armurerie
            'corp'     => 21,
            'canon'    => 16,
            'poignee'  => 10,
            'ressort'  => 30,
            'metal'    => 174,

            // Munitions
            'ammo_45acp'   => 150,
            'ammo_9mm'     => 308,
            'ammo_50ae'    => 488,
            'ammo_12gauge' => 561,
            'ammo_556x45'  => 314,
            'ammo_50bmg'   => 20,
            'ammo_762x51'  => 16,
            'ammo_762x39'  => 1191,

            // Armes finies (comptees physiquement sur les captures)
            'weapon_cal50' => 3,   // 3 Pistol .50 (poids differents = armes distinctes)
            'weapon_sns'   => 4,   // 4 SNS Pistol

            // Armes blanches
            'melee_knife'  => 1,
            'melee_katana' => 1,

            // Plans (weapon_plan) - ceux lies a un weapon
            'plan_cal50'  => 5,
            'plan_ak47'   => 1,
            'plan_pistol' => 1,

            // Drogues : on repartit les stacks visibles sur les 3 variantes principales
            // (les captures ne differencient pas les strains, choix prudent).
            'drug_weed_bluedream'  => 252,
            'drug_weed_whitewidow' => 12,
            'drug_weed_purple'     => 6,
            'drug_meth_haute'      => 14,
        ];

        foreach ($quantities as $slug => $qty) {
            StockItem::where('slug', $slug)->update(['quantity' => $qty]);
        }
    }

    /**
     * Nouvelles pieces armurerie visibles en stock mais absentes du catalogue.
     */
    private function seedWeaponPiecesExtra(): void
    {
        $pieces = [
            ['slug' => 'corp_smg',   'name' => 'Corps de SMG',    'price' => 25000, 'weight' => 1500, 'qty' => 13, 'sort' => 10],
            ['slug' => 'corp_rifle', 'name' => 'Corps de fusil',  'price' => 40000, 'weight' => 2000, 'qty' => 10, 'sort' => 11],
            ['slug' => 'crosse',     'name' => 'Crosse',          'price' => 5000,  'weight' => 400,  'qty' => 10, 'sort' => 12],
            ['slug' => 'suppressor',          'name' => 'Suppresseur',          'price' => 15000, 'weight' => 280, 'qty' => 1, 'sort' => 20],
            ['slug' => 'tactical_suppressor', 'name' => 'Suppresseur tactique', 'price' => 20000, 'weight' => 280, 'qty' => 1, 'sort' => 21],
        ];

        foreach ($pieces as $p) {
            StockItem::updateOrCreate(['slug' => $p['slug']], [
                'category'               => 'weapon_piece',
                'name'                   => $p['name'],
                'default_sell_price'     => $p['price'],
                'default_purchase_price' => $p['price'],
                'unit_weight_g'          => $p['weight'],
                'quantity'               => $p['qty'],
                'is_sellable'            => true,
                'is_active'              => true,
                'sort_order'             => $p['sort'],
            ]);
        }
    }

    /**
     * Plans d'armes non liees au catalogue weapons actuel (MG, Machine Pistol,
     * Mini SMG, Fusil a pompe, Combat PDW, AK Complet...).
     * weapon_id reste null : ces plans ne craftent rien via le simulateur
     * mais ils existent en stock et peuvent etre vendus ou attribues.
     */
    private function seedPlansExtra(): void
    {
        $plans = [
            ['slug' => 'plan_mg',             'name' => 'Plan MG',             'qty' => 1, 'sort' => 20],
            ['slug' => 'plan_combat_pdw',     'name' => 'Plan Combat PDW',     'qty' => 2, 'sort' => 21],
            ['slug' => 'plan_combat_pistol',  'name' => 'Plan Combat Pistol',  'qty' => 1, 'sort' => 22],
            ['slug' => 'plan_machine_pistol', 'name' => 'Plan Machine Pistol', 'qty' => 2, 'sort' => 23],
            ['slug' => 'plan_fusil_pompe',    'name' => 'Plan Fusil a pompe',  'qty' => 1, 'sort' => 24],
            ['slug' => 'plan_mini_smg',       'name' => 'Plan Mini SMG',       'qty' => 1, 'sort' => 25],
            ['slug' => 'plan_ak_complet',     'name' => 'Plan AK complet',     'qty' => 1, 'sort' => 26],
        ];

        foreach ($plans as $p) {
            StockItem::updateOrCreate(['slug' => $p['slug']], [
                'category'           => 'weapon_plan',
                'name'               => $p['name'],
                'default_sell_price' => 10000,
                'quantity'           => $p['qty'],
                'is_sellable'        => true,
                'is_active'          => true,
                'sort_order'         => $p['sort'],
                'notes'              => 'Plan hors catalogue craft (aucune recette en DB)',
            ]);
        }
    }

    /**
     * Variantes de drogues/conditionnement vues en stock (cocaine pure, briques,
     * sachets de weed conditionnes, joints purple, cook en poudre, etc.).
     */
    private function seedDrugsExtra(): void
    {
        $drugs = [
            ['slug' => 'drug_cocaine',          'name' => 'Cocaine (pure)',      'price' => 1200, 'weight' => 70,   'qty' => 50,  'sort' => 200, 'notes' => null],
            ['slug' => 'drug_brique_weed',      'name' => 'Brique de weed',      'price' => 1000, 'weight' => 100,  'qty' => 2,   'sort' => 210, 'notes' => 'Bloc de weed compresse'],
            ['slug' => 'drug_brique_cocaine',   'name' => 'Brique de cocaine',   'price' => 4000, 'weight' => 200,  'qty' => 2,   'sort' => 211, 'notes' => 'Bloc de cocaine compresse'],
            ['slug' => 'drug_sachet_weed',      'name' => 'Sachet de weed',      'price' => 150,  'weight' => 5,    'qty' => 905, 'sort' => 220, 'notes' => 'Weed conditionnee pour revente'],
            ['slug' => 'drug_joint_purple',     'name' => 'Joint (Purple)',      'price' => 250,  'weight' => 3,    'qty' => 99,  'sort' => 221, 'notes' => 'Joint pret-a-fumer'],
            ['slug' => 'drug_poudre_cafe',      'name' => 'Poudre de cafe',      'price' => 100,  'weight' => 500,  'qty' => 1,   'sort' => 230, 'notes' => 'Agent de coupe'],
        ];

        foreach ($drugs as $d) {
            StockItem::updateOrCreate(['slug' => $d['slug']], [
                'category'           => 'drug',
                'name'               => $d['name'],
                'default_sell_price' => $d['price'],
                'unit_weight_g'      => $d['weight'],
                'quantity'           => $d['qty'],
                'is_sellable'        => true,
                'is_active'          => true,
                'sort_order'         => $d['sort'],
                'notes'              => $d['notes'],
            ]);
        }
    }

    /**
     * Consommables agricoles (engrais, pesticide, graines, feuilles, sachets).
     */
    private function seedFarmConsumables(): void
    {
        $items = [
            ['slug' => 'farm_engrais_vitesse',  'name' => 'Engrais (vitesse)',  'price' => 500,  'weight' => 500, 'qty' => 24,   'sort' => 10, 'notes' => 'Accelere la croissance'],
            ['slug' => 'farm_engrais_booster',  'name' => 'Engrais (booster)',  'price' => 800,  'weight' => 500, 'qty' => 2,    'sort' => 11, 'notes' => 'Boost rendement'],
            ['slug' => 'farm_spray_pesticide',  'name' => 'Spray pesticide',    'price' => 400,  'weight' => 500, 'qty' => 1,    'sort' => 12, 'notes' => null],
            ['slug' => 'farm_feuille_rouler',   'name' => 'Feuille a rouler',   'price' => 5,    'weight' => 1,   'qty' => 48,   'sort' => 20, 'notes' => null],
            ['slug' => 'farm_sachet_plastique', 'name' => 'Sachet plastique',   'price' => 10,   'weight' => 1,   'qty' => 1140, 'sort' => 21, 'notes' => null],
            ['slug' => 'farm_seed_weed_bluedream',  'name' => 'Graine de weed - Blue Dream',  'price' => 500, 'weight' => 1, 'qty' => 28, 'sort' => 30, 'notes' => null],
            ['slug' => 'farm_seed_weed_whitewidow', 'name' => 'Graine de weed - White Widow', 'price' => 300, 'weight' => 1, 'qty' => 10, 'sort' => 31, 'notes' => null],
            ['slug' => 'farm_seed_weed_purple',     'name' => 'Graine de weed - Purple',      'price' => 200, 'weight' => 1, 'qty' => 5,  'sort' => 32, 'notes' => null],
        ];

        foreach ($items as $it) {
            StockItem::updateOrCreate(['slug' => $it['slug']], [
                'category'           => 'farm_consumable',
                'name'               => $it['name'],
                'default_sell_price' => $it['price'],
                'unit_weight_g'      => $it['weight'],
                'quantity'           => $it['qty'],
                'is_sellable'        => true,
                'is_active'          => true,
                'sort_order'         => $it['sort'],
                'notes'              => $it['notes'],
            ]);
        }
    }

    /**
     * Outils (menottes, decoupeurs, meuleuses, foreuses, outils de crochetage).
     */
    private function seedTools(): void
    {
        $items = [
            ['slug' => 'tool_menottes',        'name' => 'Menottes',              'price' => 5000,  'weight' => 300,  'qty' => 3, 'sort' => 10, 'notes' => null],
            ['slug' => 'tool_decoupeur_plasma','name' => 'Decoupeur plasma',      'price' => 75000, 'weight' => 1000, 'qty' => 2, 'sort' => 20, 'notes' => 'Outil de decoupe'],
            ['slug' => 'tool_meuleuse_angle',  'name' => 'Meuleuse d\'angle',     'price' => 15000, 'weight' => 1500, 'qty' => 6, 'sort' => 21, 'notes' => null],
            ['slug' => 'tool_foreuse',         'name' => 'Foreuse',               'price' => 20000, 'weight' => 6000, 'qty' => 1, 'sort' => 22, 'notes' => null],
            ['slug' => 'tool_crochetage',      'name' => 'Outil de crochetage',   'price' => 8000,  'weight' => 300,  'qty' => 1, 'sort' => 23, 'notes' => null],
        ];

        foreach ($items as $it) {
            StockItem::updateOrCreate(['slug' => $it['slug']], [
                'category'           => 'tool',
                'name'               => $it['name'],
                'default_sell_price' => $it['price'],
                'unit_weight_g'      => $it['weight'],
                'quantity'           => $it['qty'],
                'is_sellable'        => true,
                'is_active'          => true,
                'sort_order'         => $it['sort'],
                'notes'              => $it['notes'],
            ]);
        }
    }

    /**
     * Electronique (cles USB, ecrans, cartes, machines de traitement, cuivre).
     */
    private function seedElectronics(): void
    {
        $items = [
            ['slug' => 'elec_cle_usb_phantom',     'name' => 'Cle USB Phantom',     'price' => 50000, 'weight' => 100,  'qty' => 1, 'sort' => 10, 'notes' => null],
            ['slug' => 'elec_grand_ecran',         'name' => 'Grand ecran',         'price' => 25000, 'weight' => 200,  'qty' => 1, 'sort' => 20, 'notes' => null],
            ['slug' => 'elec_petit_ecran',         'name' => 'Petit ecran',         'price' => 8000,  'weight' => 100,  'qty' => 3, 'sort' => 21, 'notes' => null],
            ['slug' => 'elec_carte_electronique',  'name' => 'Carte electronique',  'price' => 12000, 'weight' => 50,   'qty' => 3, 'sort' => 30, 'notes' => null],
            ['slug' => 'elec_carte_piratage',      'name' => 'Carte de piratage',   'price' => 30000, 'weight' => 100,  'qty' => 1, 'sort' => 31, 'notes' => null],
            ['slug' => 'elec_machine_traitement',  'name' => 'Machine de traitement','price'=> 80000, 'weight' => 2500, 'qty' => 2, 'sort' => 40, 'notes' => null],
            ['slug' => 'elec_fil_cuivre',          'name' => 'Fil de cuivre',       'price' => 500,   'weight' => 10,   'qty' => 2, 'sort' => 50, 'notes' => null],
        ];

        foreach ($items as $it) {
            StockItem::updateOrCreate(['slug' => $it['slug']], [
                'category'           => 'electronic',
                'name'               => $it['name'],
                'default_sell_price' => $it['price'],
                'unit_weight_g'      => $it['weight'],
                'quantity'           => $it['qty'],
                'is_sellable'        => true,
                'is_active'          => true,
                'sort_order'         => $it['sort'],
                'notes'              => $it['notes'],
            ]);
        }
    }

    /**
     * Divers : sacs et items non classifies ailleurs.
     * L'argent propre et l'argent sale partagent la categorie `argent`.
     */
    private function seedMisc(): void
    {
        $items = [
            ['slug' => 'misc_sac_tete', 'name' => 'Sac a mettre sur la tete', 'price' => 2000, 'weight' => 100, 'qty' => 4, 'sort' => 10, 'notes' => null],
        ];

        foreach ($items as $it) {
            StockItem::updateOrCreate(['slug' => $it['slug']], [
                'category'           => 'misc',
                'name'               => $it['name'],
                'default_sell_price' => $it['price'],
                'unit_weight_g'      => $it['weight'] ?: null,
                'quantity'           => $it['qty'],
                'is_sellable'        => true,
                'is_active'          => true,
                'sort_order'         => $it['sort'],
                'notes'              => $it['notes'],
            ]);
        }

        StockItem::updateOrCreate(['slug' => 'misc_argent_sale'], [
            'category'           => 'argent',
            'name'               => 'Argent sale',
            'default_sell_price' => 1,
            'unit_weight_g'      => null,
            'quantity'           => 561235,
            'is_sellable'        => false,
            'is_active'          => true,
            'sort_order'         => 1,
            'notes'              => 'Montant en $, non vendable directement',
        ]);

        StockItem::updateOrCreate(['slug' => 'cash_argent_propre'], [
            'category'           => 'argent',
            'name'               => 'Argent propre',
            'default_sell_price' => 1,
            'unit_weight_g'      => null,
            'quantity'           => 0,
            'is_sellable'        => false,
            'is_active'          => true,
            'sort_order'         => 2,
            'notes'              => 'Montant en $, non vendable directement',
        ]);
    }
}
