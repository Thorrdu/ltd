# Tech Context - Station LTD / Toolbox Lost MC

## Technologies
- **Laravel 12.x** : framework PHP backend
- **Filament 5.3.x** : panneaux d'administration (2 panneaux : admin + armurerie)
- **MySQL 8.0.30** : base de donnees (root, sans mot de passe, base `ltd`)
- **Vite** : build des assets front (via laravel-vite-plugin)
- **Node.js 22.14.0** : runtime pour le build front
- **PHP 8.3.21** : runtime backend (Laragon)
- **Composer** : gestionnaire de dependances PHP
- **Blade** : moteur de templates pour les pages publiques
- **Tom Select 2.3.1** : selects recherchables cote front (CDN jsdelivr)
- **CSS custom** : fichiers originaux catalogue + simulateur + layout MC + theme Tom Select
- **JS custom** : mc-auth.js, simulateur-armes.js, simulateur-munitions.js, membres.js

## Environnement de developpement
- Workspace : `c:\laragon\www\ltd`
- Serveur : Laragon (Apache sur Windows) + `php artisan serve --port=8080`
- URL locale : `http://ltd.test/` (ou `http://127.0.0.1:8080` via artisan serve)
- URL admin : `/admin`
- URL armurerie : `/armurerie`
- URL hub MC : `/mc`
- URL gestion membres : `/membres`
- Base de donnees : MySQL `ltd` (root, sans mot de passe)

## Configuration
- `.env` : configuration Laravel (DB, APP_KEY, etc.)
- `.cursorignore` : contient `!.env` pour que Cursor puisse lire le fichier
- `_backup/` : ancien projet statique sauvegarde
- `bootstrap/providers.php` : enregistre AdminPanelProvider + ArmureriePanelProvider

## Modeles Eloquent (13 modeles)

### Domaine catalogue LTD
- `Category`, `Product`, `Menu`, `Enterprise`

### Domaine armurerie
- `Weapon`, `WeaponStock`, `WeaponStockMovement`, `WeaponContract`, `WeaponContractItem`, `WeaponSale`

### Utilisateurs / acces
- `User` : name, email, password (hashed), role, is_active, sim_pin (hidden)
  - Constante `ROLES` : prospect(1), member(2), officer(3), vice_president(4), president(5), treasurer(99)
  - Constante `SUPERADMIN_ROLE = 'treasurer'`
  - Helpers : `isProspect`, `isMember`, `isOfficer`, `isVicePresident`, `isPresident`, `isTreasurer`, `isSuperadmin`, `isAtLeast`, `canAssignRole`, `assignableRoles`
  - Integration Filament : `canAccessPanel(Panel)` delegue a `PageAccessRule`
  - Nouveau helper : `canAccessPage(string $key)` pour pages custom
- `PageAccessRule` : page_key, label, min_role, description, sort_order, is_system (cache 10 min)
- `Setting` : group, key, label, type, value, description, sort_order

## Base de donnees (15 migrations)

### Tables principales
- `users`, `cache`, `jobs` (Laravel standard)
- `categories`, `products`, `menus`, `enterprises` (catalogue)
- `menu_product`, `enterprise_product` (pivots catalogue)
- `weapons`, `weapon_stocks`, `weapon_stock_movements` (stocks armes)
- `weapon_contracts`, `weapon_contract_items`, `weapon_sales` (contrats + ventes)
- `settings` (parametres globaux)
- `page_access_rules` (matrice d'acces)

### Migrations ajoutees apres mars 2026
- 2026-03-11 : `enterprise_price` sur products
- 2026-03-28 : `promo_price` sur products et menus
- 2026-04-14 : `role` sur users, tables armes, `sim_pin`, `unit_cost`
- 2026-04-16 (matin) : `reference_purchase_price`, `price_min`/`price_max` sur weapons, `settings`
- 2026-04-16 (soir) : `is_active` sur users, `page_access_rules`

## Seeders (7 fichiers)
- `DatabaseSeeder` : orchestre tous les seeders
- `CategoryProductSeeder` : categories + 62 produits
- `EnterpriseSeeder` : entreprises partenaires
- `MenuSeeder` : menus et promos
- `UserSeeder` : 10 utilisateurs avec roles et PIN
- `WeaponSeeder` : armes, stocks matieres/pieces/plans/armes finies
- `SettingSeeder` : 19 parametres globaux (matieres, pieces, recettes, multiplicateurs, cotisations)
- `PageAccessRuleSeeder` : 13 regles d'acces (panneaux Filament + pages MC + pages futures)

## URLs

### Catalogue LTD
- `GET /`, `/produits`, `/menus`, `/entreprises`

### Hub MC + simulateurs
- `GET /mc` : hub
- `GET /simulateur-armes`, `GET /simulateur-munitions`
- `GET /espace-membres` : dashboard

### API simulateur (`/simulateur-armes/api/*`)
- `POST /login`, `GET /data`, `POST /sale`, `POST /movement`
- `POST /contract`, `PUT /contract/{id}`
- `POST /contract/{id}/items`, `PUT /item/{id}`, `DELETE /item/{id}`
- `POST /member`, `PUT /member/{id}` (existants, desormais piloteS par canAssignRole)
- `POST /change-pin`

### Gestion membres et matrice d'acces (`/membres`)
- `GET /membres` : page de gestion
- `GET /membres/api/list` : liste + roles + assignable
- `POST /membres/api/create`, `PUT /membres/api/{id}`, `DELETE /membres/api/{id}`
- `POST /membres/api/{id}/reset-pin`
- `GET /membres/api/matrix`, `PUT /membres/api/matrix/{id}` (superadmin)

### Panneaux Filament
- `GET /admin`, `GET /armurerie` (acces regi par `PageAccessRule` + `User::canAccessPanel`)

## Credentials
- Admin Filament : admin@ltd.test / admin (role `treasurer`, superadmin)
- Les autres membres ont un email genere `{slug}@lost.mc` et un PIN dans `UserSeeder`
- MySQL : root / (sans mot de passe)
- Mot de passe page entreprises (cote client) : ltd2026

## Points d'attention techniques
- Pas de package Spatie Permissions, Fortify, Breeze ou Sanctum
- Auth MC par PIN stocke en hash dans `users.sim_pin`, authentification cote client via header `X-Sim-User`
- Requetes CSRF : token envoye via header `X-CSRF-TOKEN` dans toutes les requetes API front
- Les routes API simulateur et membres sont dans `web.php` (pas dans `api.php`)
- Tom Select est charge via CDN, le theme dark MC vient de `public/css/mc-tom-select.css`
- Le cache de `PageAccessRule` doit etre invalide apres une migration fresh (il l'est automatiquement sur save/delete du modele)
- Les vues compilees Blade s'accumulent dans `storage/framework/views/` -- purger avec `php artisan view:clear`
- Vhost Laragon pointe sur la racine du projet, pas sur `public/` (a corriger)

## Dependances front (CDN)
- Tom Select 2.3.1 : `https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js`
- CSS Tom Select : `https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.min.css`
