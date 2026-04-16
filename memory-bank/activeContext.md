# Active Context - Station LTD / Toolbox Lost MC

## Travail en cours
Projet en phase de transition : le catalogue LTD et le domaine armurerie sont fonctionnels.
Un plan de developpement majeur est en preparation pour etendre l'application vers la gestion complete du MC.

## Changements recents (session du 16 avril 2026)

### Mise a jour complete du Memory Bank
- Tous les fichiers du Memory Bank realignes sur l'etat reel du projet
- Documentation de l'ensemble du domaine armurerie (absent du Memory Bank precedent)
- Correction de la terminologie : EnterpriseGroup -> Enterprise

### Plan de developpement en preparation
- Analyse du fichier `LA_SUIITE/next.md` definissant la feuille de route
- Plan de developpement structure en phases a creer

## Changements (sessions avril 2026)

### Domaine Armurerie complet
- 6 nouvelles migrations (weapons, weapon_stocks, weapon_stock_movements, weapon_contracts, weapon_contract_items, weapon_sales)
- 6 nouveaux modeles Eloquent (Weapon, WeaponStock, WeaponStockMovement, WeaponContract, WeaponContractItem, WeaponSale)
- Deuxieme panneau Filament `/armurerie` (ArmureriePanelProvider, dark mode)
- 5 resources Filament armurerie + page CraftWeapon + widget stats
- WeaponSeeder avec armes et stocks initiaux

### Simulateur d'armes
- Page Blade `/simulateur-armes` avec onglets (simulateur / espace membres)
- JS monolithique (simulateur-armes.js, ~1446 lignes)
- CSS dedie (simulateur-armes.css)
- WeaponSimController (API JSON, auth PIN)

### Systeme utilisateurs
- Champ `role` ajoute sur users (prospect, membre, officier, etc.)
- Champ `sim_pin` pour auth simulateur
- Methode `isOfficer()` sur User
- UserSeeder avec roles et PINs

### Evolutions catalogue
- Ajout `promo_price` sur products et menus (28 mars)
- Ajout `enterprise_price` sur products (11 mars)
- Middleware AllowIframe pour embedding

## Changements anterieurs (sessions mars 2026)
- Conversion complete du projet statique en Laravel 12 + Filament 5.3
- 62 produits, 5 categories, 4 menus, 6 entreprises en base
- Ajout purchase_price, usual_price, notes entreprise
- Fix namespace Filament v5

## Prochaines etapes (basees sur next.md)
1. **Separation des simulateurs** : armes et munitions en pages distinctes
2. **Systeme de roles et permissions** : droits granulaires par page/fonctionnalite
3. **Login deplace** : espace membre en haut a droite (toujours visible)
4. **Mobile-friendly** : menu principal avec grands boutons
5. **Module ventes rapides** : membres enregistrent leurs ventes facilement
6. **Module drogues** : gestion complete (achat orga, distribution, revente, pertes)
7. **Armes blanches** : ajout au stock et systeme de vente
8. **Module stocks generique** : stock global avec entrees/sorties/attributions
9. **Classements** : productivite des membres (global, mois, semaine, aigle de la semaine)
10. **Fiches membres** : historique complet par personne
11. **Comptabilite MC** : argent sale/propre, remboursements
12. **Cotisations** : gestion par role (prospect/membre/officier)
13. **Import stock** : via CSV/Excel
14. **Configuration** : page parametres pour tous les prix et recettes (DB)

## Decisions actives
- Laravel 12 + Filament 5.3 (actions sous Filament\Actions)
- Deux panneaux Filament : admin (catalogue) + armurerie
- Roles simples via champ `role` sur User (pas de Spatie Permissions pour l'instant)
- Auth simulateur via PIN (header X-Sim-User)
- CSS de l'ancien projet reutilises pour le catalogue
- Admin : admin@ltd.test / admin
- Serveur de test : php artisan serve --port=8080

## Problemes connus
- `price_min`/`price_max` pas dans `$fillable` du modele Weapon
- `canAccessPanel()` retourne `true` pour tous (pas de filtrage par role)
- Vhost Laragon ne pointe pas vers public/
