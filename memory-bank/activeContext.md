# Active Context - Station LTD / Toolbox Lost MC

## Travail en cours
Extension continue de l'espace membre MC. Phases 0, 1 et 2 terminees (roles hierarchises, refonte UX, ventes rapides).
Session du soir tardif du 16 avril 2026 : livraison du module **ventes rapides** (Phase 2), ajout d'une **Phase H
d'harmonisation** dans le plan (pour traiter les doublons weapon_sales vs sales et preparer la fusion stocks),
et documentation d'un catalogue d'items observes in-game (309 kg / 1000 kg de coffre MC).

## Changements recents (session du 16 avril 2026 -- soir tardif, Phase 2)

### Module ventes rapides (Phase 2) -- LIVRE
- Nouvelle table `sales` generique : `item_type` (weapon/ammo/drug/melee/other), `item_id` nullable,
  `item_name`, `quantity`, `unit_price`, `total_price`, `buyer_name`, `sold_by_user_id`,
  `validated_by_user_id` nullable, `validated_at`, `notes`, index sur created_at/item_type/item_id.
- Modele `App\Models\Sale` avec constante `TYPES`, scopes `ofType()` et `today()`, relations
  `soldBy`, `validatedBy`, `weapon`.
- Controleur `App\Http\Controllers\SaleController` :
  - `index()` rend la vue `/ventes` avec armes actives + membres.
  - `apiList()` parametrable `scope=mine|all` + `period=today|week|month|all`, renvoie ventes
    + totaux (count, quantity, revenue).
  - `apiCreate()` : valide, pour `item_type=weapon` decremente le stock arme et cree un
    `weapon_stock_movement` (reason=sale), pour les autres types utilise `item_name` libre.
  - Tous les endpoints proteges par `X-Sim-User` + `canAccessPage('ventes_rapides')`.
- Routes : `GET /ventes` (nom `ventes`), `GET /ventes/api/list`, `POST /ventes/api/create`.
- Vue `resources/views/ventes.blade.php` avec deux sous-onglets (Nouvelle vente / Historique).
- JS `public/js/ventes.js` :
  - Tom Select sur le choix d'arme, pre-remplissage du total a partir de `sell_price`.
  - Calcul auto du prix unitaire, stats du jour (ventes, articles, chiffre).
  - Historique filtrable avec stats dynamiques.
- CSS : nouveaux blocs dans `mc-layout.css` (`.member-row`, `.members-stat`, `.sale-total`,
  badges type vente colores).
- Hub `/mc` : nouveau bouton "Ventes rapides" (wide) visible une fois connecte.
- Nav layout : lien "Ventes" (gate=logged) ajoute entre Munitions et Espace.
- Regle d'acces `ventes_rapides` deja seedee (min_role=`member`).

### Phase H (harmonisation) ajoutee au plan
- Deux chemins ecrivent encore pour les ventes d'armes : `/espace-membres` -> `weapon_sales`,
  `/ventes` -> `sales`. Deduplication a traiter avant Phase 3.
- Memo Phase H detaille dans `LA_SUIITE/plan-developpement.md` (audit, migration donnees,
  unification UI, regle de fer pour les phases suivantes).
- Annexe A ajoutee au plan : inventaire observe dans le coffre MC (309 kg/1000 kg),
  repartition en 12 categories pour orienter la taxonomie de `stock_items` en Phase 3.

## Changements (session du 16 avril 2026 -- soir)

### Hierarchie des roles retravaillee
- Ajout du role `vice_president` (niveau 4) entre officer et president.
- `treasurer` devient **superadmin** (niveau 99) : il peut assigner n'importe quel role, y compris un autre tresorier.
- Nouveaux helpers sur `User` : `isVicePresident()`, `isSuperadmin()`, `canAssignRole($role)`, `assignableRoles()`.
- `isPresident()` renvoie aussi true pour un superadmin.
- Champ `is_active` ajoute sur `users` (default true).
- Constante `User::SUPERADMIN_ROLE = 'treasurer'`.

### Matrice d'acces BDD
- Nouvelle table `page_access_rules` (`page_key`, `label`, `min_role`, `description`, `sort_order`, `is_system`).
- Modele `App\Models\PageAccessRule` avec cache (10 min) et helper `userCanAccess(User $u, string $key)`.
- Seeder `PageAccessRuleSeeder` avec 13 regles initiales (panel_admin, panel_armurerie, mc_hub, simulateur_armes, simulateur_munitions, espace_membres, membres_gestion, matrice_acces, ventes_rapides, stocks_generique, comptabilite, classements, fiches_membres).
- `User::canAccessPanel(Panel)` delegue desormais a `PageAccessRule` -- acces Filament editable en BDD.
- `User::canAccessPage(string $key)` pour les pages custom (hors Filament).

### Page `/membres` (gestion complete)
- Nouvelle route `/membres` servie par `App\Http\Controllers\MemberController`.
- API JSON : `GET /membres/api/list`, `POST /membres/api/create`, `PUT /membres/api/{id}`, `POST /membres/api/{id}/reset-pin`, `DELETE /membres/api/{id}`, `GET /membres/api/matrix`, `PUT /membres/api/matrix/{id}`.
- Acces controle via `membres_gestion` (VP+ par defaut) et `matrice_acces` (treasurer par defaut).
- Vue `membres.blade.php` + JS `public/js/membres.js` :
  - Liste filtrable (recherche + filtre role) avec stats par role.
  - Changement de role inline (selects Tom Select).
  - Reinitialisation de PIN (modale affichant le nouveau PIN genere aleatoirement).
  - Activation / desactivation (is_active).
  - Suppression reservee au superadmin.
  - Onglet "Matrice d'acces" visible seulement pour le superadmin, edition inline du `min_role` par page.
- La suppression du dernier superadmin est bloquee cote serveur.

### Selects recherchables (Tom Select)
- Librairie Tom Select 2.3.1 integree via CDN sur le layout `layouts/mc.blade.php`.
- Theme dark MC dans `public/css/mc-tom-select.css` (role-badges, stock quantity hints, highlight des matches).
- Tom Select applique automatiquement sur :
  - `#loginMemberSelect` (login en haut a droite)
  - `#saleWeapon`, `#mvStock`, `#mvReason`, `.ct-weapon`, `#newMemberRole` (espace membre)
  - `.member-role-sel`, `.matrix-role-sel`, `#memberRoleFilter`, `#gmNewRole` (page `/membres`)
- Les selects stock affichent la quantite restante a droite (`ts-stock-qty` avec classe `.low` / `.zero`).
- Les selects de role affichent un badge colore par role.

### Bugfix : selects vides sur `/espace-membres`
- `showDashboard()` dans `public/js/simulateur-armes.js` testait `$('tab-membres')` qui n'existait plus depuis la refonte UX. Le guard retournait prematurement, bloquant `loadDashboardData()` et `populateForms()`.
- Corrige : le guard teste maintenant `$('memberDashboard')` (element reellement present). Ajout de checks defensifs sur chaque sous-element.
- Selects correctement remplis au login et apres chaque rafraichissement de donnees.

### Autres ajustements
- Phrase "Le Tout-Puissant pardonne. Pas les Lost." affichee sous le titre de chaque page MC (simulateur armes, simulateur munitions, espace membres, membres). Classe CSS `.mc-page-motto` dans `mc-layout.css`.
- `apiData` expose `assignable_roles` et `can_manage_members` pour que le front adapte le formulaire d'ajout de membre.
- `WeaponSimController::createMember` / `updateMember` utilisent maintenant `canAssignRole()` et `canAccessPage('membres_gestion')` au lieu d'un simple `isOfficer()`.
- `mc-auth.js` : helpers `isAtLeast`, `isVicePresident`, `isSuperadmin`, nouveau `apiDelete()`, et `apiPut()` utilise la methode HTTP native (plus de `X-HTTP-Method-Override`).
- Hub MC : nouveaux boutons "Gestion membres" (VP+), "Panel Armurerie" (officer+), "Panel Admin LTD" (superadmin).

## Changements (sessions avril 2026 -- avant aujourd'hui)
- Phase 0 : `settings` + `SettingResource` + 19 parametres seedes, constante `User::ROLES` + validation dynamique.
- Phase 1 : refonte UX complete en pages dediees (`/mc`, `/simulateur-armes`, `/simulateur-munitions`, `/espace-membres`), JS scinde en 3 fichiers, login en dropdown global.
- Domaine armurerie (6 tables, 6 modeles, panel Filament dedie, seeder).

## Prochaines etapes
1. **Phase H - harmonisation (prioritaire)** : migrer `weapon_sales` vers `sales`, rediriger le
   formulaire de vente d'`/espace-membres` vers `/ventes`, preparer la fusion `weapon_stocks` ->
   `stock_items`. A faire AVANT de creer d'autres modules qui risquent de doubler.
2. **Phase 3 - stocks generiques** : `stock_items` + `stock_movements` generiques, taxonomie
   riche (weapon_finished, piece, raw_material, ammo, plan, melee, drug, drug_raw,
   farm_consumable, tool, electronic, misc), attribution officier -> membre (sous-phase 3.4).
3. **Phase 4 - drogues** : alimentation via `stock_items` (categories `drug` / `drug_raw` /
   `farm_consumable`) + flux achat orga / attribution / reconciliation.
4. **Phase 5 - armes blanches** : extension `stock_items` (categorie `melee`).
5. **Phase 6 - classements + fiches membres** : leaderboard global/mois/semaine, fiche detaillee.
6. **Phase 7 - comptabilite MC** : argent sale/propre, transactions, cotisations.
7. **Phase 8 - polissage** : responsive, notifications, dashboards par role.

## Decisions actives
- Laravel 12 + Filament 5.3, deux panneaux Filament (admin / armurerie).
- Roles via champ `role` sur User (constante `User::ROLES`), pas de Spatie Permissions.
- Hierarchie : prospect (1) < member (2) < officer (3) < vice_president (4) < president (5) < treasurer (99 = superadmin).
- Acces aux pages pilote par la table `page_access_rules` (editable en front par le superadmin).
- Auth MC via PIN (header `X-Sim-User`).
- Selects front : Tom Select 2.3.1, theme dark partage.
- Motto "Le Tout-Puissant pardonne. Pas les Lost." sur toutes les pages MC.
- Serveur de test : `php artisan serve --port=8080`.

## Problemes connus
- Le vhost Laragon pointe encore sur la racine du projet (pas sur `public/`).
- Les vues compilees Blade s'accumulent (a purger : `php artisan view:clear`).
- **Doublon vente d'arme** : `/espace-membres` continue d'ecrire dans `weapon_sales`, `/ventes`
  ecrit dans `sales`. Cible de la Phase H.
- **Doublon stock armurerie potentiel** : la future Phase 3 introduira `stock_items` en parallele
  de `weapon_stocks`. A prevoir explicitement en Phase H.
- Certaines phases (stocks generiques, drogues, armes blanches, classements, comptabilite)
  non implementees encore.
