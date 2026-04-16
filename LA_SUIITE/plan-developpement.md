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

## Phase 2 - Module de ventes rapides
**Priorite : haute -- besoin quotidien des membres**

### 2.1 Page de saisie rapide des ventes
- [ ] Creer une page `/ventes` accessible a tous les membres connectes
- [ ] Formulaire simplifie : selection item (arme/munition/drogue/arme blanche), quantite, prix total
- [ ] Calcul automatique du prix unitaire
- [ ] Champ acheteur (texte libre)
- [ ] Bouton de validation rapide (un clic pour enregistrer)
- [ ] Historique des ventes du jour pour le membre connecte

### 2.2 Base de donnees ventes generiques
- [ ] Creer une table `sales` generique (ou etendre `weapon_sales`) :
  - `item_type` (arme, munition, drogue, arme_blanche, autre)
  - `item_id` (nullable, reference vers la table correspondante)
  - `item_name` (texte pour les cas sans reference)
  - `quantity`, `unit_price`, `total_price`
  - `buyer_name`, `sold_by_user_id`, `validated_by_user_id`
  - `notes`, `created_at`
- [ ] Creer le modele `Sale` avec relations polymorphiques ou type enum
- [ ] Migrer les `weapon_sales` existantes vers ce systeme unifie (ou les garder en parallele)

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
| 0 | `settings` |
| 2 | `sales` (generique) |
| 3 | `stock_items`, `stock_movements` (generiques) -- OU extension des tables weapon_* |
| 4 | `drugs` (ou via stock_items) |
| 5 | via stock_items |
| 6 | -- (utilise les tables existantes) |
| 7 | `mc_accounts`, `mc_transactions`, `cotisations` |
| 8 | `notifications` |

---

## Ordre de realisation recommande

```
Phase 0 (prerequis)          -- 1-2 jours
  |
  v
Phase 1 (reorganisation UX)  -- 2-3 jours
  |
  v
Phase 2 (ventes rapides)     -- 1-2 jours
  |
  v
Phase 3 (stocks generiques)  -- 3-4 jours
  |
  +---> Phase 4 (drogues)    -- 2-3 jours
  |
  +---> Phase 5 (armes bl.)  -- 1 jour
  |
  v
Phase 6 (classements/fiches) -- 2-3 jours
  |
  v
Phase 7 (comptabilite)       -- 3-4 jours
  |
  v
Phase 8 (polissage)          -- continu
```

**Estimation totale : 15-22 jours de developpement**

---

## Notes importantes

1. **Tout configurable en DB** : chaque prix, recette, multiplicateur doit etre stocke dans la table `settings` et modifiable via la page parametres (tresorier/president uniquement).

2. **Droits par page** : chaque page/fonctionnalite doit verifier le role minimum requis. La matrice des droits sera definie en Phase 0.2.

3. **Stock : quantite en stock vs exterieur** : toujours distinguer ce qui est physiquement en stock de ce qui a ete confie a un membre. L'import CSV ecrase le stock physique, pas les attributions.

4. **Pas de suppression physique** : utiliser du soft delete ou un flag `is_active` pour garder l'historique complet.

5. **Prix d'achat aux orga (drogues)** : manquant dans `drogue_indicatif.png`, a completer manuellement dans les parametres.
