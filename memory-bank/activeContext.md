# Active Context - Station LTD (Laravel + Filament)

## Travail en cours
Conversion terminee. Projet fonctionnel en Laravel 12 + Filament 5.

## Changements recents (session du 4 mars 2026)

### Conversion complete du projet
- Projet statique HTML/CSS/JS converti en Laravel 12 + Filament 5.3
- Base de donnees MySQL `ltd` avec 6 tables (categories, products, menus, menu_product, enterprise_groups, enterprise_group_product)
- 62 produits, 5 categories, 4 menus, 6 groupes entreprise, 1 admin seeds
- Panneau Filament avec 4 resources (Categories, Produits, Menus, Entreprises) + dashboard avec widgets
- Pages Blade publiques reproduisant fidelement le design original
- Documentation du reglement BXL Life (7 fichiers .md)
- Structure pour les futures taches (docs/tasks/)

### Architecture de donnees
- Product comme source unique (flags is_retail/is_enterprise)
- Pivot menu_product avec choice_group pour les choix multiples
- Pivot enterprise_group_product avec prix specifique par entreprise

## Prochaines etapes possibles
- Tester le site public et le panneau Filament dans un navigateur
- Ajuster les styles CSS si necessaire apres tests visuels
- Commencer a travailler sur les taches futures (lotto, estimation stock, strategies)
- Ajouter des fonctionnalites au panneau Filament (export, import, stats avancees)

## Decisions actives
- Laravel 12 + Filament 5.3 (versions plus recentes que prevu initialement)
- CSS de l'ancien projet reutilises sans modification dans public/css/
- Password overlay entreprises conserve cote client (JS)
- Admin : admin@ltd.test / admin
- Mode clean (?clean) conserve via JS cote client
