# Architecture -- Station LTD (Laravel + Filament)

> Document de reference pour la conversion du projet.
> Mis a jour au fur et a mesure de l'avancement.

## Vue d'ensemble

Le projet passe d'un site statique HTML/CSS/JS a une application Laravel 11 + Filament 3.
Le site public (Blade) reproduit fidelement le design existant (panneau bois, dot leaders, zebra stripes).
Le panneau d'administration (Filament) permet de gerer toutes les donnees via une interface web.

**Personnage** : Jacques Noir, patron de la station LTD
**Serveur** : Bruxelles Life (GTA RP)
**Localisation in-game** : Little Seoul, Anderlecht

---

## Stack technique

| Composant   | Version  | Notes                                      |
|-------------|----------|--------------------------------------------|
| PHP         | 8.3.21   | Laragon, CLI + Apache                      |
| Laravel     | 11.x     | Framework backend                          |
| Filament    | 3.x      | Panneau d'administration                   |
| MySQL       | 8.0.30   | root, sans mot de passe, base `ltd`        |
| Node.js     | 22.14.0  | Build assets via Vite                      |
| Composer    | latest   | Mis a jour depuis 2.4.1                    |
| Workspace   | `c:\laragon\www\ltd` | Remplacement en place       |

---

## Principe cle : Product comme source unique

Tous les produits (retail, enterprise, ingredients de menus) vivent dans une seule table `products`.
Les menus et les groupes entreprise y font reference via des tables pivot.

**Flags sur Product :**
- `is_retail` : le produit apparait sur la page publique des produits
- `is_enterprise` : le produit peut etre attache a un groupe entreprise avec un prix specifique

Un produit peut avoir les deux flags a true (ex: Jerrican d'essence -- 200 en retail, 180 pour AutoV).

---

## Schema de la base de donnees

### Table `categories`

| Colonne    | Type          | Notes                                |
|------------|---------------|--------------------------------------|
| id         | bigint PK     | Auto-increment                       |
| name       | string        | Ex: "SNACKS", "BOISSONS"            |
| column     | enum(left,right) | Disposition sur la page produits  |
| sort_order | int           | Ordre d'affichage                    |
| timestamps | datetime      | created_at, updated_at               |

### Table `products`

| Colonne       | Type          | Notes                                   |
|---------------|---------------|-----------------------------------------|
| id            | bigint PK     | Auto-increment                          |
| category_id   | bigint FK     | Ref categories.id                       |
| name          | string        | Nom du produit                          |
| price         | int           | Prix en euros (entier, pas de centimes) |
| is_retail     | boolean       | Visible sur la page produits publique   |
| is_enterprise | boolean       | Disponible pour les groupes entreprise  |
| sort_order    | int           | Ordre dans sa categorie                 |
| timestamps    | datetime      | created_at, updated_at                  |

### Table `menus`

| Colonne    | Type               | Notes                              |
|------------|--------------------|------------------------------------|
| id         | bigint PK          | Auto-increment                     |
| type       | enum(menu,promo)   | Menu ou promotion                  |
| name       | string nullable    | Nom du menu (null si promo)        |
| price      | int nullable       | Prix du menu (null si promo)       |
| promo_text | string nullable    | Texte de la promo (null si menu)   |
| sort_order | int                | Ordre d'affichage                  |
| timestamps | datetime           | created_at, updated_at             |

### Table pivot `menu_product`

| Colonne      | Type           | Notes                                          |
|--------------|----------------|-------------------------------------------------|
| id           | bigint PK      | Auto-increment                                  |
| menu_id      | bigint FK      | Ref menus.id                                    |
| product_id   | bigint FK      | Ref products.id                                 |
| choice_group | string nullable| Regroupe les produits interchangeables (ex: "fruit") |
| sort_order   | int            | Ordre dans le menu                              |

**Logique `choice_group`** : quand plusieurs produits partagent le meme `choice_group` dans un menu, ils representent un choix. A l'affichage, le groupe est rendu comme une seule ligne ("Fruit au choix" pour choice_group="fruit"). Les produits sans choice_group sont des elements fixes du menu.

Exemple -- Menu Midi (prix: 400) :

| product_id | product_name | choice_group | sort_order |
|------------|-------------|--------------|------------|
| 1          | Sandwich    | null         | 1          |
| 5          | Blue Bull   | null         | 2          |
| 9          | Pomme       | fruit        | 3          |
| 10         | Banane      | fruit        | 3          |
| 11         | Poire       | fruit        | 3          |
| 12         | Orange      | fruit        | 3          |

Rendu : "Sandwich + Blue Bull + Fruit au choix = 400"

### Table `enterprise_groups`

| Colonne    | Type          | Notes                               |
|------------|---------------|--------------------------------------|
| id         | bigint PK     | Auto-increment                       |
| name       | string        | Nom de l'entreprise partenaire       |
| sort_order | int           | Ordre d'affichage                    |
| timestamps | datetime      | created_at, updated_at               |

### Table pivot `enterprise_group_product`

| Colonne              | Type      | Notes                                    |
|----------------------|-----------|------------------------------------------|
| id                   | bigint PK | Auto-increment                           |
| enterprise_group_id  | bigint FK | Ref enterprise_groups.id                 |
| product_id           | bigint FK | Ref products.id                          |
| price                | int       | Prix specifique pour cette entreprise    |
| sort_order           | int       | Ordre dans le groupe                     |

---

## Modeles Eloquent

### Category
- **Relations** : `hasMany(Product::class)`
- **Attributs** : name, column, sort_order
- **Scope** : `scopeLeft()`, `scopeRight()` pour filtrer par colonne

### Product
- **Relations** :
  - `belongsTo(Category::class)`
  - `belongsToMany(Menu::class, 'menu_product')->withPivot('choice_group', 'sort_order')`
  - `belongsToMany(EnterpriseGroup::class, 'enterprise_group_product')->withPivot('price', 'sort_order')`
- **Attributs** : name, price, is_retail, is_enterprise, sort_order
- **Scopes** : `scopeRetail()`, `scopeEnterprise()`

### Menu
- **Relations** : `belongsToMany(Product::class, 'menu_product')->withPivot('choice_group', 'sort_order')`
- **Attributs** : type, name, price, promo_text, sort_order
- **Scopes** : `scopeMenus()` (type=menu), `scopePromos()` (type=promo)
- **Accessor** : `getDisplayItemsAttribute()` -- regroupe par choice_group pour l'affichage

### EnterpriseGroup
- **Relations** : `belongsToMany(Product::class, 'enterprise_group_product')->withPivot('price', 'sort_order')`
- **Attributs** : name, sort_order

---

## Strategie de seeding

Ordre : Categories/Products -> EnterpriseGroups -> Menus -> User admin

### 1. CategoryProductSeeder (depuis produits.json)
- Cree 4 categories : SNACKS (left), BOISSONS (right), COIN FESTIF (left), OBJETS DU QUOTIDIEN (right)
- Cree tous les produits avec `is_retail=true, is_enterprise=false`
- Prix parses depuis les strings JSON (supprime espaces, ex: "1 000" -> 1000)

### 2. EnterpriseSeeder (depuis entreprises.json)
- Cree 6 groupes entreprise : AutoV/Garage Moto, Jupiter, Vigneron, Bahamas/Unicorn/Tequilala, Quikly, Mystere
- Pour chaque produit dans chaque groupe :
  - Cherche un produit existant par nom exact (insensible a la casse)
  - Si trouve : marque `is_enterprise=true`, cree le pivot avec le prix entreprise
  - Si non trouve : cree un nouveau produit dans une categorie "FOURNITURES ENTREPRISE" avec `is_retail=false, is_enterprise=true`, puis cree le pivot

### 3. MenuSeeder (depuis menus.json)
- Cree les menus (type=menu) et promos (type=promo)
- Pour chaque item d'un menu :
  - Cherche le produit par nom exact
  - "Fruit au choix" : lie Pomme, Banane, Poire, Orange avec choice_group="fruit"
  - Les autres items : lie directement le produit (choice_group=null)

### 4. UserSeeder
- Cree un utilisateur admin : email `admin@ltd.test`, password `admin`

---

## Panneau Filament

### Dashboard
- StatsOverviewWidget : produits retail, produits enterprise, menus, groupes enterprise
- LatestProductsWidget : 5 derniers produits modifies

### Resources

**CategoryResource**
- Table : name, column (badge), products_count, sort_order (reorderable)
- Form : name (TextInput), column (Select left/right)
- RelationManager : ProductsRelationManager (table inline)
- Filter : SelectFilter column

**ProductResource**
- Table : name, price (formatted euro), category.name, is_retail (icon), is_enterprise (icon), sort_order
- Form : name, price, category_id (Select), is_retail (Toggle), is_enterprise (Toggle), sort_order
- Filters : SelectFilter category, TernaryFilter is_retail, TernaryFilter is_enterprise
- BulkAction : UpdatePriceAction

**MenuResource**
- Table : type (badge), name, price (formatted), products_count
- Form : type (Select), name (visible si menu), price (visible si menu), promo_text (visible si promo)
- RelationManager : ProductsRelationManager avec champ pivot choice_group
- Filter : SelectFilter type

**EnterpriseGroupResource**
- Table : name, products_count, sort_order (reorderable)
- Form : name
- RelationManager : ProductsRelationManager avec champ pivot price

---

## Pages publiques (Blade)

### Layout
- `resources/views/layouts/app.blade.php` : HTML head, CSS links, body structure
- CSS reutilises depuis l'ancien projet dans `public/css/`
- Images dans `public/img/`

### Routes (web.php)
```
GET /            -> PageController@home     (welcome.blade.php)
GET /produits    -> PageController@produits (produits.blade.php)
GET /menus       -> PageController@menus    (menus.blade.php)
GET /entreprises -> PageController@entreprises (entreprises.blade.php)
```

### Controllers
- `PageController` : un seul controller avec 4 methodes, chacune query la DB et passe les donnees a la vue

### Donnees passees aux vues
- **home** : aucune donnee dynamique (statique)
- **produits** : `$leftCategories`, `$rightCategories` (categories avec produits retail, triees)
- **menus** : `$menus` (avec produits et choice_groups resolus)
- **entreprises** : `$groups` (avec produits et prix pivot)

### Design preserve
- Fond navy (#0d1b2e) + image floue en pseudo-elements
- Panneau bois (#f5f0e6) avec ombres et animations
- Dot leaders entre noms et prix
- Zebra stripes sur les lignes produits
- Logo PNG integre
- Mode clean (?clean) : JS cote client pour masquer la navigation
- Password overlay entreprises : JS cote client (mot de passe : ltd2026)

---

## Structure des dossiers (post-conversion)

```
ltd/
  _backup/                    # Ancien projet sauvegarde
  app/
    Filament/
      Resources/              # CategoryResource, ProductResource, MenuResource, EnterpriseGroupResource
      Widgets/                # StatsOverviewWidget, LatestProductsWidget
    Http/Controllers/
      PageController.php      # Controller pages publiques
    Models/
      Category.php
      Product.php
      Menu.php
      EnterpriseGroup.php
      User.php
  database/
    migrations/               # 6 migrations (categories, products, menus, menu_product, enterprise_groups, enterprise_group_product)
    seeders/
      CategoryProductSeeder.php
      EnterpriseSeeder.php
      MenuSeeder.php
      UserSeeder.php
      DatabaseSeeder.php
  docs/
    architecture.md           # Ce fichier
    reglement-bxl-life/       # Documentation du reglement BXL Life
    tasks/                    # Structure pour les futures taches
  memory-bank/                # Documentation projet (Memory Bank)
  public/
    css/                      # CSS du design original
    img/                      # Images (logo, station)
  resources/views/
    layouts/app.blade.php
    welcome.blade.php
    produits.blade.php
    menus.blade.php
    entreprises.blade.php
  routes/web.php
  .env
  .cursorignore               # Contient !.env
```

---

## Conventions

- Prix en entiers (pas de centimes, pas de float)
- Symbole euro affiche cote front uniquement
- Noms de categories en majuscules
- Couleurs : navy (#0d1b2e, #0d2347), rouge (#8b0000, #c0392b), creme (#f5f0e6)
- Sort_order pour tout ce qui a un ordre d'affichage
- Panneau admin accessible via `/admin`
