# Tech Context - Station LTD (Laravel + Filament)

## Technologies
- **Laravel 11.x** : framework PHP backend
- **Filament 3.x** : panneau d'administration
- **MySQL 8.0.30** : base de donnees (root, sans mot de passe, base `ltd`)
- **Vite** : build des assets front (via laravel-vite-plugin)
- **Node.js 22.14.0** : runtime pour le build front
- **PHP 8.3.21** : runtime backend (Laragon)
- **Composer** : gestionnaire de dependances PHP (mis a jour depuis 2.4.1)
- **Blade** : moteur de templates pour les pages publiques
- **CSS custom** : fichiers originaux reutilises (common.css, accueil.css, produits.css, menus.css, entreprises.css)

## Environnement de developpement
- Workspace : `c:\laragon\www\ltd`
- Serveur : Laragon (Apache sur Windows)
- URL locale : `http://ltd.test/`
- URL admin : `http://ltd.test/admin`
- Base de donnees : MySQL `ltd` (root, sans mot de passe)

## Configuration
- `.env` : configuration Laravel (DB, APP_KEY, etc.)
- `.cursorignore` : contient `!.env` pour que Cursor puisse lire le fichier
- `_backup/` : ancien projet statique sauvegarde

## Structure des fichiers
```
ltd/
  _backup/                    # Ancien projet HTML/CSS/JS sauvegarde
    index.html, produits.html, menus.html, entreprises.html
    css/, js/, img/, data/
  app/
    Filament/
      Resources/              # 4 resources + relation managers
      Widgets/                # Dashboard widgets
    Http/Controllers/
      PageController.php      # 4 methodes pour les pages publiques
    Models/
      Category.php            # hasMany Product
      Product.php             # belongsTo Category, belongsToMany Menu/EnterpriseGroup
      Menu.php                # belongsToMany Product (pivot choice_group)
      EnterpriseGroup.php     # belongsToMany Product (pivot price)
      User.php                # Authentification Filament
  database/
    migrations/               # 6 fichiers de migration
    seeders/                  # 4 seeders + DatabaseSeeder
  docs/
    architecture.md           # Document de reference complet
    reglement-bxl-life/       # 7 fichiers .md du reglement BXL Life
    tasks/                    # Template + exemples de taches futures
  memory-bank/                # Memory Bank (6 fichiers .md)
  public/
    css/                      # CSS design original (5 fichiers)
    img/                      # logo-ltd.png, station-jour.png, station-nuit.png
  resources/views/
    layouts/app.blade.php     # Layout commun
    welcome.blade.php         # Page accueil
    produits.blade.php        # Page produits
    menus.blade.php           # Page menus
    entreprises.blade.php     # Page entreprises
  routes/web.php              # 4 routes publiques
  .env                        # Configuration Laravel
  .cursorignore               # !.env
```

## Modeles Eloquent
- `Category` : name, column (left/right), sort_order -- hasMany Product
- `Product` : name, price (int), is_retail, is_enterprise, sort_order -- belongsTo Category
- `Menu` : type (menu/promo), name, price, promo_text, sort_order -- belongsToMany Product
- `EnterpriseGroup` : name, sort_order -- belongsToMany Product (pivot price)
- `User` : standard Laravel + Filament HasRoles

## Base de donnees
- 4 tables principales : categories, products, menus, enterprise_groups
- 2 tables pivot : menu_product (choice_group, sort_order), enterprise_group_product (price, sort_order)
- 1 table users (authentification admin)

## URLs
- `GET /` : accueil
- `GET /produits` : produits retail
- `GET /menus` : menus et promos
- `GET /entreprises` : tarifs entreprises
- `GET /admin` : panneau Filament

## Credentials
- Admin Filament : admin@ltd.test / admin
- MySQL : root / (sans mot de passe)
- Mot de passe page entreprises (cote client) : ltd2026
