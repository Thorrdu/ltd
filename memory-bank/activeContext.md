# Active Context - Station LTD / Toolbox Lost MC

## Travail en cours

### Session du 25 avril 2026 — Simulateurs armes et munitions

**Import stock CSV** :
- Fichier `database/csv/stock_import_2026-04-25.csv` crée avec 76 lignes (slug;quantity) depuis captures in-game.

**Simulateur munitions — corrections** :
- Fix JS cassé : code dupliqué après la closure IIFE `})();` supprimé.
- Simulateur du bas supprimé (fonction `updateAmmoTargetSim` + listeners + select + section HTML).
- Prix de vente chargés depuis la DB (`window.AMMO_SELL_PRICES` via controller) au lieu de valeurs en dur.
- Marges réelles : ajout `gFullCA`/`gFullCR` pour tracker le coût réel de TOUTES les munitions (y compris stock). Sections "Coût réel total" et "Marge réelle" par calibre. Bilan financier utilise les marges réelles.
- Section stock renommée "Investissement restant" (ce qu'il faut acheter) + "Économie grâce au stock".
- Cache-buster ajouté sur le `<script>` tag : `?v={{ filemtime(...) }}`.

**Simulateur armes — corrections** :
- Simulateur du bas supprimé (`updateWeaponTargetSim`, `weaponStockPaidUnits`, `weaponCraftOrderCost`, `weaponStockReadFromForm`, `parseEuroOptionalInput` + listeners + section HTML "Objectif en armes").
- Tec 9 (doublon de Mini SMG) : `is_active => false` dans la migration d'ajout SMG/rifle.
- **Déduction stock composants** (simulateur du haut) :
  - Checkbox "Déduire les composants en stock" dans le blade template (même pattern que munitions).
  - Inputs dynamiques créés en JS : plan par arme (sauf SNS) + composants partagés (ressort, canon, poignée, corp, crosse, corp_smg, corp_rifle, métal, polymère).
  - Auto-remplissage depuis l'API `/simulateur-armes/api/data` (stock items) via `fillWeaponStockFields()` appelé après `loadDashboardData()`.
  - `calculate()` utilise les valeurs des inputs quand la checkbox est cochée : section "Comparaison avec le stock" (besoin/stock/manque), `effTotals` déduits pour craft matériaux, matières premières, coûts.
  - Labels "(après stock)" quand la déduction est active.
  - Event listeners sur les inputs stock pour recalcul en temps réel.
  - Cache-buster ajouté sur le `<script>` tag.

**Corrections techniques** :
- `WeaponSimController::munitions()` charge `$ammoPrices` depuis `StockItem` catégorie `ammo` et passe `ammoPricesJson` à la vue.
- `simulateur-munitions.blade.php` : ajout `<script>window.AMMO_SELL_PRICES = {!! $ammoPricesJson !!};</script>`.

### Contexte précédent
Phases 6 (fiches membres), 7.1 (comptabilite), 7.3 (cotisations) et amelioration UX demandes **LIVREES** le 19 avril 2026.

### Livraisons du 19 avril 2026

**Phase 6 – Fiches membres detaillees** :
- Route `/membres/{id}/profil` + API `/membres/api/{id}/profile` (officier+).
- Vue `membre-profil.blade.php` + `membre-profil.js`.
- Contenu de la fiche : info (nom, role, date), items en possession (attributions ouvertes),
  historique ventes (50 dernieres + totaux CA/semaine/mois), mouvements de stock,
  cotisations, demandes de remboursement.
- Lien "Fiche" ajoute sur chaque ligne de la liste des membres (`/membres`).

**Phase 7.1 – Comptabilite MC** :
- Route `/comptabilite` + `ComptabiliteController` (tresorier+).
- Vue `comptabilite.blade.php` + `comptabilite.js`.
- Onglets : Vue d'ensemble (soldes argent sale/propre, flux ventes/cotisations/depenses
  avec selecteur de periode), Par semaine (tableau des 12 dernieres semaines avec balance),
  Transactions (liste chronologique filtrable par type : ventes/cotisations/depenses).
- Les soldes sont lus depuis `stock_items` slugs `argent_sale` et `argent_propre`.

**Phase 7.3 – Cotisations** :
- Migration `2026_04_19_171428_create_cotisations_table` : user_id, period_start/end,
  amount_due, amount_paid, paid_at, marked_by_user_id, notes.
- Modele `Cotisation` avec methodes `isPaid()`, `isPartial()`, `remaining()`.
- `CotisationController` : auto-generation des cotisations de la semaine pour tous les
  membres actifs. Montants par role depuis les settings (prospect 2000, membre 5000,
  officier+ 10000).
- Route `/cotisations` (membre+) + API list/pay/generate/my-status.
- Vue `cotisations.blade.php` + `cotisations.js`.
- Onglets : Semaine en cours (navigation semaine prec/suiv, actions Payer/Partiel pour
  officiers+), Historique (filtrable par membre pour officiers+).
- Alerte si cotisation non payee sur la semaine en cours.
- Setting `cotisation_day` ajoutee.
- Page access rule `cotisations` ajoutee (min_role: member).

**Amelioration UX Demandes** :
- Nouvel onglet "A valider" visible uniquement pour tresorier+ : affiche directement
  les demandes en statut `pending` avec les boutons Approuver/Refuser. Badge compteur
  sur l'onglet. Auto-switch vers cet onglet si des demandes sont en attente.
- Onglet "Historique complet" (ancien "Toutes les demandes") : filtre par statut ET
  par membre via select.
- Filtre par `member_id` ajoute a `McRequestController::apiList()`.
- Navigation : lien Cotisations et Compta ajoutees a la barre MC.

### Contexte précédent
Phase 4bis (ventes hors stock, visibilite par role, deduction attributions) **LIVRÉE** le 18 avril 2026.

**4bis.1 – Ventes hors stock (services, informations)** :
- Migration `2026_04_18_100000_add_ad_hoc_label_to_sales` : `stock_item_id` devient
  nullable, ajout `ad_hoc_label` (varchar 150).
- `Sale` model : `ad_hoc_label` ajouté aux `$fillable`.
- `SaleController::apiCreate()` : mode "vente libre" quand `stock_item_id` est absent
  et `ad_hoc_name` fourni. Crée une sale sans mouvement de stock ni StockItem.
- `SaleController::mapSale()` : affiche `ad_hoc_label` et catégorie "service" pour
  les ventes hors stock.
- Frontend : le toggle "Article hors catalogue" renommé "Vente hors stock (service, info...)"
  et ne crée plus de StockItem (simplifié : un seul champ description sans catégorie).

**4bis.2 – Déduction automatique des attributions** :
- `SaleController::apiCreate()` : si le vendeur a une attribution ouverte pour l'article,
  elle est automatiquement réconciliée (même sans `attribution_id` explicite). Si la qty
  dépasse le reste de l'attribution, le complément est déduit du stock central.
- `SaleController::apiBatch()` : même logique de déduction auto par article.

**4bis.3 – Visibilité par rôle (ventes)** :
- `apiCatalog()` : retourne le catalogue complet uniquement pour officier+.
  Prospect/membre reçoit un catalogue vide (ne voit que ses attributions).
- `apiList()` : ajoute `user_role` dans la réponse.
- Frontend (`ventes.js`) : prospect/membre ne voit que "Mes articles (attribués sur moi)".
  L'accordéon catalogue et le toggle hors stock sont masqués. Le select classique
  est peuplé uniquement avec les articles attribués.

**4bis.4 – Simulateurs : accès membre+** :
- `PageAccessRuleSeeder` et DB : `simulateur_armes` et `simulateur_munitions` passent
  de `prospect` à `member` comme rôle minimum.

**4bis.5 – Attributions avec stock insuffisant** :
- Déjà permis (le stock passe en négatif avec avertissement). Le flag `from_external`
  permet de ne pas décrémenter du tout si l'article est stocké ailleurs.

**4bis.6 – Plan de développement mis à jour** :
- Notes 7-11 ajoutées (ventes hors stock, stock insuffisant, déduction attributions,
  visibilité par rôle, accès simulateurs).
- Phase 9 ajoutée : documentation par rôle (qui a accès à quoi).
- Tableau de visibilité par page/rôle ajouté.

### Contexte précédent
Phase 4 (harmonisation simulateur / vente rapide) **LIVRÉE** le 18 avril 2026.

**4.1 – Flag `is_quick_sale` sur `stock_items`** :
- Migration `2026_04_18_150000_add_is_quick_sale_to_stock_items` : ajout booléen
  `is_quick_sale`, default `true` pour catégories `weapon_finished`, `ammo`, `drug`, `melee`.
- `StockItem` : champ ajouté aux `$fillable` + `$casts`, scope `quickSale()`.
- Filament `StockItemResource` : toggle "Vente rapide (express)" dans le formulaire +
  colonne icône "VR" dans la table.
- API `/stocks/api/item/{slug}`, `/stocks/api/list`, `/stocks/api/item/{slug}` (PUT),
  `/stocks/api/create-item` : `is_quick_sale` ajouté aux réponses et validations.
- Page `/stocks/{slug}` : checkbox "Vente rapide (express)" dans le formulaire d'édition.

**4.2 – Attribution sans prix** :
- `StockController::apiAttribute()` : suppression de `unit_cost` dans le `StockMovement::create`.
- Filament `StockMovementResource` : `unit_cost` masqué quand reason = attribution/adjustment
  (champ `live()` sur le select reason).

**4.3 – Vente rapide filtrée par `is_quick_sale`** :
- `SaleController::loadCatalog()` : ajout `is_quick_sale` dans le mapping.
- `ventes.js` : l'accordéon express ne montre que les items avec `is_quick_sale: true`.
- Vente classique continue d'afficher tout le catalogue vendable.

**4.4 – Vente rapide depuis attributions** :
- Nouvel endpoint `GET /ventes/api/my-attributions` : retourne les attributions ouvertes
  du membre connecté (tous articles, pas seulement quick_sale).
- Section "Mes articles (attribués sur moi)" en haut de l'onglet Vente Express.
- Les items attribués peuvent être ajoutés au panier express (clés `attr_XXX`).
- La soumission sépare items standard (batch) et items attribution (create individuel
  avec `attribution_id` pour reconciliation automatique).
- CSS `.ve-item-attr` : style bleu distinctif pour les cartes d'attribution.

### Contexte précédent
Phase 3B **LIVRÉE** le 17 avril 2026 : améliorations UX front-end MC/armes.

### Phase 3B livrée (17 avril 2026)

**3B.0 – Bouton Admin retiré** : suppression des liens `/armurerie` et `/admin` du hub MC.

**3B.1 – Stocks améliorés** :
- Sous-onglet "Mouvement" ajouté entre Vue d'ensemble et Attribuer : formulaire de
  mouvement direct (direction in/out, quantité, raison, coût unitaire, notes).
- Bouton "+ Nouvel article" dans la vue d'ensemble : formulaire inline de création
  d'article (nom, catégorie, quantité, prix, poids, notes).
- Boutons rapides par ligne : "Vendre" (lien vers `/ventes`), "Attribuer" (switch
  vers l'onglet), "Détail" (lien vers `/stocks/{slug}`).
- Backend : `POST /stocks/api/movement` et `POST /stocks/api/create-item`.

**3B.2 – Attributions en masse** :
- Checkboxes sur chaque attribution en cours.
- Barre d'actions en masse (Annuler, Déjà en stock, Perte, Retour) avec compteur.
- Actions "Annuler" (retour avec notes "Annulation") et "Déjà en stock" (perte avec
  notes "Déjà en stock") ajoutées aux boutons individuels et en masse.

**3B.3 – Vente Express repensée** :
- `/ventes` restructurée en 3 onglets : Vente Express (défaut), Vente classique, Historique.
- Accordéon par catégorie (drogues en premier, ouvert par défaut), chaque item = carte
  avec nom/prix/stock/contrôles quantité, click-to-increment.
- Barre de récap fixe en bas : items sélectionnés, total, argent rapporté, acheteur, notes.
- Backend : `POST /ventes/api/batch` (vente multi-items en une transaction).

**3B.4 – Classements configurables** :
- Page `/classements` accessible à tous les membres connectés.
- 3 sous-onglets : Classement, Historique Aigles, Configuration (officier+).
- Sélecteur de période : Semaine / Sem. précédente / Mois / Global.
- Tableau avec rang, nom, rôle, ventes, quantité, CA. Top 3 mis en évidence
  (or/argent/bronze). Membre connecté toujours visible (highlight bleu).
- "Aigle de la semaine" : bannière dorée avec le #1 hebdomadaire + historique 12 sem.
- Configuration officer+ : choix des catégories éligibles + critère de tri
  (CA / nb ventes / quantité). Settings `rankings.eligible_categories` (JSON)
  et `rankings.criteria` (string) en BD.
- Backend : `RankingController` avec `apiRankings`, `apiConfig`, `apiUpdateConfig`,
  `apiEagleHistory`. Calcul en temps réel depuis `sales` + `stock_items`.
- Lien ajouté au hub MC et à la navbar (gate=logged).

### Contexte précédent
Phase 3 livrée le 16 avril 2026 (soir) : module stocks générique complet.
Ajouts post-Phase 3 : `StockInventorySeeder` (97 items), vente hors catalogue,
édition fiches articles sur `/stocks/{slug}`.

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
3. **Phase 6 – fiches membres** : pages `/membres/{id}` avec historique complet
   (attributions, ventes, cotisations). Note : classements déplacés en Phase 3B.4 (fait).
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
