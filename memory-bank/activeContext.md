# Active Context - Station LTD (Laravel + Filament)

## Travail en cours
Projet fonctionnel en Laravel 12 + Filament 5. Structure DB corrigee et testee.

## Changements recents (session du 8 mars 2026)

### Corrections de la structure DB
- Ajout de `purchase_price` (prix d'achat) et `usual_price` (prix habituel) sur la table `products`
- Ajout de `notes` (conditions du contrat) sur la table `enterprise_groups`
- Mise a jour du modele Product (fillable, casts, accesseurs formates)
- Mise a jour du modele EnterpriseGroup (fillable notes)
- Seeders enrichis avec les prix d'achat et habituels extraits du tableur de reference
- Correction du MenuSeeder : alias "Gauffre de BXL" -> "Gaufre de BXL" pour le matching

### Corrections Filament v5
- Migration de `Tables\Actions\*` vers `Filament\Actions\*` (changement de namespace Filament v5)
- Ajout des champs purchase_price et usual_price dans les formulaires et tables Filament
- Ajout du champ notes dans le formulaire EnterpriseGroupResource

### Tests valides
- Page d'accueil : fonctionne, design identique au backup
- Page produits : 4 categories, 62 produits, dot leaders, panneau bois
- Page menus : 3 menus + 1 promo, items correctement lies aux produits
- Page entreprises : overlay mot de passe, 6 groupes, prix corrects
- Panneau admin : dashboard, CRUD produits, categories, menus, groupes entreprise

## Changements anterieurs (session du 4 mars 2026)

### Conversion complete du projet
- Projet statique HTML/CSS/JS converti en Laravel 12 + Filament 5.3
- Base de donnees MySQL `ltd` avec 6 tables + 2 pivots
- 62 produits, 5 categories, 4 menus, 6 groupes entreprise, 1 admin seeds
- Pages Blade publiques reproduisant fidelement le design original

## Prochaines etapes possibles
- Corriger le vhost Laragon pour pointer vers public/ (actuellement ltd.test montre l'index)
- Ajuster les prix dans le tableur vs JSON si necessaire (Tablette: 1750 vs 1500, Portefeuille: 100 vs 160)
- Commencer a travailler sur les taches futures (lotto, estimation stock, strategies)
- Ajouter des fonctionnalites au panneau Filament (export, import, stats avancees)

## Decisions actives
- Laravel 12 + Filament 5.3 (actions sous Filament\Actions, pas Tables\Actions)
- Prix d'achat et prix habituel sont optionnels (nullable)
- Notes entreprise optionnelles (textarea pour conditions de contrat)
- CSS de l'ancien projet reutilises sans modification dans public/css/
- Password overlay entreprises conserve cote client (JS)
- Admin : admin@ltd.test / admin
- Mode clean (?clean) conserve via JS cote client
- Serveur de test : php artisan serve --port=8080 (vhost ltd.test ne pointe pas vers public/)
