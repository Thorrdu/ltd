# Progress - Station LTD / Toolbox Lost MC

## Statut actuel
Projet Laravel 12 + Filament 5 fonctionnel avec deux domaines operationnels : catalogue LTD et armurerie.
Toolbox MC en construction : Phases 0 (roles) et 1 (refonte UX) terminees. Session du 16 avril (soir) :
ajout de la gestion des utilisateurs en front, matrice d'acces editable en BDD, Tom Select, bugfix selects.

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

### Simulateur d'armes et espace membres
- [x] Pages dediees : `/mc` (hub), `/simulateur-armes`, `/simulateur-munitions`, `/espace-membres`
- [x] Calcul de prix de revient et prix de vente (recettes, multiplicateurs DB)
- [x] Espace membres : login PIN, dashboard, ventes, contrats, mouvements, historique
- [x] API JSON (WeaponSimController) avec headers `X-Sim-User` et CSRF
- [x] Selects recherchables (Tom Select 2.3.1) avec theme dark et badges/quantites

### Gestion des utilisateurs (nouveau -- 16 avril soir)
- [x] Page `/membres` dediee : table avec filtres, stats par role, actions (role / PIN / actif / supprimer)
- [x] Controleur `MemberController` + 7 endpoints API protegees
- [x] Hierarchie stricte : un utilisateur ne peut assigner que des roles strictement inferieurs au sien (sauf superadmin)
- [x] Superadmin = treasurer : peut assigner tout role, y compris tresorier
- [x] Reset PIN avec affichage modal du nouveau PIN (random 4-6 chiffres)
- [x] Activation / desactivation (champ `is_active`)
- [x] Suppression reservee au superadmin, blocage auto-suppression et demotion du dernier superadmin

### Matrice d'acces editable (nouveau -- 16 avril soir)
- [x] Table `page_access_rules` (13 regles seedees)
- [x] `User::canAccessPanel()` et `User::canAccessPage()` piloteS par la DB
- [x] Edition inline de la matrice reservee au superadmin (onglet "Matrice d'acces" sur `/membres`)
- [x] Cache de 10 minutes sur les regles (invalidation auto sur save/delete)

### Infrastructure
- [x] 15 migrations appliquees (fresh-seed : 0 erreur)
- [x] 7 seeders : CategoryProduct, Enterprise, Menu, User, Weapon, Setting, PageAccessRule
- [x] Systeme de roles avec hierarchie numerique (prospect 1, member 2, officer 3, vice_president 4, president 5, treasurer 99)
- [x] Auth PIN pour simulateur via header `X-Sim-User`
- [x] Documentation (architecture, reglement BXL Life, Memory Bank, plan-developpement)

## Ce qui reste a faire

### Priorite haute
- [ ] Phase 2 : module ventes rapides (`/ventes`, table `sales` generique)
- [ ] Phase 3 : stocks generiques (`stock_items`, `stock_movements`) avec attribution officier -> membre (3.4)
- [ ] Aligner le vhost Laragon sur `public/`

### Priorite moyenne
- [ ] Phase 4 : module drogues (referentiel + flux achat orga -> attribution -> reconciliation)
- [ ] Phase 5 : armes blanches (ajout au stock generique)
- [ ] Phase 6 : classements + fiches membres detaillees
- [ ] Phase 7 : comptabilite MC (argent sale/propre, transactions, cotisations)

### Priorite basse / Future
- [ ] Phase 8 : polissage (responsive fin, notifications in-app, dashboards par role)
- [ ] Import stock via CSV/Excel (Phase 3.5)

## Historique
- **16 avril 2026 (soir)** : Gestion utilisateurs `/membres` + matrice d'acces BDD + Tom Select + bugfix selects vides + role `vice_president` + treasurer=superadmin.
- **16 avril 2026 (apres-midi)** : Phase 1 refonte UX (pages dediees, login dropdown, JS scinde). Phase 0 terminee (settings, roles helpers, SettingResource, 19 parametres).
- **14-16 avril 2026** : Domaine armurerie complet (6 tables, 6 modeles, panneau Filament, simulateur, API).
- **28 mars 2026** : Ajout promo_price sur products et menus.
- **11 mars 2026** : Ajout enterprise_price sur products.
- **8 mars 2026** : Ajout purchase_price/usual_price/notes, fix namespace Filament v5.
- **4 mars 2026** : Conversion complete vers Laravel 12 + Filament 5. Documentation.
- **2 mars 2026** : Derniere version du site statique original.

## Chiffres cles
- 62 produits catalogue en base
- 13 modeles Eloquent (ajout de `Setting`, `PageAccessRule`)
- 15 migrations appliquees
- 7 seeders (ajout `SettingSeeder`, `PageAccessRuleSeeder`)
- 2 panneaux Filament (admin + armurerie)
- 1 page front dediee gestion (`/membres`)
- 6 roles dans la hierarchie (prospect -> treasurer)
- 13 regles d'acces seedees dans `page_access_rules`
- 19 parametres dans `settings`
