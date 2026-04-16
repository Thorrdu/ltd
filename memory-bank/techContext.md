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
- **CSS custom** : fichiers originaux catalogue + simulateur
- **JS custom** : simulateur-armes.js (monolithique, ~1446 lignes)

## Environnement de developpement
- Workspace : `c:\laragon\www\ltd`
- Serveur : Laragon (Apache sur Windows)
- URL locale : `http://ltd.test/`
- URL admin : `http://ltd.test/admin`
- URL armurerie : `http://ltd.test/armurerie`
- Base de donnees : MySQL `ltd` (root, sans mot de passe)

## Configuration
- `.env` : configuration Laravel (DB, APP_KEY, etc.)
- `.cursorignore` : contient `!.env` pour que Cursor puisse lire le fichier
- `_backup/` : ancien projet statique sauvegarde
- `bootstrap/providers.php` : enregistre AdminPanelProvider + ArmureriePanelProvider

## Modeles Eloquent (11 modeles)

### Domaine catalogue LTD
- `Category` : name, column (left/right), sort_order -- hasMany Product
- `Product` : category_id, name, purchase_price, usual_price, price, promo_price, enterprise_price, is_retail, is_enterprise, sort_order -- belongsTo Category
- `Menu` : type (menu/promo), name, price, promo_price, promo_text, sort_order -- belongsToMany Product
- `Enterprise` : name, notes, sort_order -- belongsToMany Product (pivot price)

### Domaine armurerie
- `Weapon` : name, slug, craft_time_seconds, sell_price, recipe_*, reference_purchase_price, price_min, price_max, is_active, sort_order
- `WeaponStock` : category, weapon_id, name, slug, quantity, sort_order (constantes CATEGORIES)
- `WeaponStockMovement` : weapon_stock_id, quantity_change, reason, unit_cost, weapon_contract_id, user_id, attributed_to_user_id, notes
- `WeaponContract` : name, client_name, status, notes, created_by_user_id (constantes STATUSES)
- `WeaponContractItem` : weapon_contract_id, weapon_id, qty_ordered, qty_delivered
- `WeaponSale` : weapon_id, weapon_contract_id, quantity, unit_price, buyer_name, user_id, sold_by_user_id, notes

### Utilisateurs
- `User` : name, email, password (hashed), role, sim_pin (hidden) -- FilamentUser, isOfficer(), checkSimPin()

## Base de donnees (21 migrations)

### Tables principales
- `users`, `cache`, `jobs` (Laravel standard)
- `categories`, `products`, `menus`, `enterprises` (catalogue)
- `menu_product`, `enterprise_product` (pivots catalogue)
- `weapons`, `weapon_stocks`, `weapon_stock_movements` (stocks armes)
- `weapon_contracts`, `weapon_contract_items` (contrats)
- `weapon_sales` (ventes)

### Migrations ajoutees apres mars 2026
- 2026-03-11 : `enterprise_price` sur products
- 2026-03-28 : `promo_price` sur products et menus
- 2026-04-14 : `role` sur users, tables armes (weapons, stocks, movements, contracts, sales), `sim_pin`, `unit_cost`
- 2026-04-16 : `reference_purchase_price`, `price_min`/`price_max` sur weapons

## Seeders (6 fichiers)
- `DatabaseSeeder` : orchestre tous les seeders
- `CategoryProductSeeder` : categories + 62 produits
- `EnterpriseSeeder` : entreprises partenaires
- `MenuSeeder` : menus et promos
- `UserSeeder` : utilisateurs avec roles et PIN
- `WeaponSeeder` : armes, stocks matieres/pieces/plans/armes finies

## URLs
- `GET /` : accueil catalogue LTD
- `GET /produits` : produits retail
- `GET /menus` : menus et promos
- `GET /entreprises` : tarifs entreprises
- `GET /simulateur-armes` : simulateur d'armes
- `POST /simulateur-armes/login` : auth PIN simulateur
- `GET /simulateur-armes/data` : donnees simulateur (API JSON)
- `POST /simulateur-armes/sale` : enregistrer une vente
- `POST /simulateur-armes/movement` : enregistrer un mouvement de stock
- `POST /simulateur-armes/contract` : creer un contrat
- API contrats, items, membres, change-pin (voir routes/web.php)
- `GET /admin` : panneau Filament catalogue LTD
- `GET /armurerie` : panneau Filament armurerie

## Credentials
- Admin Filament : admin@ltd.test / admin
- MySQL : root / (sans mot de passe)
- Mot de passe page entreprises (cote client) : ltd2026

## Points d'attention techniques
- Le modele Weapon n'a pas `price_min`/`price_max` dans `$fillable` alors que la DB et le seeder les utilisent (a corriger)
- `canAccessPanel()` retourne `true` pour tous les utilisateurs (pas de filtrage par role)
- Pas de package Spatie Permissions, Fortify, Breeze ou Sanctum
- Les routes API simulateur sont dans web.php (pas dans api.php)
- Le modele s'appelle `Enterprise` (pas `EnterpriseGroup` comme dans l'ancienne doc)
