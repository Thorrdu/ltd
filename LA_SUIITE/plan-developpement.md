# Plan de developpement - Toolbox Lost MC

> Document de reference pour le developpement des fonctionnalites futures.
> Base sur `LA_SUIITE/next.md` et l'etat actuel du projet au 16 avril 2026.
> Objectif global : faciliter la gestion quotidienne et comptable du MC.

---

## Phase 0 - Corrections techniques et prerequis [TERMINEE]
**Terminee le 16 avril 2026**

### 0.1 Corrections modeles existants
- [x] Ajouter `price_min`, `price_max` dans `$fillable` du modele `Weapon`
- [x] Verifier la coherence de tous les `$fillable` / `$casts` des modeles armurerie
- [x] Tester le seeder complet (`php artisan migrate:fresh --seed`) -- 22 migrations, 6 seeders, 0 erreur

### 0.2 Systeme de roles et permissions
- [x] Definir les roles : `prospect` (1), `member` (2), `officer` (3), `treasurer` (4), `president` (5)
- [x] Constante `ROLES` dans User avec hierarchie par niveau
- [x] `canAccessPanel()` : admin = treasurer+, armurerie = officer+
- [x] Methodes : `isProspect()`, `isMember()`, `isOfficer()`, `isTreasurer()`, `isPresident()`, `isAtLeast($role)`
- [x] WeaponSimController migre vers `isOfficer()` (hierarchique, inclut treasurer/president)
- [x] Validation roles dynamique dans creation/edition membre (tous les roles de `ROLES`)
- [x] UserSeeder : 1 president, 1 treasurer, 2 officers, 4 members, 2 prospects

### 0.3 Page de configuration / parametres (DB-driven)
- [x] Table `settings` (group, key, label, type, value, description, sort_order)
- [x] Modele `Setting` avec cache, helpers statiques `get()`, `set()`, `getGroup()`, `clearCache()`
- [x] SettingResource Filament avec edition inline, groupement, filtres -- acces treasurer+
- [x] SettingSeeder : 19 parametres (matieres premieres, pieces, recettes munitions, multiplicateurs, cotisations)

---

## Phase 1 - Reorganisation de l'interface [TERMINEE]
**Terminee le 16 avril 2026 -- refonte UX complete**

### Principe : une page = une fonction, zero onglet imbrique

**Architecture des pages :**
- `/mc` -- Hub d'accueil avec gros boutons clairs (publics + membres)
- `/simulateur-armes` -- Simulateur armes uniquement (selection, pieces, couts, craft)
- `/simulateur-munitions` -- Simulateur munitions uniquement (tableau, objectif)
- `/espace-membres` -- Dashboard membre (stocks, ventes, contrats, historique, gestion)

**Barre superieure minimale :** LOST MC (lien home) + bouton connexion / nom+role+quitter

### 1.1 Separation des simulateurs
- [x] JS monolithique (1446 lignes) scinde en 3 fichiers :
  - `mc-auth.js` : module auth partage (`window.McAuth`), callbacks login/logout
  - `simulateur-armes.js` : simulateur armes + logique dashboard membre
  - `simulateur-munitions.js` : simulateur munitions autonome
- [x] `/simulateur-armes` ne contient QUE le simulateur (plus de tabs, plus d'espace membre)
- [x] `/simulateur-munitions` page dediee avec JS autonome

### 1.2 Deplacer le login
- [x] Login retire des onglets, deplace dans dropdown en haut a droite
- [x] Bouton "Se connecter" dans la barre superieure sur toutes les pages MC
- [x] Panel dropdown avec formulaire PIN
- [x] Nom + role (avec couleur par hierarchie) + bouton quitter une fois connecte
- [x] Session via sessionStorage persistante entre les pages

### 1.3 Hub mobile-friendly + pages dediees
- [x] `/mc` hub : 2 sections (Simulateurs / Espace membres)
- [x] Section Simulateurs toujours visible (Armes, Munitions)
- [x] Section Espace membres visible si connecte (bouton vers `/espace-membres` + Admin)
- [x] Message "Connectez-vous" si non connecte
- [x] Grille responsive (2 colonnes desktop, 1 colonne mobile)
- [x] Chaque page a un lien "Retour a l'accueil" sous le header
- [x] `/espace-membres` page dediee avec sous-onglets (Stocks, Ventes, Contrats, Historique, Gestion)

### Fichiers
- `resources/views/layouts/mc.blade.php` -- layout partage (barre + auth + toast)
- `public/css/mc-layout.css` -- styles barre, hub, pages, responsive
- `public/js/mc-auth.js` -- module auth partage
- `public/js/simulateur-armes.js` -- simulateur armes + dashboard
- `public/js/simulateur-munitions.js` -- simulateur munitions
- `resources/views/mc-hub.blade.php` -- page hub
- `resources/views/simulateur-armes.blade.php` -- simulateur pur
- `resources/views/simulateur-munitions.blade.php` -- simulateur pur
- `resources/views/espace-membres.blade.php` -- dashboard membres
- `routes/web.php` -- `/mc`, `/simulateur-armes`, `/simulateur-munitions`, `/espace-membres`

---

## Phase 2 - Module de ventes rapides [TERMINEE]
**Terminee le 16 avril 2026 (soir tardif) -- besoin quotidien des membres**

### 2.1 Page de saisie rapide des ventes
- [x] Page `/ventes` accessible aux membres connectes (regle `ventes_rapides`, min_role `member`)
- [x] Formulaire simplifie : selection type (arme/munition/drogue/arme_blanche/autre)
- [x] Pour type=weapon : select Tom Select recherchable sur les armes actives, prix auto-rempli
- [x] Pour les autres types : champ libre `item_name`
- [x] Calcul automatique du prix unitaire (total / quantite)
- [x] Champ acheteur libre + notes optionnelles
- [x] Bouton de validation rapide, reset automatique apres succes
- [x] Historique "Mes ventes du jour" avec stats (nombre, articles, chiffre)
- [x] Sous-onglet "Historique" avec filtres scope (mes/toutes) et periode (jour/semaine/mois/tout)

### 2.2 Base de donnees ventes generiques
- [x] Table `sales` generique :
  - `item_type` (weapon, ammo, drug, melee, other)
  - `item_id` nullable (reference vers la table correspondante selon type)
  - `item_name` (texte pour les cas sans reference)
  - `quantity`, `unit_price`, `total_price`
  - `buyer_name`, `sold_by_user_id`, `validated_by_user_id` nullable, `validated_at` nullable
  - `notes`, `created_at`, `updated_at`
  - Index sur `item_type`, `item_id`, `created_at`
- [x] Modele `Sale` avec constante `TYPES`, scopes `ofType()` et `today()`, relations `soldBy`, `validatedBy`, `weapon`
- [x] Pour les ventes de type `weapon` : decrement auto du `weapon_stock` correspondant + `weapon_stock_movement` de type `sale`
- [ ] **(Phase Harmonisation)** Migrer les `weapon_sales` existantes vers `sales` et retirer l'ecriture dans `weapon_sales` depuis `/espace-membres`

### Fichiers
- `database/migrations/2026_04_16_171611_create_sales_table.php`
- `app/Models/Sale.php`
- `app/Http/Controllers/SaleController.php`
- `resources/views/ventes.blade.php`
- `public/js/ventes.js`
- `public/css/mc-layout.css` (blocs `VENTES PAGE` -- `.member-row`, `.members-stat`, `.sale-total`, etc.)
- Routes : `GET /ventes`, `GET /ventes/api/list`, `POST /ventes/api/create`
- Hub MC + nav : bouton "Ventes rapides" visible une fois connecte

---

## Phase H - Harmonisation et deduplication [A PLANIFIER]
**Priorite : critique -- a traiter avant que les doublons deviennent ingerables**

### Contexte
Au fil du developpement par phases, chaque nouveau module cree sa propre surface (table + UI) sans
reprendre systematiquement l'ancien. Resultat : plusieurs chemins aboutissent au meme effet
metier avec des enregistrements dupliques ou des lectures divergentes. Exemples actuels :

- **Ventes d'armes** : le dashboard `/espace-membres` ecrit toujours dans `weapon_sales`,
  tandis que `/ventes` ecrit dans `sales`. Les historiques sont desynchronises.
- **Mouvements de stock armurerie** : le simulateur gere `weapon_stock_movements`, mais le futur
  module stocks generiques (Phase 3) introduira `stock_movements`.
- **Ajouts/retraits stock** : accessibles a la fois via le dashboard membre et via le panel
  Filament armurerie -- chaque chemin a sa propre validation.

### H.1 Audit et cartographie
- [ ] Lister toutes les tables operationnelles et leur finalite reelle.
- [ ] Identifier les couples (table historique / table generique) et decider de la table maitre.
- [ ] Identifier les formulaires/pages qui ecrivent dans une table historique et planifier leur migration.

### H.2 Migration des donnees
- [ ] Script de migration `weapon_sales` -> `sales` (type=weapon, item_id=weapon_id).
- [ ] Script de migration `weapon_stocks` + `weapon_stock_movements` -> `stock_items` + `stock_movements`
      (si Phase 3 retient la voie "tout generique").
- [ ] Conserver les tables historiques en lecture seule (flag `is_legacy`) ou les supprimer apres verification.

### H.3 Unification des UIs
- [ ] `/espace-membres` : remplacer le formulaire de vente d'arme par un lien/redirect vers `/ventes`
      (ou embarquer le meme composant).
- [ ] `/espace-membres` : harmoniser le formulaire de mouvement de stock avec `/stocks` (Phase 3).
- [ ] Panel Filament armurerie : decider si on conserve l'interface avancee (probablement oui) ou
      si on la masque apres migration. Le superadmin garde l'acces quoi qu'il arrive.

### H.4 Regle de fer
- [ ] Chaque phase future doit declarer explicitement :
      a) si elle cree une nouvelle table, b) si elle remplace une table existante, c) les UIs qui
      doivent etre migrees ou supprimees en meme temps.
- [ ] Ajouter une check-list "deduplication" dans le modele de commit / PR.

---

## Phase 3 - Module stocks generique
**Priorite : haute -- tracabilite indispensable**

### 3.1 Extension du systeme de stock
- [ ] Generaliser le concept de stock au-dela des armes :
  - Armes (deja existant)
  - Munitions (deja existant partiellement)
  - Drogues (nouveau)
  - Armes blanches (nouveau)
  - Autres items (extensible)
- [ ] Creer une table `stock_items` generique ou adapter `weapon_stocks` :
  - `category` (arme, munition, drogue, arme_blanche, autre)
  - `name`, `slug`, `quantity_in_stock`, `quantity_external`
  - `purchase_price`, `sell_price`, `sort_order`
- [ ] Creer une table `stock_movements` generique :
  - `stock_item_id`, `user_id`, `attributed_to_user_id`
  - `quantity_change`, `reason` (entree, sortie, vente, perte, don, retour)
  - `requires_approval`, `approved_by_user_id`, `approved_at`
  - `notes`, `created_at`

### 3.2 Page etat des stocks
- [ ] Creer une page `/stocks` montrant pour chaque item :
  - Quantite en stock
  - Quantite en exterieur (attribuee a des membres, non reconciliee)
  - Lien vers le detail (qui a quoi)
- [ ] Page detail par item : liste des attributions non reconciliees (membre, quantite, date de sortie)
- [ ] Reconciliation : le membre indique "vendu" (cree une vente), "retour" (retour stock), "perte" (saisie police, etc.), "don"

### 3.3 Validation tresorier
- [ ] Certains mouvements necessitent approbation : sorties importantes, retours
- [ ] Notification au tresorier (dans l'app, pas par email)
- [ ] Page de validation : liste des mouvements en attente, bouton approuver/refuser

### 3.4 Attribution d'items a un membre/prospect (officier+)
- [ ] Formulaire dedie "Attribuer un item" accessible a officier+ sur `/espace-membres` (ou `/stocks`)
- [ ] Selection : item (arme/munition/drogue/arme blanche) + quantite + beneficiaire (membre ou prospect)
- [ ] Enregistre un `stock_movement` de type `attribution` (quantite sortie du stock interne, ajoutee au compte externe du beneficiaire)
- [ ] Champ `attributed_to_user_id` sur le mouvement (deja present sur `weapon_stock_movements`)
- [ ] Notes libres (motif, contexte)
- [ ] Visible sur la fiche du beneficiaire (items actuellement en sa possession, non reconcilies)
- [ ] Le beneficiaire peut ensuite reconcilier : vendu (genere une vente), perdu, retour stock, don
- [ ] Historique complet des attributions consultable par officier+
- [ ] Generalisable a tous les types de stock (arme, munition, drogue, arme blanche)

### 3.4 Import stock via CSV/Excel
- [ ] Page d'import accessible tresorier/president
- [ ] Upload fichier CSV/Excel (genere depuis screenshots en jeu via GPT)
- [ ] L'import ecrase les quantites en stock MAIS ne modifie pas les quantites exterieures (emprunts en cours)
- [ ] Preview avant validation de l'import
- [ ] Historique des imports avec date et utilisateur

---

## Phase 4 - Module drogues
**Priorite : moyenne -- flux economique important**

### 4.1 Referentiel drogues
- [ ] Creer une table `drugs` (ou utiliser le stock generique) :
  - Weed (Blue Dream, White Widow, Purple, OG Kush)
  - Cook
  - Amphetamine (basse, moyen, haute)
  - Methamphetamine (basse, moyen, haute)
  - LSD, MDMA, LEAN
- [ ] Champs : `name`, `quality` (basse/moyen/haute si applicable), `purchase_price_orga`, `sell_price_street`, `sell_price_sac`, `sell_price_orga`, `min_price_staff`, `fabrication` (Inde-Gangs-MC / Orga-Famille / Cayo)
- [ ] Seeder avec les donnees de `drogue_indicatif.png` :

| Drogue | Prix PNJ (Rue) | Prix au sac | Prix vente Orga | Prix min staff | Fabrication |
|--------|----------------|-------------|-----------------|----------------|-------------|
| Weed Blue Dream | 140-180 | 100 | / | / | Inde-Gangs-MC |
| Weed White Widow | 80-140 | 65 | / | / | Inde-Gangs-MC |
| Weed Purple | 50-80 | 45 | / | / | Inde-Gangs-MC |
| Weed OG Kush | 30-50 | 30 | / | / | Inde-Gangs-MC |
| Cook | 350-750 | 450 | 300-350 | 300 | Orga-Famille |
| Amphetamine basse | 500 | 400 | 400 | 400 | Orga-Famille |
| Amphetamine moyen | 900 | 600 | 700-800 | 700 | Orga-Famille |
| Amphetamine haute | 1000 | 800 | 800-900 | 800 | Orga-Famille |
| Methamphetamine basse | 600-750 | 1000 | 550 | 550 | Orga-Famille |
| Methamphetamine moyen | 1000-1500 | 1400 | 900 | 900 | Orga-Famille |
| Methamphetamine haute | 2000-2600 | 2200 | 1300-1400 | 1400 | Orga-Famille |
| LSD | 3800 | / | 3000-3500 | 3000 | Cayo |
| MDMA | 2900 | / | 2000-2500 | 2000 | Cayo |
| LEAN | 2400 | / | 1600-2000 | 1600 | Cayo |

### 4.2 Flux de gestion des drogues
- [ ] Achat aux organisations : enregistrement quantite + prix + fournisseur
- [ ] Attribution a un membre/prospect : X unites confiees a une personne
- [ ] Reconciliation par le membre : vendu (avec montant), perdu (saisie police), retour stock
- [ ] Dashboard drogue : stock total, stock en exterieur, detail par membre, pertes
- [ ] Calcul automatique profit/perte par operation

---

## Phase 5 - Armes blanches
**Priorite : moyenne -- items simples a integrer**

### 5.1 Referentiel armes blanches
- [ ] Ajouter les armes blanches au systeme de stock generique :

| Arme | Prix d'achat | Prix de vente (x1.5) |
|------|-------------|---------------------|
| Switchblade | 20 000 | 30 000 |
| Knife | 20 000 | 30 000 |
| Machete | 20 000 | 30 000 |
| Batte | 12 000 | 18 000 |
| Queue de billard | 12 000 | 18 000 |
| Golf Club | 12 000 | 18 000 |
| Pied de biche | 15 000 | 22 500 |
| Hammer | 15 000 | 22 500 |
| Cle anglaise | 15 000 | 22 500 |

- [ ] Multiplicateur de vente par defaut : x1.5 (configurable dans les parametres)
- [ ] Integration dans le flux de stock et de vente generique

---

## Phase 6 - Classements et fiches membres
**Priorite : moyenne -- motivation et suivi**

### 6.1 Classements
- [ ] Page `/classements` accessible a tous les membres connectes
- [ ] Classement global (depuis le debut)
- [ ] Classement mensuel
- [ ] Classement hebdomadaire
- [ ] Criteres : montant total des ventes, nombre de ventes, profit genere
- [ ] "Aigle de la semaine" : membre le plus productif (badge visible sur sa fiche)

### 6.2 Fiches membres
- [ ] Page `/membres/{id}` accessible uniquement par officiers et president
- [ ] Contenu de la fiche :
  - Informations : nom, role, date d'arrivee
  - Items actuellement en sa possession (pris du stock, non reconcilies)
  - Historique des ventes (montant total, nombre, dernieres ventes)
  - Historique des mouvements de stock (prises, retours, pertes)
  - Argent rapporte au MC (total, ce mois, cette semaine)
  - Cotisations : etat des paiements
- [ ] Liste des membres : `/membres` avec filtres par role, tri par activite

---

## Phase 7 - Comptabilite MC
**Priorite : basse -- complexe mais necessaire a terme**

### 7.1 Suivi des comptes
- [ ] Creer une table `mc_accounts` :
  - `type` : argent_sale, argent_propre
  - `balance` : solde actuel
- [ ] Creer une table `mc_transactions` :
  - `account_type`, `amount`, `reason`, `category` (vente, achat, amende, entretien, cotisation, autre)
  - `created_by_user_id`, `validated_by_user_id`
  - `requires_validation`, `validated_at`
  - `notes`, `created_at`
- [ ] Page `/comptabilite` accessible tresorier/president : vue des soldes, historique transactions

### 7.2 Systeme de demandes
- [ ] Les membres peuvent soumettre des demandes de remboursement (amende, entretien moto, etc.)
- [ ] Le tresorier/president valide ou refuse
- [ ] Le tresorier peut encoder directement des transactions sans demande
- [ ] Historique complet avec filtres par type, periode, membre

### 7.3 Cotisations
- [ ] Table `cotisations` :
  - `user_id`, `period_start`, `period_end`
  - `amount_due`, `amount_paid`, `paid_at`
  - `notes`
- [ ] Montants par defaut selon le role (configurable dans les parametres) :
  - Prospect : 2 000 / semaine
  - Membre : 5 000 / semaine
  - Officier : 10 000 / semaine
- [ ] Page de suivi des cotisations :
  - Indiquer le jour de cotisation qui a paye, combien (possibilite de payer plus)
  - Vue tresorier : qui est a jour, qui doit encore
- [ ] Alertes pour les retards de paiement

---

## Phase 8 - Ameliorations UX et polissage
**Priorite : continue -- en parallele des autres phases**

### 8.1 Responsive / mobile
- [ ] Audit complet du CSS sur mobile (simulateur, stocks, classements)
- [ ] Navigation hamburger ou bottom tabs sur mobile
- [ ] Boutons et zones de touch suffisamment grands

### 8.2 Notifications in-app
- [ ] Systeme de notifications simples (table `notifications`)
- [ ] Badge compteur dans le header
- [ ] Types : mouvement de stock en attente, cotisation due, contrat mis a jour, nouveau classement

### 8.3 Dashboard MC
- [ ] Page d'accueil personnalisee selon le role :
  - Membre : ses stats, ses items, rappel cotisation
  - Officier : alertes stock, mouvements en attente, classement
  - Tresorier : soldes, transactions recentes, cotisations en retard
  - President : vue globale de tout

---

## Resume des tables a creer

| Phase | Tables |
|-------|--------|
| 0 | `settings`, `page_access_rules` |
| 2 | `sales` (generique) -- CREEE |
| H | -- (migration + deprecation) |
| 3 | `stock_items`, `stock_movements` (generiques) |
| 4 | extension de `stock_items` (categorie `drug` / `drug_raw` / `farm_consumable`) |
| 5 | extension de `stock_items` (categorie `melee`) |
| 6 | -- (utilise les tables existantes) |
| 7 | `mc_accounts`, `mc_transactions`, `cotisations` |
| 8 | `notifications` |

---

## Ordre de realisation recommande

```
Phase 0 (prerequis)           -- TERMINEE
  |
  v
Phase 1 (reorganisation UX)   -- TERMINEE
  |
  v
Phase 2 (ventes rapides)      -- TERMINEE
  |
  v
Phase H (harmonisation)       -- 1 jour (AVANT d'ajouter d'autres modules doublonnes)
  |
  v
Phase 3 (stocks generiques)   -- 3-4 jours
  |
  +---> Phase 4 (drogues)     -- 2-3 jours
  |
  +---> Phase 5 (armes bl.)   -- 1 jour
  |
  v
Phase 6 (classements/fiches)  -- 2-3 jours
  |
  v
Phase 7 (comptabilite)        -- 3-4 jours
  |
  v
Phase 8 (polissage)           -- continu
```

**Estimation totale restante : ~13-17 jours de developpement (Phase H + 3 a 8)**

---

## Notes importantes

1. **Tout configurable en DB** : chaque prix, recette, multiplicateur doit etre stocke dans la table `settings` et modifiable via la page parametres (tresorier/president uniquement).

2. **Droits par page** : chaque page/fonctionnalite doit verifier le role minimum requis. La matrice des droits sera definie en Phase 0.2.

3. **Stock : quantite en stock vs exterieur** : toujours distinguer ce qui est physiquement en stock de ce qui a ete confie a un membre. L'import CSV ecrase le stock physique, pas les attributions.

4. **Pas de suppression physique** : utiliser du soft delete ou un flag `is_active` pour garder l'historique complet.

5. **Prix d'achat aux orga (drogues)** : manquant dans `drogue_indicatif.png`, a completer manuellement dans les parametres.

6. **Harmonisation** : chaque nouveau module doit etre accompagne d'une verification anti-doublons
   (cf. Phase H). Ne jamais laisser deux formulaires ecrire dans deux tables differentes pour
   la meme operation metier.

---

## Annexe A -- Inventaire observe dans le coffre MC (in-game)

Total observe au 16 avril 2026 : 309 kg / 1000 kg max.
Ces items doivent etre couverts par les Phases 3 (stocks generique), 4 (drogues) et 5 (armes blanches).

### Categories

**A.1 Armes finies (categorie `weapon_finished`)**
- PISTOL .50 (differentes masses : 2.00 / 2.225 / 2.405 kg)
- SNS PISTOL (465g / 555g)

**A.2 Corps et pieces armurerie (categorie `piece`)**
- CORPS DE PISTOLET, CORPS DE SMG, CORPS DE FUSILS
- CANON, POIGNEE, CROSSE, RESSORT
- TACTICAL SUPPRESSOR, SUPPRESSOR

**A.3 Matieres premieres armurerie (categorie `raw_material`)**
- POUDRE A CANON, FRAGMENT DE METAL, PIECE DE METAL

**A.4 Munitions (categorie `ammo`)**
- .45 ACP, 9MM, .50 AE, .50 BMG
- 12 GAUGE, 5.56x45, 7.62x51, 7.62x39

**A.5 Plans d'armes (categorie `plan`)**
- PLAN CALIBRE 50 (x5 au moins)
- PLAN MG, PLAN MACHINE ..., PLAN PISTOLET
- PLAN COMBAT P... (x2 au moins)
- PLAN AK47, PLAN AK COMPACT
- PLAN FUSIL A P..., PLAN MINI SMG

**A.6 Armes blanches (categorie `melee`, Phase 5)**
- KNIFE (300g)
- KATANA BXLIFE (500g)
- (a completer : Switchblade, Machete, Batte, Queue de billard, Golf Club, Pied de biche,
  Hammer, Cle anglaise -- liste Phase 5)

**A.7 Drogues finies (categorie `drug`, Phase 4)**
- BRIQUE DE WEED (100g)
- BRIQUE DE COCAINE (400g)
- SACHET DE WEED (5g / 1kg selon qualite), SACHET PLASTIQUE
- JOINT (PURPLE / ...) (3g)
- METH (HAUTE Q...) (100g)
- COCAINE (70g)

**A.8 Matieres premieres drogues (categorie `drug_raw`, Phase 4)**
- TETE DE WEED (basse / moyenne / haute qualite selon poids)
- GRAINE DE WEED (3 varietes : 1g / 1g / 1g selon couleur -- Blue Dream, White Widow, Purple...)
- POUDRE DE CAFEINE
- FEUILLE A ROULER

**A.9 Consommables agricoles (categorie `farm_consumable`, Phase 4)**
- ENGRAIS (VITESSE)
- ENGRAIS (BOOSTER)
- SPRAY PESTICIDE

**A.10 Outils et accessoires (categorie `tool`, Phase 3 ou extension)**
- DECOUPEUR PLASMA
- MEULEUSE D'ANGLE
- FOREUSE
- OUTIL DE CROCHETAGE
- CLE USB PHANTOM
- MENOTTES

**A.11 Electronique (categorie `electronic`, futur)**
- GRAND ECRAN POUR ORDINATEUR
- PETIT ECRAN POUR ORDINATEUR
- CARTE ELECTRONIQUE (2 tailles)
- CARTE DE PIRATAGE
- MACHINE DE TRANSFERT (x2)
- FIL DE CUIVRE

**A.12 Divers / sacs**
- SAC A METTRE [...] (plusieurs variantes)
- ARGENT SALE (561 235$ observes)

### Implications pour les phases

- **Phase 3** doit prevoir une taxonomie `stock_items.category` riche :
  `weapon_finished`, `piece`, `raw_material`, `ammo`, `plan`, `melee`, `drug`, `drug_raw`,
  `farm_consumable`, `tool`, `electronic`, `misc`.
- **Phase 4 (drogues)** doit distinguer drogues finies / matieres premieres / consommables agricoles.
- Un champ `unit_weight_g` sur `stock_items` permettrait de calculer l'occupation du coffre
  (309/1000 kg) et d'eviter de charger plus que la capacite.
- L'import CSV/Excel (Phase 3.5) doit pouvoir associer chaque ligne a un item existant ou creer
  un nouvel item avec sa categorie.
