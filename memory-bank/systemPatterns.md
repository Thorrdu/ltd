# System Patterns - Station LTD (Laravel + Filament)

## Architecture
- Application Laravel 11 avec panneau Filament 3
- Pages publiques en Blade (design reproduit depuis l'ancien site statique)
- CSS custom dans `public/css/` (reutilisation des fichiers originaux)
- Images dans `public/img/` (logo PNG, photos station)
- Assets front compiles via Vite (npm)
- Base de donnees MySQL 8.0

## Principe central : Product unifie
Tous les produits vivent dans une seule table `products` avec flags `is_retail` et `is_enterprise`.
Les menus et groupes entreprise y font reference via des tables pivot avec champs supplementaires.

## Schema relationnel

### Tables principales
- `categories` : groupes de produits (SNACKS, BOISSONS, etc.) avec colonne left/right
- `products` : source unique, flags is_retail/is_enterprise, prix retail, sort_order
- `menus` : type menu/promo, prix du menu, texte promo
- `enterprise_groups` : entreprises partenaires (AutoV, Jupiter, etc.)

### Tables pivot
- `menu_product` : lie menus et produits, champ `choice_group` pour les produits interchangeables
- `enterprise_group_product` : lie groupes entreprise et produits, champ `price` specifique par entreprise

### Logique choice_group
Quand un menu offre un choix entre plusieurs produits (ex: "Fruit au choix"), ceux-ci partagent le meme `choice_group` dans le pivot. A l'affichage, le groupe est rendu comme une seule ligne descriptive.

## Pattern Filament

### Resources
- `CategoryResource` : CRUD categories + RelationManager produits
- `ProductResource` : CRUD produits + filtres (categorie, retail/enterprise, prix) + bulk actions
- `MenuResource` : CRUD menus + RelationManager produits avec pivot choice_group
- `EnterpriseGroupResource` : CRUD groupes + RelationManager produits avec pivot price

### Dashboard Widgets
- `StatsOverviewWidget` : compteurs (produits retail, enterprise, menus, groupes)
- `LatestProductsWidget` : 5 derniers produits modifies

## Pattern Blade (pages publiques)

### Layout
- `layouts/app.blade.php` : structure commune (head, CSS links, body)
- 4 vues : welcome, produits, menus, entreprises

### Controller
- `PageController` : un controller, 4 methodes, chacune query la DB et passe les donnees

### Design CSS preserve
- Fond navy (#0d1b2e) + image floue en pseudo-elements (body::before, body::after)
- Panneau bois (#f5f0e6) avec ombres et animation boardFadeIn
- Dot leaders (span.dot-leader flex:1 border-bottom dotted)
- Zebra stripes (product-row:nth-child(even))
- Tailles compactes (logo 50px, titre 26px, lignes 13px)
- Mode clean (?clean) : JS masque la navigation
- Password overlay entreprises : JS cote client (ltd2026)

### Routes web.php
- GET / -> PageController@home
- GET /produits -> PageController@produits
- GET /menus -> PageController@menus
- GET /entreprises -> PageController@entreprises

## Conventions
- Prix en entiers (pas de centimes, pas de float)
- Euro affiche cote front uniquement
- Noms de categories en majuscules
- Couleurs : navy (#0d1b2e, #0d2347), rouge (#8b0000, #c0392b), creme (#f5f0e6)
- sort_order sur toutes les entites ordonnees
- Panneau admin accessible via /admin
- Seeders lisent les JSON depuis _backup/data/

## Structure des dossiers cles
```
app/Filament/Resources/     -- Resources et RelationManagers Filament
app/Http/Controllers/       -- PageController (pages publiques)
app/Models/                 -- Category, Product, Menu, EnterpriseGroup, User
database/migrations/        -- 6 migrations
database/seeders/           -- CategoryProductSeeder, EnterpriseSeeder, MenuSeeder, UserSeeder
docs/                       -- architecture.md, reglement-bxl-life/, tasks/
memory-bank/                -- Documentation Memory Bank
public/css/                 -- CSS design original
public/img/                 -- Logo, photos
resources/views/            -- Blade templates (layouts/, 4 pages)
```
