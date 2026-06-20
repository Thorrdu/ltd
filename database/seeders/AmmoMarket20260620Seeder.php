<?php

namespace Database\Seeders;

use App\Models\StockItem;
use Illuminate\Database\Seeder;

/**
 * Refonte du marché des munitions (20 juin 2026).
 *
 * Le craft ne consomme plus de fragments de fer mais un CORPS et une TÊTE
 * par cartouche (chaque craft produit toujours 10 munitions). Ces composants
 * ne se craftent PAS : ils sont récupérés via les braquages de containers ou
 * rachetés aux autres groupes. Le prix ci-dessous est le prix « juste » de
 * rachat inter-groupes (corps et tête au même prix).
 *
 * Ce seeder est INCRÉMENTAL (StockItemSeeder est déjà en prod) :
 *  1. crée les composants corps + tête (catégorie ammo_component) ;
 *  2. ajoute les calibres manquants (.22 LR, .44 Magnum) ;
 *  3. réajuste les prix/poids des munitions pour garder une marge correcte
 *     une fois les composants rachetés, sans prix excessif.
 */
class AmmoMarket20260620Seeder extends Seeder
{
    public function run(): void
    {
        $this->seedComponents();
        $this->updateAmmoPrices();
    }

    /**
     * Composants corps + tête par calibre.
     *
     * Prix = rachat inter-groupes (corps = tête). Comme TOUTES les recettes
     * consomment 10 corps + 10 têtes, le seul facteur qui différencie le coût
     * d'un craft est la quantité de poudre. On regroupe donc les calibres par
     * palier de recette : les calibres d'un même palier ont des composants au
     * même prix (donc même coût et même marge à la revente).
     *
     *  - Palier 5 poudre  : 9mm, .22 LR, .45 ACP, .38 LC      => composant 15
     *  - Palier 10 poudre : .50 AE, .44 Magnum                => composant 20
     *  - Palier 20 poudre : 5.56x45, 7.62x39, 7.62x51, .50 BMG => composant 30
     *  - Palier 30 poudre : 12 Gauge                          => composant 35
     */
    private function seedComponents(): void
    {
        // [calibre, libellé corps, libellé tête, prix unitaire (corps = tête)]
        $groups = [
            // Palier 5 poudre
            ['9mm',     'Corps de munition 9mm',              'Tête de munition 9mm',              15],
            ['22lr',    'Corps de munition .22',              'Tête de munition .22',              15],
            ['45acp',   'Corps de munition .45',              'Tête de munition .45',              15],
            ['38lc',    'Corps de munition .38',              'Tête de munition .38',              15],
            // Palier 10 poudre
            ['50ae',    'Corps de munition .50',              'Tête de munition .50',              20],
            ['44mag',   'Corps de munition .44',              'Tête de munition .44',              20],
            // Palier 20 poudre
            ['556x45',  'Corps de munition de fusil',         'Tête de munition de fusil',         30],
            ['762x39',  'Corps de munition de fusil avancée', 'Tête de munition de fusil avancée', 30],
            ['762x51',  'Corps de munition de sniper',        'Tête de munition de sniper',        30],
            ['50bmg',   'Corps de munition de sniper lourd',  'Tête de munition de sniper lourd',  30],
            // Palier 30 poudre
            ['12gauge', 'Corps de munition de fusil à pompe', 'Tête de munition de fusil à pompe', 35],
        ];
        $order = 10;
        foreach ($groups as [$cal, $corpsName, $teteName, $price]) {
            StockItem::updateOrCreate(['slug' => 'ammo_corps_' . $cal], [
                'category'               => 'ammo_component',
                'name'                   => $corpsName,
                'default_sell_price'     => $price,
                'default_purchase_price' => $price,
                'is_sellable'            => true,
                'is_active'              => true,
                'sort_order'             => $order,
            ]);
            StockItem::updateOrCreate(['slug' => 'ammo_tete_' . $cal], [
                'category'               => 'ammo_component',
                'name'                   => $teteName,
                'default_sell_price'     => $price,
                'default_purchase_price' => $price,
                'is_sellable'            => true,
                'is_active'              => true,
                'sort_order'             => $order + 5,
            ]);
            $order += 10;
        }
    }

    /**
     * Réajuste les munitions existantes et crée les calibres manquants.
     *
     * Le prix de vente est fixé PAR PALIER de recette : les calibres qui ont la
     * même recette (donc le même coût) ont le même prix et la même marge.
     * La marge augmente avec le palier (plus le calibre est cher à produire,
     * plus la marge absolue monte), sauf le 12 Gauge conservé à 500 €.
     *
     *  Palier 5 poudre  : coût 80 €  => vente 130 (marge +50)
     *  Palier 10 poudre : coût 140 € => vente 220 (marge +80)
     *  Palier 20 poudre : coût 260 € => vente 380 (marge +120)
     *  Palier 30 poudre : coût 370 € => vente 500 (marge +130, inchangé)
     */
    private function updateAmmoPrices(): void
    {
        $items = [
            // Palier 5 poudre — coût/mun 80 €
            ['slug' => 'ammo_9mm',     'name' => '9mm',            'price' => 130, 'weight' => 7],
            ['slug' => 'ammo_22lr',    'name' => '.22 Long Rifle', 'price' => 130, 'weight' => 3],
            ['slug' => 'ammo_45acp',   'name' => '.45 ACP',        'price' => 130, 'weight' => 15],
            ['slug' => 'ammo_38lc',    'name' => '.38 LC',         'price' => 130, 'weight' => 15],
            // Palier 10 poudre — coût/mun 140 €
            ['slug' => 'ammo_50ae',    'name' => '.50 AE',         'price' => 220, 'weight' => 45],
            ['slug' => 'ammo_44mag',   'name' => '.44 Magnum',     'price' => 220, 'weight' => 16],
            // Palier 20 poudre — coût/mun 260 €
            ['slug' => 'ammo_556x45',  'name' => '5.56x45',        'price' => 380, 'weight' => 4],
            ['slug' => 'ammo_762x39',  'name' => '7.62x39',        'price' => 380, 'weight' => 8],
            ['slug' => 'ammo_762x51',  'name' => '7.62x51',        'price' => 380, 'weight' => 9],
            ['slug' => 'ammo_50bmg',   'name' => '.50 BMG',        'price' => 380, 'weight' => 51],
            // Palier 30 poudre — coût/mun 370 €
            ['slug' => 'ammo_12gauge', 'name' => '12 Gauge',       'price' => 500, 'weight' => 38],
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
}
