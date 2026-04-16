# Active Context - Station LTD / Toolbox Lost MC

## Travail en cours
Phase 3 **LIVRÉE** le 16 avril 2026 (soir) : module stocks générique complet avec page
`/stocks` (officier+), formulaire d'attribution, page détail `/stocks/{slug}`, flux de
réconciliation, validations trésorier (seuil configurable) et import CSV. Le formulaire
de vente résiduel de `/espace-membres` a été supprimé : la saisie passe exclusivement
par `/ventes` (redirige avec `stock_item_id`/`quantity`/`attribution_id` en query string).

Ajout 16 avril (soir, post-Phase 3) :
- **`StockInventorySeeder`** : peuple les quantités observées dans le stockage Lost MC
  (captures Discord) et crée les items manquants (crosse, corps SMG/fusils, suppresseurs,
  plans hors catalogue craft, variantes drogues, consommables agricoles, outils,
  électronique, argent sale, sacs). 97 items au total après seed.
- **Vente hors catalogue** dans `/ventes` : toggle "Article hors catalogue" qui remplace
  le select par deux champs (nom + catégorie). Le backend (`SaleController::apiCreate`)
  crée à la volée un `stock_item` (slug `adhoc_*`) avec quantity=0 qui passe en négatif
  après la vente (à régulariser via `/stocks`).
- **Édition des fiches articles** sur `/stocks/{slug}` : nouveau endpoint
  `PUT /stocks/api/item/{slug}` (officier+, scope `stocks_generique`) qui accepte
  name, category, default_sell_price, default_purchase_price, unit_weight_g,
  is_sellable, is_active, notes. Un `StockMovement` reason=`adjustment` qty=0 est
  créé avec un résumé des champs modifiés pour la traçabilité. Côté UI : bouton
  "Modifier" dans le bloc "Fiche article" qui révèle un formulaire inline.

## Phase 3 - Module stocks générique (16 avril 2026, soir) -- LIVRÉE

### Fonctionnalités livrées
- **Page `/stocks`** (officier+) avec sous-onglets :
  - Vue d'ensemble : jauge capacité (kg), totaux par catégorie, tableau filtrable par
    catégorie + recherche texte. Affiche quantité en stock ET quantité "en exterieur"
    (somme des attributions ouvertes).
  - Attribuer : formulaire `stock_item_id` + `quantity` + `attributed_to_user_id` + notes.
  - Attributions en cours : liste des attributions ouvertes (scope `all` pour officier,
    `mine` pour member), boutons Vendu / Retour / Perte / Don.
  - Validations : tresorier+ seulement. Liste des attributions en attente d'approbation.
  - Import : tresorier+ seulement. Textarea CSV, preview, commit.
- **Page `/stocks/{slug}`** : détail complet d'un item (stats, attributions en cours,
  mouvements récents).
- **Onglet "Mes attributions"** sur `/espace-membres` : liste des attributions ouvertes
  du membre connecté avec boutons de réconciliation. L'ancien onglet "Ventes" est
  renommé "Mouvements" (historique seul). L'ancienne carte "Déclarer une vente" du
  dashboard redirige désormais vers `/ventes` (pas de saisie in-place).

### Backend ajouté
- **Migration additive** `2026_04_16_181908_add_attribution_fields_to_stock_movements` :
  `reconciled_at`, `reconciled_by_movement_id` (self FK), `requires_approval`,
  `approved_by_user_id`, `approved_at`, `rejected_at`, `rejection_reason`.
- **`StockMovement`** : scopes `openAttribution()`, `pendingApproval()`, méthode
  `isOpenAttribution()`, relations `approvedBy()` et `reconciledByMovement()`.
- **`StockController`** (nouveau) : méthodes `index()`, `show($slug)`, `apiList()`,
  `apiItem($slug)`, `apiAttributions()`, `apiAttribute()`, `apiReconcile($id)`,
  `apiValidationsList()`, `apiApprove($id)`, `apiReject($id)`, `apiImportPreview()`,
  `apiImportCommit()`. Auth via header `X-Sim-User` + vérification rôle via
  `page_access_rules`.
- **`SaleController::apiCreate`** : accepte un `attribution_id` optionnel. Si présent
  et valide, l'attribution est réconciliée (lien `reconciled_by_movement_id` vers la
  vente) et le stock n'est PAS redécrémenté (déjà décrémenté par l'attribution).
- **Routes** `routes/web.php` : GET `/stocks`, GET `/stocks/{slug}`, + endpoints API
  `/stocks/api/list`, `/stocks/api/item/{slug}`, `/stocks/api/attributions`,
  `/stocks/api/attribute`, `/stocks/api/reconcile/{id}`, `/stocks/api/validations`,
  `/stocks/api/approve/{id}`, `/stocks/api/reject/{id}`, `/stocks/api/import/preview`,
  `/stocks/api/import/commit`. Le wildcard `{slug}` est placé après les API pour
  éviter les collisions.

### Seeders mis à jour
- `PageAccessRuleSeeder` : nouvelles règles `stocks_generique` (officer+),
  `stocks_validations` (treasurer+), `stocks_import` (treasurer+).
- `SettingSeeder` : groupe `stocks` avec `attribution_approval_threshold` (int, default 0
  = validation désactivée) et `stock_max_capacity_kg` (int, default 1000).

### Frontend ajouté
- `resources/views/stocks.blade.php` + `public/js/stocks.js` : page principale.
- `resources/views/stocks-detail.blade.php` + `public/js/stocks-detail.js` : page détail.
- `resources/views/espace-membres.blade.php` : onglet "Mes attributions" avec inline JS,
  suppression du formulaire de vente (carte avec bouton "Ventes rapides" qui redirige).
- `public/js/simulateur-armes.js` : quick-sell cards redirigent vers `/ventes?stock_item_id=X&quantity=Y`
  au lieu de remplir un formulaire in-page.
- `public/css/mc-layout.css` : styles `.stocks-*`, `.att-*`, `.imp-*` ajoutés.
- `resources/views/layouts/mc.blade.php` + `mc-hub.blade.php` : lien "Stocks" (officier+).

### Tests
Script de test (supprimé après exécution) exerçant le flux complet :
attribute → listAttributions → reconcile(return) → stock restauré ; nouvelle attribution
→ sale avec `attribution_id` → réconciliation sans double-decrement ; import CSV preview
avec detection des slugs inconnus. Tous les cas passent (HTTP 200, quantités cohérentes,
`reconciled_at` renseigné).

## Phase H - Harmonisation du stock (16 avril 2026, soir tardif) -- LIVRÉE

### Avant : désordre
- `weapon_stocks` (armurerie) + `weapon_stock_movements` + `weapon_sales`
- `sales` (générique, créée Phase 2) avec item_type/item_id
- `stock_items` (doublon de weapon_stocks, créé brièvement puis supprimé)
- Deux chemins écrivant dans des tables différentes : `/espace-membres` vs `/ventes`

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
1. **Phase 4 – drogues** : flux complet achat orga / attribution / reconciliation.
   Les 14 items `drug` sont déjà seedés, `/stocks` les gère déjà en lecture. Il reste
   le formulaire d'achat orga dédié et le dashboard drogue (profit/perte cumulé).
2. **Phase 5 – armes blanches** : 10 items `melee` déjà seedés et vendables via `/ventes`.
   Reste uniquement à rendre le multiplicateur x1.5 configurable via `settings`.
3. **Phase 6 – classements + fiches membres** : leaderboard global/mois/semaine,
   pages `/membres/{id}` avec historique complet (attributions, ventes, cotisations).
4. **Phase 7 – comptabilité MC** : argent sale/propre, transactions, cotisations
   avec validation trésorier.
5. **Phase 8 – polissage** : responsive, notifications, dashboards par rôle.

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
- Le formulaire de vente résiduel de `/espace-membres` a été retiré en Phase 3 :
  toute saisie de vente se fait désormais via `/ventes` (point d'entrée unique).
- Les POST vers les endpoints `/stocks/api/*` nécessitent le header `X-CSRF-TOKEN`
  (fournit par `window.McAuth.getCsrfToken()`). Tests automatisés doivent bypass le
  middleware ou instancier les contrôleurs directement.
