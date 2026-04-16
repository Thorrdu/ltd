# Progress - Station LTD / Toolbox Lost MC

## Statut actuel
Projet Laravel 12 + Filament 5 fonctionnel avec deux domaines operationnels : catalogue LTD et armurerie.
Toolbox MC en construction : Phases 0 (roles + settings + matrice d'acces + gestion membres),
1 (refonte UX), 2 (ventes rapides), H (harmonisation du schema) et 3 (stocks generiques + attributions
+ validations + import CSV) TERMINEES.
Session du 16 avril (soir) : Phase 3 livree avec page `/stocks` (officier+), page detail
`/stocks/{slug}`, formulaire d'attribution, flux de reconciliation (retour/perte/don/vente),
validation tresorier avec seuil configurable, import CSV avec preview. Le formulaire de vente
residuel de `/espace-membres` a ete retire (redirection vers `/ventes`). Prochaine etape :
Phase 4 (drogues : flux achat orga + dashboard profit) ou Phase 6 (classements + fiches membres).

## Ce qui fonctionne

### Catalogue LTD
- [x] Site public : 4 pages Blade (accueil, produits, menus, entreprises)
- [x] Design panneau bois preserve, dot leaders, zebra stripes, mode clean
- [x] Panneau Filament `/admin` : 4 resources, 3 relation managers, 2 widgets
- [x] 62 produits, 5 categories, 4 menus, 6 entreprises en base
- [x] Prix d'achat, prix habituel, prix promo, prix entreprise sur produits
- [x] Middleware AllowIframe pour embedding

### Armurerie
- [x] Panneau Filament `/armurerie` : 5 resources, page CraftWeapon, widget stats
- [x] Modeles complets : Weapon, WeaponStock, WeaponStockMovement, WeaponContract, WeaponContractItem, WeaponSale
- [x] Stocks par categorie (matieres, pieces, plans, armes finies)
- [x] Mouvements de stock avec tracabilite (utilisateur, raison, cout)
- [x] Contrats clients avec items commandes/livres
- [x] Ventes avec suivi par vendeur

### Simulateur d'armes et espace membres
- [x] Pages dediees : `/mc` (hub), `/simulateur-armes`, `/simulateur-munitions`, `/espace-membres`
- [x] Calcul de prix de revient et prix de vente (recettes, multiplicateurs DB)
- [x] Espace membres : login PIN, dashboard, ventes, contrats, mouvements, historique
- [x] API JSON (WeaponSimController) avec headers `X-Sim-User` et CSRF
- [x] Selects recherchables (Tom Select 2.3.1) avec theme dark et badges/quantites

### Gestion des utilisateurs (nouveau -- 16 avril soir)
- [x] Page `/membres` dediee : table avec filtres, stats par role, actions (role / PIN / actif / supprimer)
- [x] Controleur `MemberController` + 7 endpoints API protegees
- [x] Hierarchie stricte : un utilisateur ne peut assigner que des roles strictement inferieurs au sien (sauf superadmin)
- [x] Superadmin = treasurer : peut assigner tout role, y compris tresorier
- [x] Reset PIN avec affichage modal du nouveau PIN (random 4-6 chiffres)
- [x] Activation / desactivation (champ `is_active`)
- [x] Suppression reservee au superadmin, blocage auto-suppression et demotion du dernier superadmin

### Matrice d'acces editable (16 avril soir)
- [x] Table `page_access_rules` (13 regles seedees)
- [x] `User::canAccessPanel()` et `User::canAccessPage()` pilotes par la DB
- [x] Edition inline de la matrice reservee au superadmin (onglet "Matrice d'acces" sur `/membres`)
- [x] Cache de 10 minutes sur les regles (invalidation auto sur save/delete)

### Ventes rapides (Phase 2 -- 16 avril soir tardif, mise a jour Phase 3)
- [x] Page `/ventes` avec formulaire unifie (catalog groupe par categorie via optgroups Tom Select)
- [x] Table `sales` avec FK `stock_item_id` (plus de `item_type`/`item_id` libres depuis Phase H)
- [x] Controleur `SaleController` (index, apiList, apiCreate, apiCatalog) protege par `ventes_rapides`
- [x] Pour `stock_item.category=weapon_finished` : decrement auto du `stock_item` + `stock_movement` reason=`sale`
- [x] Support `attribution_id` en query string et payload : reconcilie l'attribution liee sans double-decrement
- [x] Historique filtrable (scope mine/all, periode today/week/month/all) avec stats
- [x] Pre-remplissage du prix depuis `stock_items.default_sell_price`
- [x] Bouton dans `/mc` et lien "Ventes" dans la nav (gate=logged)

### Stocks generiques et attributions (Phase 3 -- 16 avril 2026, soir) -- TERMINEE
- [x] Page `/stocks` (officier+) avec sous-onglets : Vue d'ensemble, Attribuer, Attributions en
      cours, Validations (tresorier+), Import (tresorier+).
- [x] Page detail `/stocks/{slug}` : stats, attributions ouvertes, mouvements recents.
- [x] Jauge de capacite (kg) basee sur `unit_weight_g` et setting `stocks.stock_max_capacity_kg`.
- [x] Totaux par categorie (stock + attributions en cours).
- [x] Filtres par categorie et recherche texte.
- [x] Formulaire d'attribution : cree un `StockMovement` reason=`attribution`, decrement atomique.
- [x] Reconciliation par le membre : Vendu (redirect `/ventes?stock_item_id=X&quantity=Y&attribution_id=Z`),
      Retour (adjustment +qty), Perte (adjustment note obligatoire), Don (adjustment note beneficiaire).
- [x] Validation tresorier : si quantite >= setting `stocks.attribution_approval_threshold`,
      l'attribution passe en `requires_approval` et attend approve/reject.
- [x] Import CSV : preview + commit, mise a jour `stock_items.quantity`, creation de
      `stock_movement` adjustment avec note "Import CSV du ...".
- [x] Onglet "Mes attributions" sur `/espace-membres` (ancien "Ventes" renomme "Mouvements").
- [x] Suppression du formulaire de vente du dashboard `/espace-membres` + redirection vers `/ventes`.
- [x] Migration additive 2026_04_16_181908 : `reconciled_at`, `reconciled_by_movement_id`,
      `requires_approval`, `approved_by_user_id`, `approved_at`, `rejected_at`, `rejection_reason`.
- [x] Seeders : 3 nouvelles `page_access_rules` + 2 settings dans le groupe `stocks`.

### Inventaire reel seed (16 avril 2026, soir -- post Phase 3)
- [x] `StockInventorySeeder` : MAJ des quantites des items existants (munitions, pieces,
      armes, drogues, matieres) + creation des items manquants observes dans le stockage
      Lost MC : crosse, corps SMG/fusils, suppresseurs, 7 plans hors catalogue craft,
      cocaine, briques de weed/cocaine, sachets, joints, 8 consommables agricoles (engrais,
      pesticide, graines, feuilles, sachets plastique), 5 outils, 7 items electroniques,
      argent sale, sacs. 97 stock_items au total.
- [x] `SaleController::apiCreate` accepte `ad_hoc_name` + `ad_hoc_category` quand
      `stock_item_id` est vide : cree un `stock_item` (slug `adhoc_*`, quantity=0,
      is_active=true) puis poursuit la vente normalement (stock passe en negatif,
      warning renvoye au client, regularisation possible via `/stocks`).
- [x] `/ventes` : toggle "Article hors catalogue" qui masque le select et expose
      deux champs (nom libre + select catégorie des 12).
- [x] Edition des fiches articles sur `/stocks/{slug}` : bouton "Modifier" +
      formulaire inline (name, category, sell/purchase price, weight, sellable,
      active, notes). Endpoint `PUT /stocks/api/item/{slug}` (officier+) avec
      creation d'un mouvement adjustment qty=0 tracant les champs changes.

### Infrastructure
- [x] 24 migrations appliquees (ajout de `add_attribution_fields_to_stock_movements`)
- [x] 9 seeders : CategoryProduct, Enterprise, Menu, User, Weapon, StockItem,
      StockInventory, Setting, PageAccessRule
- [x] 15 modeles Eloquent (ajout de `Sale`, `StockItem`, `StockMovement`)
- [x] Systeme de roles avec hierarchie numerique (prospect 1, member 2, officer 3, vice_president 4, president 5, treasurer 99)
- [x] Auth PIN pour simulateur via header `X-Sim-User`
- [x] Documentation (architecture, reglement BXL Life, Memory Bank, plan-developpement)

## Ce qui reste a faire

### Priorite haute (prochaine session)
- [ ] **Phase 4** : module drogues avec flux achat aux organisations (formulaire dedie creant
      un `StockMovement` reason=`purchase` avec `unit_cost` et fournisseur), dashboard drogue
      avec calcul profit/perte cumule, extension seeder pour sous-categories `drug_raw` et
      `farm_consumable`.
- [ ] **Phase 6** : classements (global/mois/semaine) + fiches membres `/membres/{id}`
      avec historique complet (attributions, ventes, cotisations, argent rapporte).
- [ ] Aligner le vhost Laragon sur `public/` (residuel).

### Priorite moyenne
- [ ] Phase 5 : rendre le multiplicateur x1.5 configurable via `settings` et recalculer
      `default_sell_price` dynamiquement depuis le panel Filament.
- [ ] Phase 7 : comptabilite MC (argent sale/propre, transactions, cotisations) avec
      validation tresorier et historique filtrable.

### Priorite basse / Future
- [ ] Phase 8 : polissage (responsive fin, notifications in-app, dashboards par role).

## Historique
- **16 avril 2026 (soir, apres Phase H)** : Phase 3 livree -- module stocks generique complet :
  page `/stocks` (officier+) avec sous-onglets (Vue d'ensemble, Attribuer, Attributions en cours,
  Validations tresorier+, Import tresorier+), page detail `/stocks/{slug}`, flux de
  reconciliation complet (Vendu/Retour/Perte/Don), validation tresorier avec seuil configurable,
  import CSV avec preview. Suppression du formulaire de vente de `/espace-membres` (redirection
  vers `/ventes`). Migration additive sur `stock_movements` (colonnes reconciled/approval).
  `SaleController` etendu pour reconcilier les attributions sans double-decrement.
- **16 avril 2026 (soir tardif)** : Phase H livree -- harmonisation du schema : 3 tables uniques
  (`stock_items`, `stock_movements`, `sales`), suppression des anciennes `weapon_*`.
  54 items seedes sur 7 categories effectives.
- **16 avril 2026 (soir tardif)** : Phase 2 livree -- module `/ventes` (table `sales` generique +
  SaleController + vue + JS + integration hub/nav). Phase H d'harmonisation ajoutee au plan.
  Annexe A avec inventaire in-game (12 categories).
- **16 avril 2026 (soir)** : Gestion utilisateurs `/membres` + matrice d'acces BDD + Tom Select + bugfix selects vides + role `vice_president` + treasurer=superadmin.
- **16 avril 2026 (apres-midi)** : Phase 1 refonte UX (pages dediees, login dropdown, JS scinde). Phase 0 terminee (settings, roles helpers, SettingResource, 19 parametres).
- **14-16 avril 2026** : Domaine armurerie complet (6 tables, 6 modeles, panneau Filament, simulateur, API).
- **28 mars 2026** : Ajout promo_price sur products et menus.
- **11 mars 2026** : Ajout enterprise_price sur products.
- **8 mars 2026** : Ajout purchase_price/usual_price/notes, fix namespace Filament v5.
- **4 mars 2026** : Conversion complete vers Laravel 12 + Filament 5. Documentation.
- **2 mars 2026** : Derniere version du site statique original.

## Chiffres cles
- 62 produits catalogue en base
- 15 modeles Eloquent (ajout de `Sale`, `StockItem`, `StockMovement`)
- 24 migrations appliquees
- 8 seeders
- 2 panneaux Filament (admin + armurerie)
- 8 pages front dediees (`/mc`, `/simulateur-armes`, `/simulateur-munitions`, `/espace-membres`,
  `/membres`, `/ventes`, `/stocks`, `/stocks/{slug}`)
- 6 roles dans la hierarchie (prospect -> treasurer)
- 16 regles d'acces seedees dans `page_access_rules` (ajout `stocks_generique`,
  `stocks_validations`, `stocks_import`)
- 21 parametres dans `settings` (ajout `stocks.attribution_approval_threshold`,
  `stocks.stock_max_capacity_kg`)
- 12 categories `stock_items` supportees (`weapon_finished`, `weapon_plan`, `weapon_piece`,
  `raw_material`, `ammo`, `melee`, `drug`, `drug_raw`, `farm_consumable`, `tool`, `electronic`, `misc`)
- 54 items seedes, 8 raisons de mouvement (`purchase`, `gather`, `craft_consume`, `craft_produce`,
  `sale`, `delivery`, `attribution`, `adjustment`)
