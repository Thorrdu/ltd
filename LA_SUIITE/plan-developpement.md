# Plan de developpement - Toolbox Lost MC

> Document de reference pour le developpement des fonctionnalites futures.
> Base sur `LA_SUIITE/next.md` et l'etat actuel du projet au 17 avril 2026.
> Objectif global : faciliter la gestion quotidienne et comptable du MC.

---

## Phase 0 - Corrections techniques et prerequis [TERMINEE]
**Terminee le 16 avril 2026**

### 0.1 Corrections modeles existants
- [x] Ajouter `price_min`, `price_max` dans `$fillable` du modele `Weapon`
- [x] Verifier la coherence de tous les `$fillable` / `$casts` des modeles armurerie
- [x] Tester le seeder complet (`php artisan migrate:fresh --seed`) -- 22 migrations, 6 seeders, 0 erreur

### 0.2 Systeme de roles et permissions
- [x] Hierarchie finale : `prospect` (1), `member` (2), `officer` (3), `vice_president` (4),
      `president` (5), `treasurer` (99 = superadmin)
- [x] Constante `ROLES` dans User avec niveaux numeriques, `SUPERADMIN_ROLE = treasurer`
- [x] Methodes : `isProspect()`, `isMember()`, `isOfficer()`, `isVicePresident()`,
      `isPresident()`, `isTreasurer()`, `isSuperadmin()`, `isAtLeast($role)`,
      `canAssignRole($role)`, `assignableRoles()`
- [x] Acces aux panneaux et pages piloté par la table `page_access_rules` (edition inline
      reservee au superadmin)
- [x] WeaponSimController et MemberController utilisent `canAccessPage($key)`
- [x] UserSeeder : 1 president, 1 treasurer, 2 officers, 4 members, 2 prospects

### 0.3 Page de configuration / parametres (DB-driven)
- [x] Table `settings` (group, key, label, type, value, description, sort_order)
- [x] Modele `Setting` avec cache, helpers statiques `get()`, `set()`, `getGroup()`, `clearCache()`
- [x] SettingResource Filament avec edition inline, groupement, filtres -- acces treasurer+
- [x] SettingSeeder : 19 parametres (matieres premieres, pieces, recettes munitions, multiplicateurs, cotisations)

### 0.4 Matrice d'acces (ajoutee le 16 avril 2026)
- [x] Table `page_access_rules` (13 regles seedees : `panel_admin`, `panel_armurerie`, `mc_hub`,
      `simulateur_armes`, `simulateur_munitions`, `espace_membres`, `membres_gestion`,
      `matrice_acces`, `ventes_rapides`, `stocks_generique`, `comptabilite`, `classements`,
      `fiches_membres`)
- [x] `User::canAccessPanel()` et `User::canAccessPage()` pilotes par la DB
- [x] Edition inline de la matrice reservee au superadmin (onglet "Matrice d'acces" sur `/membres`)
- [x] Cache 10 minutes avec invalidation auto

### 0.5 Gestion des utilisateurs (ajoutee le 16 avril 2026)
- [x] Page `/membres` : table filtrable, stats par role, actions (role / PIN / actif / supprimer)
- [x] Controleur `MemberController` + 7 endpoints API protegees
- [x] Hierarchie stricte : un utilisateur ne peut assigner que des roles strictement inferieurs
- [x] Superadmin = treasurer : peut assigner tout role, y compris tresorier
- [x] Reset PIN avec affichage modal du nouveau PIN (random 4-6 chiffres)
- [x] Activation / desactivation (champ `is_active`)

---

## Phase 1 - Reorganisation de l'interface [TERMINEE]
**Terminee le 16 avril 2026 -- refonte UX complete**

### Principe : une page = une fonction, zero onglet imbrique

**Architecture des pages :**
- `/mc` -- Hub d'accueil avec gros boutons clairs (publics + membres)
- `/simulateur-armes` -- Simulateur armes uniquement (selection, pieces, couts, craft)
- `/simulateur-munitions` -- Simulateur munitions uniquement (tableau, objectif)
- `/espace-membres` -- Dashboard membre (stocks, ventes, contrats, historique, gestion)

**Barre superieure minimale :** LOST MC (lien home) + bouton connexion / nom+role+quitter

### 1.1 Separation des simulateurs
- [x] JS monolithique (1446 lignes) scinde en 3 fichiers :
  - `mc-auth.js` : module auth partage (`window.McAuth`), callbacks login/logout
  - `simulateur-armes.js` : simulateur armes + logique dashboard membre
  - `simulateur-munitions.js` : simulateur munitions autonome
- [x] `/simulateur-armes` ne contient QUE le simulateur (plus de tabs, plus d'espace membre)
- [x] `/simulateur-munitions` page dediee avec JS autonome

### 1.2 Deplacer le login
- [x] Login retire des onglets, deplace dans dropdown en haut a droite
- [x] Bouton "Se connecter" dans la barre superieure sur toutes les pages MC
- [x] Panel dropdown avec formulaire PIN
- [x] Nom + role (avec couleur par hierarchie) + bouton quitter une fois connecte
- [x] Session via sessionStorage persistante entre les pages

### 1.3 Hub mobile-friendly + pages dediees
- [x] `/mc` hub : 2 sections (Simulateurs / Espace membres)
- [x] Section Simulateurs toujours visible (Armes, Munitions)
- [x] Section Espace membres visible si connecte (bouton vers `/espace-membres` + Admin)
- [x] Message "Connectez-vous" si non connecte
- [x] Grille responsive (2 colonnes desktop, 1 colonne mobile)
- [x] Chaque page a un lien "Retour a l'accueil" sous le header
- [x] `/espace-membres` page dediee avec sous-onglets (Stocks, Ventes, Contrats, Historique, Gestion)

### Fichiers
- `resources/views/layouts/mc.blade.php` -- layout partage (barre + auth + toast)
- `public/css/mc-layout.css` -- styles barre, hub, pages, responsive
- `public/js/mc-auth.js` -- module auth partage
- `public/js/simulateur-armes.js` -- simulateur armes + dashboard
- `public/js/simulateur-munitions.js` -- simulateur munitions
- `resources/views/mc-hub.blade.php` -- page hub
- `resources/views/simulateur-armes.blade.php` -- simulateur pur
- `resources/views/simulateur-munitions.blade.php` -- simulateur pur
- `resources/views/espace-membres.blade.php` -- dashboard membres
- `routes/web.php` -- `/mc`, `/simulateur-armes`, `/simulateur-munitions`, `/espace-membres`

---

## Phase 2 - Module de ventes rapides [TERMINEE]
**Terminee le 16 avril 2026 (soir tardif) -- besoin quotidien des membres**

### 2.1 Page de saisie rapide des ventes
- [x] Page `/ventes` accessible aux membres connectes (regle `ventes_rapides`, min_role `member`)
- [x] Formulaire simplifie : selection d'un `stock_item` sellable via Tom Select groupe par categorie
- [x] Prix par defaut pre-rempli depuis `stock_items.default_sell_price`
- [x] Calcul automatique du prix unitaire (total / quantite)
- [x] Champ acheteur libre + notes optionnelles
- [x] Bouton de validation rapide, reset automatique apres succes
- [x] Historique "Mes ventes du jour" avec stats (nombre, articles, chiffre)
- [x] Sous-onglet "Historique" avec filtres scope (mes/toutes) et periode (jour/semaine/mois/tout)

### 2.2 Base de donnees ventes generiques
- [x] Table `sales` unifiee (post-Phase H) :
  - `stock_item_id` FK obligatoire (plus de `item_type`/`item_id`/`item_name` libres)
  - `quantity`, `unit_price`, `total_price`
  - `buyer_name`, `sold_by_user_id`, `validated_by_user_id` nullable, `validated_at` nullable
  - `weapon_contract_id` nullable, `notes`, timestamps
- [x] Modele `Sale` avec scopes `today()`, `inPeriod($period)`, relations `stockItem`, `soldBy`, `validatedBy`, `contract`
- [x] Pour `stock_item.category = weapon_finished` : decrement auto du `stock_item` + `stock_movement` reason=`sale`

### Fichiers
- `database/migrations/2026_04_16_171611_create_sales_table.php`
- `app/Models/Sale.php`
- `app/Http/Controllers/SaleController.php`
- `resources/views/ventes.blade.php`
- `public/js/ventes.js`
- `public/css/mc-layout.css` (blocs `VENTES PAGE`)
- Routes : `GET /ventes`, `GET /ventes/api/list`, `GET /ventes/api/catalog`, `POST /ventes/api/create`
- Hub MC + nav : bouton "Ventes rapides" visible une fois connecte

---

## Phase H - Harmonisation et deduplication [TERMINEE]
**Livree le 16 avril 2026 (soir tardif) -- rationalisation complete du schema**

### Resultat : 3 tables uniques pour 3 concepts metier

Avant la Phase H, plusieurs chemins coexistaient (`weapon_stocks` + `weapon_stock_movements` +
`weapon_sales` vs `stock_items` + `sales`). Chaque concept est desormais represente par une
unique table, indexee par `category` + `slug` pour le catalogue.

### H.1 Tables unifiees [FAIT]
- [x] **`stock_items`** : seul catalogue / stock (colonnes `category`, `slug`, `name`,
      `weapon_id` nullable, `quantity`, `unit_weight_g`, `default_sell_price`,
      `default_purchase_price`, `is_sellable`, `is_active`, `sort_order`, `notes`).
- [x] **`stock_movements`** : journal unique (`stock_item_id`, `quantity_change`, `reason`
      parmi `purchase|gather|craft_consume|craft_produce|sale|delivery|attribution|adjustment`,
      `unit_cost`, `weapon_contract_id`, `user_id`, `attributed_to_user_id`, `notes`,
      `created_at` uniquement).
- [x] **`sales`** : seule table de ventes (cf. 2.2 ci-dessus).

### H.2 Suppression des tables historiques [FAIT]
- [x] Tables supprimees : `weapon_stocks`, `weapon_stock_movements`, `weapon_sales`.
- [x] Modeles supprimes : `WeaponStock`, `WeaponStockMovement`, `WeaponSale`.

### H.3 Unification des UIs [FAIT]
- [x] `WeaponSimController::createSale()` ecrit desormais dans `sales` (via `stock_item.slug = 'weapon_<slug>'`).
- [x] `WeaponSimController::apiData()` lit categories `weapon_*` et `raw_material` depuis `stock_items`.
- [x] `SaleController::apiCreate()` utilise `stock_item_id` avec decrement auto pour `weapon_finished`.
- [x] Resources Filament refondues : `StockItemResource`, `StockMovementResource`, `SaleResource`.
- [x] Page Filament `CraftWeapon` reecrite pour `StockItem` + `StockMovement`.
- [x] `ArmurerieStatsWidget` aligne sur `stock_items` + `sales`.
- [x] JS front aligne : `simulateur-armes.js` (slugs `weapon_finished`, `weapon_piece`, `weapon_plan`),
      `ventes.js` reecrit (payload `stock_item_id`, optgroups Tom Select par categorie).

### H.4 Categories disponibles
12 categories supportees par `stock_items.category` :
`weapon_finished`, `weapon_plan`, `weapon_piece`, `raw_material`, `ammo`, `melee`, `drug`,
`drug_raw`, `farm_consumable`, `tool`, `electronic`, `misc`.

### H.5 Regle de fer [ETABLIE]
- [x] Une seule table pour chaque concept. Toute nouvelle verticale (drogues, armes blanches,
      consommables agricoles, outils, electronique) DOIT passer par `stock_items` via la
      colonne `category`, sans creer de table dediee.
- [x] Toute nouvelle ecriture de mouvement passe par `stock_movements`. Toute vente passe par `sales`.

### Reste a faire (residuel, non bloquant)
- [x] Supprimer le formulaire de vente du dashboard `/espace-membres` et rediriger vers `/ventes`
      (fait en Phase 3 : onglet "Ventes" renomme en "Mouvements", quick-sell de `simulateur-armes.js`
      et carte de vente de `espace-membres.blade.php` redirigent vers `/ventes?stock_item_id=X&quantity=Y`).

---

## Phase 3 - Module stocks generique [TERMINEE]
**Livree le 16 avril 2026 (soir) -- UI complete, attributions, validations et import CSV**

### Etat actuel (post-Phase H)
- [x] Tables `stock_items` (54 items seedes) et `stock_movements` en place.
- [x] Modeles `StockItem` (scopes `active`, `sellable`, `ofCategory`, methodes `addQuantity`,
      `removeQuantity`) et `StockMovement`.
- [x] Colonne `attributed_to_user_id` sur `stock_movements` pour les attributions.
- [x] `StockItemResource` Filament avec action inline "Ajuster" (cree le mouvement auto).
- [x] `StockMovementResource` Filament en lecture seule.
- [x] Migration additive 2026_04_16_181908 sur `stock_movements` : `reconciled_at`,
      `reconciled_by_movement_id`, `requires_approval`, `approved_by_user_id`, `approved_at`,
      `rejected_at`, `rejection_reason`.

### 3.1 Page publique `/stocks` (officier+) [FAIT]
- [x] Route `/stocks` + `StockController`, protegee par `page_access_rules` (cle
      `stocks_generique`, min_role `officer`).
- [x] Vue Blade `stocks.blade.php` avec sous-onglets (Vue d'ensemble, Attribuer,
      Attributions en cours, Validations tresorier+, Import tresorier+).
- [x] Tableau groupe par `category` avec colonnes nom, stock, en exterieur, prix, poids.
- [x] Filtres par categorie et recherche texte (`public/js/stocks.js`).
- [x] Route `/stocks/{slug}` + vue `stocks-detail.blade.php` : historique des mouvements
      et attributions en cours par item.
- [x] Jauge de capacite (somme `quantity * unit_weight_g` / `stocks.stock_max_capacity_kg`).

### 3.2 Attribution d'items a un membre/prospect (officier+) [FAIT]
- [x] Formulaire "Attribuer" sur `/stocks` (sous-onglet "Attribuer") ET sur `/espace-membres`
      (onglet "Mes attributions" pour visualisation, creation sur `/stocks`).
- [x] Endpoint API `POST /stocks/api/attribute` : cree un `StockMovement`
      `reason=attribution`, `quantity_change = -quantity`, decrement atomique du stock.
- [x] Validation : refus si stock insuffisant, user cible existant et actif.

### 3.3 Reconciliation par le beneficiaire [FAIT]
- [x] Onglet "Mes attributions" sur `/espace-membres` : liste les attributions ouvertes
      (sans `reconciled_at`) du membre connecte.
- [x] Endpoint `GET /stocks/api/attributions?scope=mine|all&status=open|all` (officier voit tout).
- [x] Endpoint `POST /stocks/api/reconcile/{id}` avec `action` :
  - **return** : `reason=adjustment`, `quantity_change = +quantity`, stock restaure.
  - **loss** : `reason=adjustment`, `quantity_change = 0` (trace seulement), note obligatoire.
  - **gift** : idem loss, note = beneficiaire du don.
  - **sell** : bouton "Vendu" redirige cote front vers `/ventes?stock_item_id=X&quantity=Y&attribution_id=Z`.
- [x] `SaleController::apiCreate()` etendu : si `attribution_id` fourni et valide, reconcilie
      l'attribution (link `reconciled_by_movement_id`) sans double-decrement du stock.

### 3.4 Validation tresorier (optionnel) [FAIT]
- [x] Migration additive : colonnes `requires_approval`, `approved_by_user_id`, `approved_at`,
      `rejected_at`, `rejection_reason` sur `stock_movements`.
- [x] Setting `stocks.attribution_approval_threshold` (int, defaut 0 = desactive).
- [x] Si quantite >= seuil, l'attribution passe en `requires_approval = true` et ne
      decremente pas immediatement (en attente).
- [x] Sous-onglet "Validations" sur `/stocks` (tresorier+) liste les attributions en attente.
- [x] Endpoints `POST /stocks/api/approve/{id}` et `POST /stocks/api/reject/{id}` avec
      verification tresorier+.

### 3.5 Import stock via CSV/Excel [FAIT]
- [x] Sous-onglet "Import" sur `/stocks` (tresorier+) avec zone textarea CSV (copier/coller
      des donnees screenshotees coffre, parse GPT en amont).
- [x] Endpoint `POST /stocks/api/import/preview` : parse CSV, retourne preview (slug trouve,
      ancienne qty, nouvelle qty, diff) + erreurs (slug inconnu, quantite invalide).
- [x] Endpoint `POST /stocks/api/import/commit` : met a jour `stock_items.quantity`, cree
      un `StockMovement` `reason=adjustment` par ligne avec note "Import CSV du ...".
- [x] L'import ecrase le stock physique MAIS ne touche pas aux attributions ouvertes.

### Fichiers
- `database/migrations/2026_04_16_181908_add_attribution_fields_to_stock_movements.php`
- `app/Http/Controllers/StockController.php`
- `app/Http/Controllers/SaleController.php` (extension `attribution_id`)
- `app/Models/StockMovement.php` (scopes `openAttribution`, `pendingApproval`)
- `resources/views/stocks.blade.php`, `resources/views/stocks-detail.blade.php`
- `resources/views/espace-membres.blade.php` (onglet "Mes attributions", suppression formulaire vente)
- `public/js/stocks.js`, `public/js/stocks-detail.js`, `public/js/simulateur-armes.js` (redirect vers `/ventes`)
- `public/css/mc-layout.css` (styles `.stocks-*`, `.att-*`, `.imp-*`)
- `resources/views/layouts/mc.blade.php` et `resources/views/mc-hub.blade.php` (lien "Stocks")
- `database/seeders/PageAccessRuleSeeder.php` (cles `stocks_generique`, `stocks_validations`, `stocks_import`)
- `database/seeders/SettingSeeder.php` (settings `stocks.attribution_approval_threshold`, `stocks.stock_max_capacity_kg`)
- Routes : `GET /stocks`, `GET /stocks/{slug}`, `GET/POST /stocks/api/*`

---

## Phase 3B - UX Pratique MC [A FAIRE]
**Priorite : HAUTE -- demandee le 17 avril 2026 -- simplifier l'utilisation quotidienne**

> Les motards du MC ont besoin d'une interface simple et rapide.
> Cette phase regroupe toutes les ameliorations UX identifiees apres la livraison
> des Phases 1-3 : suppression de liens inutiles, meilleure gestion des stocks,
> attributions plus pratiques, vente express repensee, et classements configurables.

### 3B.0 Nettoyage de l'interface [A FAIRE]
- [ ] **Retirer le bouton "Admin" (lien vers `/armurerie`)** de la barre membre sur
      `/espace-membres`. Ce lien n'a rien a faire dans l'interface motard ;
      les admin accedent au panel Filament directement via l'URL.
- [ ] Verifier qu'aucun autre lien vers `/admin` ou `/armurerie` ne subsiste
      dans les vues MC (hub, simulateur, ventes, stocks).

### 3B.1 Stocks : gestion complete sans quitter la page [A FAIRE]
**Probleme actuel :** le menu stock a des lacunes -- possibilite reduite de declarer
des mouvements, pas de creation d'items, obligation de naviguer vers un autre menu
pour dire qu'on a prete/vendu.

- [ ] **Mouvement de stock rapide sur `/stocks`** : ajouter un sous-onglet "Mouvement"
      (ou integrer dans "Vue d'ensemble") avec formulaire simplifie :
  - Select stock item (Tom Select, groupé par categorie)
  - Direction : Entree / Sortie (toggle)
  - Quantite
  - Raison (select : achat, recolte, craft, perte, pret, don, ajustement)
  - Cout unitaire (si achat)
  - Notes (optionnel)
  - Bouton "Enregistrer" -> cree un `StockMovement`, met a jour la quantite
- [ ] **Creation d'item en ligne sur `/stocks`** : bouton "+ Nouvel article" dans
      "Vue d'ensemble" (officier+) ouvrant un mini-formulaire :
  - Nom, Categorie (select des 12), Slug (auto-genere), Prix vente, Prix achat,
    Poids (g), Vendable (toggle), Quantite initiale, Notes
  - Cree le `StockItem` + un `StockMovement` reason=`adjustment` si qty > 0
- [ ] **Vente directe depuis `/stocks`** : sur chaque item de stock vendable,
      un bouton "Vendre" qui redirige vers `/ventes?stock_item_id=X` (pre-remplit
      l'article et le prix). Deja partiellement fait pour les armes finies sur
      `/espace-membres`, a generaliser a tous les items sur `/stocks`.
- [ ] **Pret/Attribution rapide** : bouton "Attribuer" directement sur la ligne
      d'un item dans la vue d'ensemble (en plus du sous-onglet dedie).

### 3B.2 Attributions en cours : gestion pratique et en masse [A FAIRE]
**Probleme actuel :** pas de possibilite d'annuler, de dire "c'est deja traite en stock",
pas de retour sur plusieurs objets a la fois, pas de champ quantite directement visible.

- [ ] **Champ quantite directe** : afficher la quantite attribuee de maniere visible
      et editable sur chaque ligne d'attribution en cours. Permettre de modifier
      (retour partiel) sans passer par un formulaire separe.
- [ ] **Champ motif unique** : un seul champ texte "Motif" unifie pour toutes les
      actions de reconciliation (retour, perte, don, annulation).
- [ ] **Action "Annuler"** : nouveau bouton sur chaque attribution en cours qui
      reconcilie l'attribution comme un retour complet (stock restaure) avec
      motif = "Annulation". Distinction visuelle avec le bouton "Retour".
- [ ] **Action "Deja traite"** : bouton "Deja en stock" qui reconcilie sans
      mouvement de stock (l'item est physiquement deja de retour dans le coffre,
      c'est juste l'ecriture qui manquait). Cree un mouvement qty=0 avec
      raison "adjustment" et note explicative.
- [ ] **Actions en masse** : checkbox sur chaque ligne d'attribution + bouton
      "Action groupee" avec :
  - Retour total (restaure le stock pour toutes les lignes cochees)
  - Annulation (idem retour avec motif "Annulation groupee")
  - Perte (reconcilie toutes les lignes cochees comme pertes)
  Le motif est saisi une seule fois pour toutes les lignes.
- [ ] **Filtres** : par membre, par categorie d'item, par date d'attribution.
- [ ] **Vue compacte** : afficher directement sur la ligne : article, quantite,
      attribue a, date, et les boutons d'action (sans deplier un sous-menu).

### 3B.3 Vente Express repensee [A FAIRE]
**Probleme actuel :** le menu de vente actuel (`/ventes`) est fonctionnel mais trop
technique pour une utilisation rapide par les motards. Les articles les plus vendus
sont les drogues, les armes et les munitions -- il faut un workflow de saisie express.

#### Architecture de la vente express
- [ ] **Refonte de `/ventes`** ou creation d'une page `/vente-express` dediee :
  la page affiche toutes les categories d'items sous forme de **panneaux
  depliables/repliables** (accordeons).

- [ ] **Panneaux par categorie** (dans l'ordre de priorite) :
  1. **Drogues** (`drug`) -- DEPLIE par defaut (c'est le plus vendu)
  2. **Armes a feu** (`weapon_finished`)
  3. **Munitions** (`ammo`)
  4. **Armes blanches** (`melee`)
  5. **Electronique** (`electronic`)
  6. **Outils** (`tool`)
  7. **Divers** (`misc`, `farm_consumable`, etc.)

- [ ] **Contenu de chaque panneau** :
  - Les items de la categorie affiches sous forme de **boutons-toggle** ou
    **grille de cartes compactes** (comme les weapon-cards du simulateur).
  - Chaque item affiche : nom, stock actuel, prix de vente par defaut.
  - Au clic sur un item, un **champ quantite** apparait (ou s'incremente).
  - Les items avec stock = 0 sont grises mais restent cliquables (vente
    possible meme en negatif, avec warning).

- [ ] **Zone de recapitulatif en bas** (toujours visible, fixe) :
  - Liste des articles selectionnes avec quantites
  - Total calcule automatiquement (somme des qty * prix unitaire)
  - Champ **"Argent rapporte"** (le montant reellement encaisse, peut differer
    du total theorique si le client negocie ou si c'est un pack)
  - Champ **"Acheteur"** (nom libre)
  - Champ **"Notes"** (optionnel)
  - Bouton **"Valider la vente"**

- [ ] **Logique backend** :
  - Un appel API unique qui cree N `sales` (une par article) + N `stock_movements`
    en une seule transaction.
  - Endpoint : `POST /ventes/api/batch` (nouveau) ou extension de `POST /ventes/api/create`
    pour accepter un tableau d'items.
  - Chaque `sale` a le meme `buyer_name`, meme `notes`, meme `sold_by_user_id`.
  - Le champ "Argent rapporte" est stocke dans un champ `actual_amount` ou en notes
    si different du total theorique.
  - Response : recapitulatif (articles vendus, stock mis a jour, warnings si negatif).

- [ ] **Reset apres validation** : retour a l'etat initial (panneaux depliables,
      toast de confirmation, compteurs remis a zero), pret pour la vente suivante.

- [ ] **Historique rapide** : sous la zone de saisie, un mini-historique "Mes ventes
      recentes" (5 dernieres) avec montant et heure, lien vers l'historique complet.

### 3B.4 Classements configurables [A FAIRE]
**Probleme actuel :** aucun classement n'existe. Le MC veut motiver les membres
avec un "Aigle de la semaine" et des stats de performance.

> **Note :** cette section remplace et enrichit l'ancienne Phase 6.1 (classements).
> La Phase 6.2 (fiches membres) reste inchangee.

- [ ] **Page `/classements`** accessible a tous les membres connectes.

- [ ] **Configuration par les admins du front** (officier+) :
  - Sous-onglet "Configuration" (ou dans la matrice d'acces / settings).
  - Selection des **categories de stock_items** qui comptent pour le classement
    (ex : cocher `drug`, `weapon_finished`, `ammo` ; decocher `tool`, `electronic`).
  - Enregistre dans `settings` (cle `rankings.eligible_categories`, valeur = JSON
    array des categories selectionnees).
  - Possibilite de choisir le critere de classement :
    - Chiffre d'affaires total (somme `sales.total_price`)
    - Nombre de ventes (count `sales`)
    - Quantite totale vendue (somme `sales.quantity`)
  - Enregistre dans `settings` (cle `rankings.criteria`, defaut = `revenue`).

- [ ] **Vues du classement** :
  - **Semaine en cours** : du lundi 00:00 au dimanche 23:59
  - **Semaine precedente** : affichee a cote ou via un selecteur
  - **Mois en cours**
  - **Global** (depuis le debut)
  - Selecteur de periode en haut de page (onglets ou boutons).

- [ ] **"Aigle de la semaine"** :
  - Le membre #1 du classement hebdomadaire recoit le badge "Aigle de la semaine".
  - Badge affiche sur le hub `/mc`, sur la page classements, et sur la barre membre.
  - Historique des aigles precedents (tableau semaine / nom / score).
  - Le badge est calcule automatiquement au changement de semaine (ou a chaque
    consultation du classement).

- [ ] **Tableau de classement** :
  - Colonnes : Rang, Nom, Nombre de ventes, Quantite, Chiffre d'affaires, Badge
  - Mise en evidence du top 3 (or/argent/bronze ou style Lost MC).
  - Le membre connecte est toujours visible (meme s'il est loin dans le classement).
  - Pagination ou scroll infini si beaucoup de membres.

- [ ] **Donnees** : les classements sont calcules en temps reel a partir de la table
      `sales` filtree par :
  - `sales.stock_item_id` -> `stock_items.category` IN (categories eligibles)
  - Periode (semaine, mois, global)
  - Groupement par `sales.sold_by_user_id`
  - Pas de table de cache (le volume de donnees est faible).

### Fichiers prevus pour Phase 3B
- `resources/views/espace-membres.blade.php` -- suppression bouton Admin
- `resources/views/stocks.blade.php` -- sous-onglet Mouvement, bouton Nouvel article
- `public/js/stocks.js` -- formulaire mouvement, creation item, vente directe
- `resources/views/ventes.blade.php` (ou `vente-express.blade.php`) -- refonte accordeon
- `public/js/ventes.js` (ou `vente-express.js`) -- logique vente express multi-articles
- `public/css/mc-layout.css` -- styles accordeon, panneau express, classement
- `app/Http/Controllers/StockController.php` -- endpoint mouvement + creation item
- `app/Http/Controllers/SaleController.php` -- endpoint batch vente
- `resources/views/classements.blade.php` -- page classements
- `public/js/classements.js` -- logique classements + config admin
- `app/Http/Controllers/RankingController.php` -- endpoints classements + config
- `database/seeders/SettingSeeder.php` -- settings `rankings.eligible_categories`, `rankings.criteria`
- `database/seeders/PageAccessRuleSeeder.php` -- cle `classements` (deja seedee, min_role a verifier)
- Routes : extensions sur `/stocks/api/*`, `/ventes/api/batch`, `/classements`, `/classements/api/*`

### Ordre de realisation interne (Phase 3B)
```
3B.0 Nettoyage (bouton Admin)        ~15 min
  |
  v
3B.1 Stocks : mouvements + creation  ~2-3h
  |
  v
3B.2 Attributions : actions en masse  ~2-3h
  |
  v
3B.3 Vente Express repensee           ~4-5h (plus gros morceau)
  |
  v
3B.4 Classements configurables        ~3-4h
```

---

## Phase 4 - Module drogues [A FAIRE]
**Priorite : moyenne -- flux economique important**

### Etat actuel (post-Phase H)
- [x] 14 items `category=drug` deja seedes dans `stock_items` (Weed, Cook, Amphetamine,
      Methamphetamine, LSD, MDMA, LEAN avec qualites et prix).
- [x] Deja vendables via `/ventes` (cf. SaleController::loadCatalog).

### 4.1 Referentiel drogues (deja en place, a completer) [PARTIEL]
- [x] Stock via `stock_items.category = drug` (14 items seedes).
- [ ] Ajouter la sous-categorie `drug_raw` pour les matieres premieres (tete de weed, graine,
      poudre de cafeine, feuille a rouler) -- deja prevue dans la taxonomie.
- [ ] Ajouter la sous-categorie `farm_consumable` pour engrais, spray pesticide.
- [ ] Etendre le seeder avec les donnees de `drogue_indicatif.png` (prix detailles par qualite,
      source de fabrication), stockees dans `stock_items.notes` ou dans `settings` dediees :

| Drogue | Prix PNJ (Rue) | Prix au sac | Prix vente Orga | Prix min staff | Fabrication |
|--------|----------------|-------------|-----------------|----------------|-------------|
| Weed Blue Dream | 140-180 | 100 | / | / | Inde-Gangs-MC |
| Weed White Widow | 80-140 | 65 | / | / | Inde-Gangs-MC |
| Weed Purple | 50-80 | 45 | / | / | Inde-Gangs-MC |
| Weed OG Kush | 30-50 | 30 | / | / | Inde-Gangs-MC |
| Cook | 350-750 | 450 | 300-350 | 300 | Orga-Famille |
| Amphetamine basse | 500 | 400 | 400 | 400 | Orga-Famille |
| Amphetamine moyen | 900 | 600 | 700-800 | 700 | Orga-Famille |
| Amphetamine haute | 1000 | 800 | 800-900 | 800 | Orga-Famille |
| Methamphetamine basse | 600-750 | 1000 | 550 | 550 | Orga-Famille |
| Methamphetamine moyen | 1000-1500 | 1400 | 900 | 900 | Orga-Famille |
| Methamphetamine haute | 2000-2600 | 2200 | 1300-1400 | 1400 | Orga-Famille |
| LSD | 3800 | / | 3000-3500 | 3000 | Cayo |
| MDMA | 2900 | / | 2000-2500 | 2000 | Cayo |
| LEAN | 2400 | / | 1600-2000 | 1600 | Cayo |

### 4.2 Flux de gestion des drogues [A FAIRE]
- [ ] **Achat aux organisations** : formulaire dedie creant un `StockMovement`
      `reason=purchase` avec `unit_cost` (prix d'achat orga) et notes (fournisseur).
- [ ] **Attribution a un membre/prospect** : reutilise le formulaire generique de Phase 3.2
      (`reason=attribution`, `attributed_to_user_id`).
- [ ] **Reconciliation** : reutilise le flux generique de Phase 3.3 (vendu -> `Sale`, perdu
      -> `adjustment`, retour -> `adjustment`).
- [ ] **Dashboard drogue** : page `/drogues` (ou filtre `category=drug` sur `/stocks`) avec
      stock total, stock en exterieur, detail par membre, pertes, profit cumule.
- [ ] Calcul automatique profit/perte : somme des `sales.total_price` des drogues - somme des
      `stock_movements.unit_cost * quantity_change` des achats.

---

## Phase 5 - Armes blanches [A FAIRE]
**Priorite : basse -- items deja en place, il ne reste que l'UI de vente dediee**

### Etat actuel (post-Phase H)
- [x] 10 items `category=melee` deja seedes dans `stock_items` avec `default_sell_price`
      (multiplicateur x1.5 applique).
- [x] Deja vendables via `/ventes` (optgroup "Armes blanches" dans le Tom Select).

### 5.1 Referentiel armes blanches (deja en place) [FAIT]
- [x] 10 items seedes dans `stock_items` :

| Arme | Prix d'achat | Prix de vente (x1.5) |
|------|-------------|---------------------|
| Switchblade | 20 000 | 30 000 |
| Knife | 20 000 | 30 000 |
| Machete | 20 000 | 30 000 |
| Batte | 12 000 | 18 000 |
| Queue de billard | 12 000 | 18 000 |
| Golf Club | 12 000 | 18 000 |
| Pied de biche | 15 000 | 22 500 |
| Hammer | 15 000 | 22 500 |
| Cle anglaise | 15 000 | 22 500 |

- [x] Multiplicateur de vente x1.5 applique au seeding (valeur stockee sur chaque item).
- [ ] **Reste a faire** : rendre le multiplicateur configurable via `settings` et recalculer
      `default_sell_price` dynamiquement sur edition depuis le panel Filament.
- [x] Integration dans le flux de vente generique via `/ventes` (deja operationnel).

---

## Phase 6 - Fiches membres detaillees
**Priorite : moyenne -- suivi individuel**

> Les classements (ancienne 6.1) ont ete remontes dans la Phase 3B.4 car ils sont
> directement lies a l'UX quotidienne du MC et a la motivation des membres.

### 6.1 Fiches membres (ancienne 6.2)
- [ ] Page `/membres/{id}` accessible uniquement par officiers et president
- [ ] Contenu de la fiche :
  - Informations : nom, role, date d'arrivee
  - Items actuellement en sa possession (pris du stock, non reconcilies)
  - Historique des ventes (montant total, nombre, dernieres ventes)
  - Historique des mouvements de stock (prises, retours, pertes)
  - Argent rapporte au MC (total, ce mois, cette semaine)
  - Cotisations : etat des paiements
- [ ] Liste des membres : `/membres` avec filtres par role, tri par activite

---

## Phase 7 - Comptabilite MC
**Priorite : basse -- complexe mais necessaire a terme**

### 7.1 Suivi des comptes
> **Note** : pas besoin d'une table `mc_accounts` avec balance. Les objets "argent sale" et "argent propre" sont des `stock_items` normaux. Toute vente se fait en argent sale. Le blanchiment convertit argent sale → argent propre.
- [ ] Creer une table `mc_transactions` :
  - `amount`, `reason`, `category` (vente, achat, amende, entretien, cotisation, autre)
  - `currency_type` : argent_sale ou argent_propre (ref stock_items)
  - `created_by_user_id`, `validated_by_user_id`
  - `requires_validation`, `validated_at`
  - `notes`, `created_at`
- [ ] Page `/comptabilite` accessible tresorier/president : vue des soldes (= quantite stock argent_sale + argent_propre), historique transactions

### 7.2 Systeme de demandes -- FAIT
- [x] Table `mc_requests` : user_id, category (amende/entretien_moto/equipement/medical/autre), amount, description, photo_path, status (pending/approved/rejected), handled_by_user_id, handled_at, handler_notes
- [x] Les membres peuvent soumettre des demandes de remboursement avec photo justificative
- [x] Le tresorier/president valide ou refuse avec notes
- [ ] Le tresorier peut encoder directement des transactions sans demande (→ 7.1)
- [x] Historique complet avec onglets (nouvelle demande / mes demandes / toutes les demandes)
- [x] Upload photo vers `public/img/demandes/` (pas de symlink storage, ISPConfig)

### 7.3 Cotisations
- [ ] Table `cotisations` :
  - `user_id`, `period_start`, `period_end`
  - `amount_due`, `amount_paid`, `paid_at`
  - `notes`
- [ ] Montants par defaut selon le role (configurable dans les parametres) :
  - Prospect : 2 000 / semaine
  - Membre : 5 000 / semaine
  - Officier : 10 000 / semaine
- [ ] Page de suivi des cotisations :
  - Indiquer le jour de cotisation qui a paye, combien (possibilite de payer plus)
  - Vue tresorier : qui est a jour, qui doit encore
- [ ] Alertes pour les retards de paiement

---

## Phase 8 - Ameliorations UX et polissage
**Priorite : continue -- en parallele des autres phases**

### 8.1 Responsive / mobile
- [ ] Audit complet du CSS sur mobile (simulateur, stocks, classements)
- [ ] Navigation hamburger ou bottom tabs sur mobile
- [ ] Boutons et zones de touch suffisamment grands

### 8.2 Notifications in-app
- [ ] Systeme de notifications simples (table `notifications`)
- [ ] Badge compteur dans le header
- [ ] Types : mouvement de stock en attente, cotisation due, contrat mis a jour, nouveau classement

### 8.3 Dashboard MC
- [ ] Page d'accueil personnalisee selon le role :
  - Membre : ses stats, ses items, rappel cotisation
  - Officier : alertes stock, mouvements en attente, classement
  - Tresorier : soldes, transactions recentes, cotisations en retard
  - President : vue globale de tout

---

## Resume des tables

| Phase | Tables | Statut |
|-------|--------|--------|
| 0 | `settings`, `page_access_rules` | CREEES |
| 2 | `sales` (unifiee, FK `stock_item_id`) | CREEE |
| H | `stock_items`, `stock_movements` (taxonomie 12 categories) | CREEES |
| H | Suppression de `weapon_stocks`, `weapon_stock_movements`, `weapon_sales` | FAIT |
| 3 | Colonnes `requires_approval`, `reconciled_at` etc. sur `stock_movements` | FAIT |
| 3B | -- (utilise tables existantes + settings `rankings.*`) | FAIT |
| 4 | Extension `stock_items` via categorie (`drug_raw`, `farm_consumable` a seeder) | SEEDER A ETENDRE |
| 5 | Extension `stock_items` categorie `melee` | SEEDE |
| 6 | -- (utilise les tables existantes) | -- |
| 7 | `mc_requests` (FAIT), `mc_transactions`, `cotisations` | EN COURS |
| 8 | `notifications` | A FAIRE |

---

## Ordre de realisation recommande (mis a jour)

```
Phase 0 (prerequis)           -- TERMINEE
  |
  v
Phase 1 (reorganisation UX)   -- TERMINEE
  |
  v
Phase 2 (ventes rapides)      -- TERMINEE
  |
  v
Phase H (harmonisation)       -- TERMINEE (tables unifiees)
  |
  v
Phase 3 (UI stocks + attrib.) -- TERMINEE
  |
  v
Phase 3B (UX pratique MC)     -- TERMINEE
  |  3B.0 Nettoyage (bouton Admin)     ✓
  |  3B.1 Stocks complets              ✓
  |  3B.2 Attributions en masse        ✓
  |  3B.3 Vente Express                ✓
  |  3B.4 Classements configurables    ✓
  |
  +---> Phase 7.2 (demandes)  -- TERMINEE (mc_requests + photo upload)
  |
  +---> Phase 4 (drogues flux) -- 1-2 jours (items seedes, flux a brancher)
  |
  +---> Phase 5 (armes bl. UI) -- 0.5 jour (items seedes, deja vendables)
  |
  v
Phase 6 (fiches membres)      -- 1-2 jours
  |
  v
Phase 7.1/7.3 (compta+cotis.) -- 2-3 jours
  |
  v
Phase 8 (polissage)           -- continu
```

**Estimation totale restante : ~8-12 jours de developpement (Phases 3B a 8)**
La Phase 3B est la priorite immediate. Elle ameliore directement l'experience
quotidienne des motards du MC : mouvements de stock simplifies, attributions
pratiques, vente express ultra-rapide, et classements pour la motivation.

---

## Notes importantes

1. **Tout configurable en DB** : chaque prix, recette, multiplicateur doit etre stocke dans la table `settings` et modifiable via la page parametres (tresorier/president uniquement).

2. **Droits par page** : chaque page/fonctionnalite doit verifier le role minimum requis. La matrice des droits sera definie en Phase 0.2.

3. **Stock : quantite en stock vs exterieur** : toujours distinguer ce qui est physiquement en stock de ce qui a ete confie a un membre. L'import CSV ecrase le stock physique, pas les attributions.

4. **Pas de suppression physique** : utiliser du soft delete ou un flag `is_active` pour garder l'historique complet.

5. **Prix d'achat aux orga (drogues)** : manquant dans `drogue_indicatif.png`, a completer manuellement dans les parametres.

6. **Harmonisation (REGLE DE FER post-Phase H)** : une seule table pour chaque concept.
   `stock_items` pour tout catalogue/stock, `stock_movements` pour tout mouvement, `sales`
   pour toute vente. Toute nouvelle verticale (drogues, armes blanches, consommables, outils,
   electronique) passe par `stock_items.category` sans creer de table dediee. Toute ecriture
   de mouvement passe par `stock_movements`, toute vente par `sales`.

---

## Annexe A -- Inventaire observe dans le coffre MC (in-game)

Total observe au 16 avril 2026 : 309 kg / 1000 kg max.
Ces items doivent etre couverts par les Phases 3 (stocks generique), 4 (drogues) et 5 (armes blanches).

### Categories

**A.1 Armes finies (categorie `weapon_finished`)**
- PISTOL .50 (differentes masses : 2.00 / 2.225 / 2.405 kg)
- SNS PISTOL (465g / 555g)

**A.2 Corps et pieces armurerie (categorie `piece`)**
- CORPS DE PISTOLET, CORPS DE SMG, CORPS DE FUSILS
- CANON, POIGNEE, CROSSE, RESSORT
- TACTICAL SUPPRESSOR, SUPPRESSOR

**A.3 Matieres premieres armurerie (categorie `raw_material`)**
- POUDRE A CANON, FRAGMENT DE METAL, PIECE DE METAL

**A.4 Munitions (categorie `ammo`)**
- .45 ACP, 9MM, .50 AE, .50 BMG
- 12 GAUGE, 5.56x45, 7.62x51, 7.62x39

**A.5 Plans d'armes (categorie `plan`)**
- PLAN CALIBRE 50 (x5 au moins)
- PLAN MG, PLAN MACHINE ..., PLAN PISTOLET
- PLAN COMBAT P... (x2 au moins)
- PLAN AK47, PLAN AK COMPACT
- PLAN FUSIL A P..., PLAN MINI SMG

**A.6 Armes blanches (categorie `melee`, Phase 5)**
- KNIFE (300g)
- KATANA BXLIFE (500g)
- (a completer : Switchblade, Machete, Batte, Queue de billard, Golf Club, Pied de biche,
  Hammer, Cle anglaise -- liste Phase 5)

**A.7 Drogues finies (categorie `drug`, Phase 4)**
- BRIQUE DE WEED (100g)
- BRIQUE DE COCAINE (400g)
- SACHET DE WEED (5g / 1kg selon qualite), SACHET PLASTIQUE
- JOINT (PURPLE / ...) (3g)
- METH (HAUTE Q...) (100g)
- COCAINE (70g)

**A.8 Matieres premieres drogues (categorie `drug_raw`, Phase 4)**
- TETE DE WEED (basse / moyenne / haute qualite selon poids)
- GRAINE DE WEED (3 varietes : 1g / 1g / 1g selon couleur -- Blue Dream, White Widow, Purple...)
- POUDRE DE CAFEINE
- FEUILLE A ROULER

**A.9 Consommables agricoles (categorie `farm_consumable`, Phase 4)**
- ENGRAIS (VITESSE)
- ENGRAIS (BOOSTER)
- SPRAY PESTICIDE

**A.10 Outils et accessoires (categorie `tool`, Phase 3 ou extension)**
- DECOUPEUR PLASMA
- MEULEUSE D'ANGLE
- FOREUSE
- OUTIL DE CROCHETAGE
- CLE USB PHANTOM
- MENOTTES

**A.11 Electronique (categorie `electronic`, futur)**
- GRAND ECRAN POUR ORDINATEUR
- PETIT ECRAN POUR ORDINATEUR
- CARTE ELECTRONIQUE (2 tailles)
- CARTE DE PIRATAGE
- MACHINE DE TRANSFERT (x2)
- FIL DE CUIVRE

**A.12 Divers / sacs**
- SAC A METTRE [...] (plusieurs variantes)
- ARGENT SALE (561 235$ observes)

### Implications pour les phases

- **Phase 3** doit prevoir une taxonomie `stock_items.category` riche :
  `weapon_finished`, `piece`, `raw_material`, `ammo`, `plan`, `melee`, `drug`, `drug_raw`,
  `farm_consumable`, `tool`, `electronic`, `misc`.
- **Phase 4 (drogues)** doit distinguer drogues finies / matieres premieres / consommables agricoles.
- Un champ `unit_weight_g` sur `stock_items` permettrait de calculer l'occupation du coffre
  (309/1000 kg) et d'eviter de charger plus que la capacite.
- L'import CSV/Excel (Phase 3.5) doit pouvoir associer chaque ligne a un item existant ou creer
  un nouvel item avec sa categorie.
