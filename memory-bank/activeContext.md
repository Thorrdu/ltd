# Active Context - Station LTD / Toolbox Lost MC

## Travail en cours
Phase H **LIVRÉE** le 16 avril 2026 (soir tardif) : rationalisation complète du schéma
stock / mouvements / ventes. Il ne reste plus qu'UNE SEULE table pour chaque concept.
Les tables `weapon_stocks`, `weapon_stock_movements` et `weapon_sales` ont disparu.

## Phase H - Harmonisation du stock (16 avril 2026, soir tardif) -- LIVRÉE

### Avant : désordre
- `weapon_stocks` (armurerie) + `weapon_stock_movements` + `weapon_sales`
- `sales` (générique, créée Phase 2) avec item_type/item_id
- `stock_items` (doublon de weapon_stocks, créé brièvement puis supprimé)
- Deux chemins écrivant dans des tables différentes : `/espace-membres` vs `/ventes`

### Après : 3 tables uniques

**`stock_items`** (le seul catalogue / stock, indexé par `category` + `slug` unique)
- Colonnes : `category`, `slug`, `name`, `weapon_id` nullable, `quantity`, `unit_weight_g`,
  `default_sell_price`, `default_purchase_price`, `is_sellable`, `is_active`,
  `sort_order`, `notes`, timestamps.
- 12 catégories : `weapon_finished`, `weapon_plan`, `weapon_piece`, `raw_material`, `ammo`,
  `melee`, `drug`, `drug_raw`, `farm_consumable`, `tool`, `electronic`, `misc`.

**`stock_movements`** (journal unique de TOUT mouvement de stock)
- `stock_item_id` FK, `quantity_change`, `reason` (purchase, gather, craft_consume,
  craft_produce, sale, delivery, attribution, adjustment), `unit_cost`, `weapon_contract_id`
  nullable, `user_id`, `attributed_to_user_id` nullable, `notes`, `created_at`.

**`sales`** (la seule table de ventes, tout type d'article)
- `stock_item_id` FK obligatoire, `quantity`, `unit_price`, `total_price`, `buyer_name`,
  `sold_by_user_id`, `weapon_contract_id` nullable, `validated_by_user_id`, `validated_at`,
  `notes`.

### Modèles supprimés
- `App\Models\WeaponStock`, `App\Models\WeaponStockMovement`, `App\Models\WeaponSale`.

### Modèles créés / mis à jour
- `App\Models\StockItem` : modèle unifié avec constantes `CATEGORIES` et `CATEGORY_COLORS`,
  scopes `active()`, `sellable()`, `ofCategory($cat)`, méthodes `addQuantity()`, `removeQuantity()`.
- `App\Models\StockMovement` : constante `REASONS`, relations vers `stockItem`, `user`,
  `attributedTo`, `contract`. `$timestamps = false` (seul `created_at` est tracé).
- `App\Models\Sale` simplifié : plus de `item_type`/`item_id`/`item_name` libres,
  tout passe par `stock_item_id`. Scope `inPeriod($period)` ajouté.
- `App\Models\Weapon` : `stockItems()` (pluriel), helpers `planStockItem()` et `finishedStockItem()`.
- `App\Models\WeaponContract` : relations `movements()` et `sales()` pointent vers les
  nouveaux modèles unifiés.

### Controllers refondus
- `WeaponSimController::apiData()` : charge catégories `weapon_*` et `raw_material` depuis
  `stock_items`, mouvements depuis `stock_movements`, ventes d'armes depuis `sales`
  (filtré par `stockItem.category = weapon_finished`).
- `WeaponSimController::createSale()` : trouve le `stock_item` via `slug = 'weapon_'.$weapon->slug`,
  décrémente, crée le `StockMovement` (reason=sale) et la `Sale`.
- `WeaponSimController::createMovement()` : attend `stock_item_id` (plus `weapon_stock_id`).
- `SaleController::apiCreate()` : une seule table, le payload est `stock_item_id` +
  `quantity` + `total_price` + `buyer_name`. Pour `weapon_finished`, décrément auto + mouvement.
- `SaleController::loadCatalog()` : renvoie `stock_items` `is_sellable=true` des catégories
  vendables (weapon_finished, ammo, melee, drug, drug_raw, misc).

### Filament (auto-découverte)
- Nouvelles resources : `StockItemResource`, `StockMovementResource`, `SaleResource`.
  Groupes : Stock (StockItem + StockMovement) / Finance (Sale) / Armes (Weapon, Contract) /
  Contrats.
- `StockItemResource` affiche toutes les catégories avec filtres, action inline « Ajuster »
  qui crée automatiquement un `StockMovement`.
- `StockMovementResource` en lecture seule (pas d'edit ni de delete) avec création manuelle.
- `SaleResource` en lecture seule.
- `ArmurerieStatsWidget` aligné : lit `stock_items` + `sales`.
- `CraftWeapon` (page) : toutes les requêtes passent par `StockItem` et `StockMovement`.

### Seeders
- `WeaponSeeder` ne s'occupe plus que de la table `weapons` (métadonnées des recettes).
- `StockItemSeeder` devient la source unique pour peupler le stock : matières premières,
  pièces armurerie, plans, armes finies, munitions, armes blanches, drogues. 54 articles
  seedés répartis sur 7 catégories effectives.

### Front
- `public/js/simulateur-armes.js` : renommage catégories `finished_weapon` → `weapon_finished`,
  `piece` → `weapon_piece`, `plan` → `weapon_plan`. Payload mouvement = `stock_item_id`.
- `public/js/ventes.js` entièrement réécrit : le catalog est groupé par catégorie via
  optgroups Tom Select, payload = `stock_item_id` + `quantity` + `total_price`.
- Les slugs de stock restent stables (`weapon_<arme>`, `plan_<arme>`, `ressort`, `canon`,
  `poignee`, `corp`, `metal`, `polymere`, `minerai`, `petrole`) donc le simulateur de
  craft continue de fonctionner sans modification de logique.

### Vérifications
- `php artisan migrate:fresh --seed` OK (23 migrations, 8 seeders).
- 54 stock_items seedés : weapon_finished=7, weapon_plan=7, weapon_piece=6, raw_material=2,
  ammo=8, melee=10, drug=14.
- `php artisan optimize:clear` puis tests HTTP : `/mc`=200, `/ventes`=200,
  `/espace-membres`=200, `/simulateur-armes`=200, `/armurerie`=302 (redirige vers login, OK).

## Prochaines étapes
1. **Phase 3 – stocks génériques** : UI d'attribution officier → membre sur le journal
   `stock_movements`, reason `attribution`. La structure est en place (colonne
   `attributed_to_user_id`).
2. **Phase 4 – drogues** : flux complet achat orga / attribution / reconciliation.
   Les 14 items `drug` sont déjà seedés, il reste le frontend.
3. **Phase 5 – armes blanches** : 10 items `melee` déjà seedés, à brancher sur `/ventes`.
4. **Phase 6 – classements + fiches membres** : leaderboard global/mois/semaine.
5. **Phase 7 – comptabilité MC** : argent sale/propre, transactions, cotisations.
6. **Phase 8 – polissage** : responsive, notifications, dashboards par rôle.

## Décisions actives
- Laravel 12 + Filament 5.3, deux panneaux Filament (admin / armurerie).
- Rôles via champ `role` sur User (constante `User::ROLES`), pas de Spatie Permissions.
- Hiérarchie : prospect (1) < member (2) < officer (3) < vice_president (4) < president (5)
  < treasurer (99 = superadmin).
- Accès aux pages piloté par la table `page_access_rules`.
- Auth MC via PIN (header `X-Sim-User`).
- **Une seule table pour chaque concept** (stock_items, stock_movements, sales).
  Toute nouvelle verticale (drogues, armes blanches, etc.) DOIT passer par ces tables
  via la colonne `category`.
- Selects front : Tom Select 2.3.1, thème dark partagé.
- Motto « Le Tout-Puissant pardonne. Pas les Lost. » sur toutes les pages MC.

## Problèmes connus
- Vhost Laragon pointe encore sur la racine du projet ; on accède via `/public/<route>`.
- Le formulaire de vente de `/espace-membres` reste actif et écrit dans la même table
  `sales` via `WeaponSimController::createSale` : plus de doublon fonctionnel,
  mais à terme on peut supprimer ce formulaire et rediriger vers `/ventes` (un seul
  endroit pour saisir une vente).
