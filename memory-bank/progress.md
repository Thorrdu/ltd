# Progress - Station LTD / Toolbox Lost MC

## Statut actuel
Projet Laravel 12 + Filament 5 fonctionnel avec deux domaines operationnels : catalogue LTD et armurerie.
Phase de planification pour l'extension vers la gestion complete du MC.

## Ce qui fonctionne

### Catalogue LTD
- [x] Site public : 4 pages Blade (accueil, produits, menus, entreprises)
- [x] Design panneau bois preserve, dot leaders, zebra stripes, mode clean
- [x] Panneau Filament `/admin` : 4 resources, 3 relation managers, 2 widgets
- [x] 62 produits, 5 categories, 4 menus, 6 entreprises en base
- [x] Prix d'achat, prix habituel, prix promo, prix entreprise sur produits
- [x] Middleware AllowIframe pour embedding

### Armurerie
- [x] Panneau Filament `/armurerie` : 5 resources, page CraftWeapon, widget stats
- [x] Modeles complets : Weapon, WeaponStock, WeaponStockMovement, WeaponContract, WeaponContractItem, WeaponSale
- [x] Stocks par categorie (matieres, pieces, plans, armes finies)
- [x] Mouvements de stock avec tracabilite (utilisateur, raison, cout)
- [x] Contrats clients avec items commandes/livres
- [x] Ventes avec suivi par vendeur

### Simulateur d'armes
- [x] Page Blade `/simulateur-armes` avec onglets
- [x] Calcul de prix de revient et prix de vente
- [x] Espace membres (login PIN, ventes, stats)
- [x] API JSON (WeaponSimController)
- [x] Craft munitions

### Infrastructure
- [x] 21 migrations, 11 modeles, 6 seeders
- [x] Systeme de roles (champ `role` sur User)
- [x] Auth PIN pour simulateur
- [x] Documentation (architecture, reglement BXL Life, Memory Bank)

## Ce qui reste a faire (base sur next.md)

### Priorite haute
- [ ] Corriger `$fillable` du modele Weapon (price_min, price_max)
- [ ] Implementer le filtrage `canAccessPanel()` par role
- [ ] Separer simulateurs armes et munitions en 2 pages distinctes
- [ ] Deplacer le login espace membre en haut a droite (toujours visible)
- [ ] Rendre l'app mobile-friendly (menu principal grands boutons)

### Priorite moyenne
- [ ] Module ventes rapides pour les membres
- [ ] Classements de productivite (global, mois, semaine)
- [ ] Module stocks generique avec entrees/sorties/attributions
- [ ] Historique complet des mouvements de stock
- [ ] Page configuration/parametres (prix, recettes, tout en DB)

### Priorite basse / Future
- [ ] Module drogues (achat orga, distribution, revente, pertes)
- [ ] Armes blanches (switchblade, knife, machete, batte, etc.)
- [ ] Fiches membres detaillees
- [ ] Comptabilite MC (argent sale/propre, remboursements, amendes)
- [ ] Cotisations par role (prospect 2k, membre 5k, officier 10k)
- [ ] Import stock via CSV/Excel
- [ ] Corriger le vhost Laragon (ltd.test -> public/)

## Historique
- **16 avril 2026** : Mise a jour complete du Memory Bank. Plan de developpement en preparation.
- **14-16 avril 2026** : Domaine armurerie complet (6 tables, 6 modeles, panneau Filament, simulateur, API).
- **28 mars 2026** : Ajout promo_price sur products et menus.
- **11 mars 2026** : Ajout enterprise_price sur products.
- **8 mars 2026** : Ajout purchase_price/usual_price/notes, fix namespace Filament v5.
- **4 mars 2026** : Conversion complete vers Laravel 12 + Filament 5. Documentation.
- **2 mars 2026** : Derniere version du site statique original.

## Chiffres cles
- 62 produits catalogue en base
- 11 modeles Eloquent
- 21 migrations
- 6 seeders
- 2 panneaux Filament (admin + armurerie)
- 7 fichiers documentation reglement BXL Life
- ~1446 lignes JS simulateur
