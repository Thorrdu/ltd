# System Patterns - Station LTD / Toolbox Lost MC

## Architecture globale
- Application Laravel 12 avec **deux panneaux Filament 5** :
  - `/admin` : gestion catalogue LTD (AdminPanelProvider)
  - `/armurerie` : gestion armes/stocks/contrats/ventes (ArmureriePanelProvider)
- Pages publiques en Blade (catalogue LTD + simulateur armes)
- CSS custom dans `public/css/` (design original + simulateur)
- JS custom dans `public/js/` (simulateur-armes.js)
- Assets front compiles via Vite
- Base de donnees MySQL 8.0

## Domaine 1 : Catalogue LTD

### Principe central : Product unifie
Tous les produits vivent dans une seule table `products` avec flags `is_retail` et `is_enterprise`.
Les menus et entreprises y font reference via des tables pivot.

### Schema relationnel
- `categories` : groupes de produits (SNACKS, BOISSONS, etc.) avec colonne left/right
- `products` : source unique, flags is_retail/is_enterprise, prix retail/purchase/usual/promo/enterprise, sort_order
- `menus` : type menu/promo, prix, promo_price, texte promo
- `enterprises` : entreprises partenaires (notes pour conditions de contrat)
- `menu_product` : pivot avec `choice_group` pour produits interchangeables
- `enterprise_product` : pivot avec `price` specifique par entreprise

### Resources Filament (Admin)
- `CategoryResource` : CRUD categories + RelationManager produits
- `ProductResource` : CRUD produits + filtres + bulk actions
- `MenuResource` : CRUD menus + RelationManager produits avec pivot choice_group
- `EnterpriseResource` : CRUD entreprises + RelationManager produits avec pivot price
- Widgets : StatsOverviewWidget, LatestProductsWidget

## Domaine 2 : Armurerie

### Schema relationnel
- `weapons` : armes avec recettes de craft (recipe_*), temps de craft, prix de vente, prix reference achat, prix min/max
- `weapon_stocks` : stocks par categorie (matieres, pieces, plans, armes finies) avec quantite
- `weapon_stock_movements` : historique des mouvements (entree/sortie, raison, cout unitaire, utilisateur, attribution)
- `weapon_contracts` + `weapon_contract_items` : contrats clients avec items commandes/livres
- `weapon_sales` : ventes d'armes (quantite, prix unitaire, acheteur, vendeur)

### Categories de stock
Constantes dans WeaponStock : matieres premieres, pieces detachees, plans, armes finies.

### Resources Filament (Armurerie)
- `WeaponResource` : CRUD armes avec recettes et prix
- `WeaponStockResource` : consultation des stocks
- `WeaponStockMovementResource` : creation et historique des mouvements
- `WeaponContractResource` : CRUD contrats + RelationManager items
- `WeaponSaleResource` : CRUD ventes
- Page `CraftWeapon` : interface de craft
- Widget : ArmurerieStatsWidget

## Domaine 3 : Simulateur armes (Frontend)

### Architecture
- Page Blade standalone (`/simulateur-armes`)
- JS monolithique (`public/js/simulateur-armes.js`) gerant :
  - Calcul de prix de revient et prix de vente
  - Espace membres (login PIN, ventes, stats)
  - Craft munitions
- API JSON dans `WeaponSimController` (routes web, auth par `X-Sim-User` header + PIN)

## Authentification et roles

### Systeme actuel
- Champ `role` simple sur table `users` (pas de Spatie Permissions)
- Champ `sim_pin` pour authentification dans le simulateur
- Methode `isOfficer()` sur User
- `canAccessPanel()` retourne `true` pour tous les utilisateurs
- Login Filament natif sur `/admin` et `/armurerie`
- Login simulateur par PIN (WeaponSimController@login)

### A developper
- Filtrage par role dans `canAccessPanel()` pour chaque panneau
- Roles granulaires (prospect, membre, officier, tresorier, president)
- Droits d'acces par page/fonctionnalite

## Pattern Blade (pages publiques catalogue)

### Design CSS preserve
- Fond navy (#0d1b2e) + image floue en pseudo-elements
- Panneau bois (#f5f0e6) avec ombres et animation boardFadeIn
- Dot leaders, zebra stripes, tailles compactes
- Mode clean (?clean) : JS masque la navigation
- Password overlay entreprises : JS cote client (ltd2026)

### Middleware
- `AllowIframe` : CSP `frame-ancestors *`, CORS large sur GET

## Routes web.php
- Pages catalogue LTD (groupe AllowIframe) : `/`, `/produits`, `/menus`, `/entreprises`
- Simulateur : `GET /simulateur-armes`
- API simulateur (POST/GET/PUT) : login, data, sale, movement, contract, member, change-pin

## Conventions
- Prix en entiers (pas de centimes, pas de float)
- Euro affiche cote front uniquement
- Noms de categories en majuscules
- sort_order sur toutes les entites ordonnees
- Couleurs catalogue : navy (#0d1b2e), rouge (#8b0000), creme (#f5f0e6)
- Panneau armurerie : dark mode
- Code et commentaires en anglais, contenu affiche en francais
- Seeders dans database/seeders/

## Structure des dossiers cles
```
app/Filament/
  Resources/                  -- Resources admin LTD (Category, Product, Menu, Enterprise)
  Widgets/                    -- Dashboard widgets admin
  Armurerie/
    Resources/                -- Resources armurerie (Weapon, Stock, Movement, Contract, Sale)
    Pages/                    -- CraftWeapon
    Widgets/                  -- ArmurerieStatsWidget
app/Http/Controllers/
  PageController.php          -- Pages publiques catalogue
  WeaponSimController.php     -- Vue simulateur + API JSON
app/Http/Middleware/
  AllowIframe.php             -- CSP/CORS pour iframe
app/Models/                   -- 11 modeles Eloquent
database/migrations/          -- 21 migrations
database/seeders/             -- 5 seeders + DatabaseSeeder
docs/                         -- architecture.md, reglement-bxl-life/, tasks/
memory-bank/                  -- Documentation Memory Bank
public/css/                   -- CSS catalogue (5) + simulateur (1)
public/js/                    -- simulateur-armes.js
public/img/                   -- Logo, photos
resources/views/              -- Blade templates (layouts/, 5 pages + 1 filament custom)
```
