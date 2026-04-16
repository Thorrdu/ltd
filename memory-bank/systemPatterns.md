# System Patterns - Station LTD / Toolbox Lost MC

## Architecture globale
- Application Laravel 12 avec **deux panneaux Filament 5** :
  - `/admin` : gestion catalogue LTD (AdminPanelProvider)
  - `/armurerie` : gestion armes/stocks/contrats/ventes (ArmureriePanelProvider)
- Pages publiques en Blade (catalogue LTD + hub MC + simulateurs + espace membres + gestion membres)
- CSS custom dans `public/css/` (design original + simulateur + layout MC + theme Tom Select)
- JS custom dans `public/js/` (mc-auth.js, simulateur-armes.js, simulateur-munitions.js, membres.js)
- Assets front compiles via Vite, Tom Select 2.3.1 via CDN
- Base de donnees MySQL 8.0

## Domaine 1 : Catalogue LTD

### Principe central : Product unifie
Tous les produits vivent dans une seule table `products` avec flags `is_retail` et `is_enterprise`.
Les menus et entreprises y font reference via des tables pivot.

### Schema relationnel
- `categories`, `products`, `menus`, `enterprises`, `menu_product`, `enterprise_product`

### Resources Filament (Admin)
- `CategoryResource`, `ProductResource`, `MenuResource`, `EnterpriseResource`, `SettingResource`
- Widgets : StatsOverviewWidget, LatestProductsWidget

## Domaine 2 : Armurerie

### Schema relationnel
- `weapons`, `weapon_stocks`, `weapon_stock_movements`, `weapon_contracts`, `weapon_contract_items`, `weapon_sales`

### Resources Filament (Armurerie)
- WeaponResource, WeaponStockResource, WeaponStockMovementResource, WeaponContractResource, WeaponSaleResource
- Page CraftWeapon, widget ArmurerieStatsWidget

### ATTENTION harmonisation (Phase H du plan)
- `weapon_sales` coexiste avec `sales` (Phase 2). Les deux tables sont alimentees selon le chemin
  utilise (`/espace-membres` vs `/ventes`). A fusionner dans la Phase H avant de partir sur
  Phase 3.
- `weapon_stocks` / `weapon_stock_movements` seront candidates a la fusion dans `stock_items` /
  `stock_movements` en Phase 3. Toute evolution doit etre declaree dans la Phase H pour eviter
  un troisieme doublon.

## Domaine 3 : Hub MC et espace membres (Frontend)

### Pages
- `/mc` : hub d'accueil avec grille de boutons (simulateurs + espace membres)
- `/simulateur-armes` : craft d'armes
- `/simulateur-munitions` : craft de munitions
- `/ventes` : saisie rapide des ventes generiques (Phase 2, min_role `member`)
- `/espace-membres` : dashboard membre (stocks, ventes, contrats, historique, gestion rapide)
- `/membres` : gestion complete des utilisateurs + matrice d'acces (VP+ / superadmin)

### Layout partage (`layouts/mc.blade.php`)
- Barre superieure : LOST MC (home) + bouton Login / nom+role+logout
- Motto "Le Tout-Puissant pardonne. Pas les Lost." sous le titre de chaque page
- Dropdown login avec select PIN (Tom Select)
- Toast de notifications
- Assets : `mc-layout.css`, `mc-tom-select.css`, `mc-auth.js`, `tom-select.complete.min.js`

### JS modulaire
- `mc-auth.js` : session `window.McAuth` (login, logout, apiGet/Post/Put/Delete, callbacks)
- `simulateur-armes.js` : simulateur + dashboard membre (~1500 lignes)
- `simulateur-munitions.js` : simulateur munitions autonome
- `membres.js` : page `/membres` (CRUD users + matrice d'acces)
- `ventes.js` : page `/ventes` (formulaire multi-type + historique filtrable)

## Authentification et roles

### Roles et hierarchie
Constante `User::ROLES` avec niveaux numeriques :
| Role            | Niveau | Notes                                    |
|-----------------|-------:|------------------------------------------|
| prospect        |      1 | Nouveau, en probation                    |
| member          |      2 | Membre du MC                             |
| officer         |      3 | Officier                                 |
| vice_president  |      4 | Vice-President                           |
| president       |      5 | President                                |
| treasurer       |     99 | **Superadmin** (cf. `SUPERADMIN_ROLE`)   |

### Helpers `User`
- `isProspect()`, `isMember()`, `isOfficer()`, `isVicePresident()`, `isPresident()`, `isTreasurer()`, `isSuperadmin()`
- `isAtLeast($role)` : compare les niveaux
- `canAssignRole($role)` : strictement superieur sauf superadmin (qui peut tout)
- `assignableRoles()` : liste des roles assignables par cet utilisateur
- `canAccessPanel(Panel)` et `canAccessPage(string $key)` : delegue a `PageAccessRule`

### Auth MC (PIN)
- Champ `sim_pin` sur users (hash)
- API : POST `/simulateur-armes/api/login`
- Session client : `sessionStorage` (`lmc_uid`, `lmc_name`, `lmc_role`)
- Requetes API : header `X-Sim-User` + `X-CSRF-TOKEN`

## Matrice d'acces (nouveau)

### Table `page_access_rules`
- `page_key` (unique), `label`, `min_role`, `description`, `sort_order`, `is_system`
- Cache 10 min dans `Cache::remember(PageAccessRule::CACHE_KEY, ...)`
- Invalidation auto sur save/delete

### Resolution d'acces
1. Superadmin : acces total, quel que soit la regle.
2. Regle absente pour la cle : acces refuse (secure by default).
3. Sinon : `user->isAtLeast($rule->min_role)`.

### Cles de pages seedees
- `panel_admin`, `panel_armurerie` (controles cote Filament)
- `mc_hub`, `simulateur_armes`, `simulateur_munitions`, `espace_membres` (controles cote layout/vue)
- `membres_gestion`, `matrice_acces` (controles cote `/membres`)
- `ventes_rapides`, `stocks_generique`, `comptabilite`, `classements`, `fiches_membres` (futur)

## Pattern API JSON (front MC)

### Convention
- GET pour lecture, POST pour creation, PUT pour mise a jour, DELETE pour suppression
- Requetes envoyees avec methode HTTP native (plus de `X-HTTP-Method-Override`)
- Toutes retournent JSON : `{ok: true, message, ...}` ou `{error: 'message'}`
- Header CSRF obligatoire sur les requetes POST/PUT/DELETE

### MemberController
- `index()` : rend la vue `/membres`
- `apiList()`, `apiCreate()`, `apiUpdate($id)`, `apiResetPin($id)`, `apiDelete($id)`
- `apiMatrix()`, `apiUpdateMatrix($id)` : gestion de la matrice d'acces
- Toutes protegees par `requireAccess($pageKey)` + header `X-Sim-User`

### SaleController (Phase 2)
- `index()` : rend la vue `/ventes` (armes actives + liste membres)
- `apiList()` : supporte `scope=mine|all` et `period=today|week|month|all`, renvoie ventes + totaux
- `apiCreate()` : valide, pour type `weapon` decremente le weapon_stock et cree un mouvement
  de type `sale`
- Toutes protegees par `X-Sim-User` + `canAccessPage('ventes_rapides')` (min_role `member` par defaut)

### WeaponSimController
- `apiData()` expose desormais `assignable_roles` et `can_manage_members`
- `createMember()` et `updateMember()` utilisent `canAssignRole()` et `canAccessPage('membres_gestion')`
- Continue d'ecrire dans `weapon_sales` via `createSale()` -- a rediriger vers `sales` en Phase H

## Pattern Tom Select (dark theme MC)

### Principes
- Theme global dans `public/css/mc-tom-select.css`
- Plugin `dropdown_input` active pour rechercher dans les listes longues
- Rendu custom :
  - Selects stock : badge quantite a droite (`.ts-stock-qty` avec classes `.low`, `.zero`)
  - Selects role : badge colore (`.ts-role-badge.role-xxx`)
- Destruction explicite avant re-population (`destroyTs(el)` puis `initTs(...)`)

## Conventions
- Prix en entiers (pas de centimes, pas de float)
- Euro affiche cote front uniquement
- sort_order sur toutes les entites ordonnees
- Couleurs catalogue : navy (#0d1b2e), rouge (#8b0000), creme (#f5f0e6)
- Panneau armurerie + pages MC : dark mode
- Couleurs de role : treasurer bleu (#60a5fa), president or (#ffd700), vice_president orange (#ff9f43), officer gris, member gris sombre, prospect plus sombre
- Code et commentaires en anglais, contenu affiche en francais
- Seeders dans database/seeders/

## Structure des dossiers cles
```
app/Filament/
  Resources/                  -- Resources admin LTD (Category, Product, Menu, Enterprise, Setting)
  Widgets/                    -- Dashboard widgets admin
  Armurerie/
    Resources/                -- Resources armurerie
    Pages/                    -- CraftWeapon
    Widgets/                  -- ArmurerieStatsWidget
app/Http/Controllers/
  PageController.php          -- Pages publiques catalogue
  WeaponSimController.php     -- Simulateur + API armurerie/membres
  MemberController.php        -- Gestion des utilisateurs + matrice d'acces
app/Http/Middleware/
  AllowIframe.php             -- CSP/CORS pour iframe
app/Models/                   -- 13 modeles Eloquent (dont Setting, PageAccessRule)
database/migrations/          -- 15 migrations appliquees
database/seeders/             -- 7 seeders + DatabaseSeeder
docs/                         -- architecture.md, reglement-bxl-life/, tasks/
memory-bank/                  -- Documentation Memory Bank
LA_SUIITE/                    -- plan-developpement.md, next.md
public/css/                   -- CSS catalogue + simulateur + mc-layout + mc-tom-select
public/js/                    -- mc-auth.js, simulateur-armes.js, simulateur-munitions.js, membres.js
resources/views/              -- Blade templates (layouts/, mc-hub, simulateurs, espace-membres, membres)
```
