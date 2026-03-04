# Product Context - Station LTD / Toolbox Jacques Noir

## Pourquoi ce projet existe
Jacques Noir, patron de la station LTD sur Bruxelles Life, a besoin :
- D'un menu visuel professionnel pour afficher les prix (screenshot + Discord)
- D'un panneau d'administration pour gerer les donnees sans toucher au code
- D'une plateforme extensible pour de futurs outils lies a son activite RP

## Problemes resolus
- Gestion centralisee des produits, menus et tarifs entreprise
- Interface d'administration intuitive (Filament) au lieu d'edition JSON manuelle
- Modele de donnees unifie : un seul Product alimente les pages produits, menus et entreprises
- Documentation du reglement BXL Life accessible en local pour reference rapide

## Experience utilisateur

### Visiteur (client en jeu)
- Arrive sur la page d'accueil, choisit une section (Produits, Menus, Entreprises)
- Design panneau bois, compact, lisible en screenshot
- Mode clean (?clean) pour captures sans navigation

### Administrateur (Jacques Noir)
- Accede au panneau Filament via /admin
- Gere les produits (nom, prix, categorie, flags retail/enterprise)
- Gere les menus (composition depuis les produits existants, promotions)
- Gere les groupes entreprise (produits attaches avec prix specifiques)
- Visualise les statistiques du catalogue sur le dashboard

### Futur (extensible)
- Systeme de lotto/tombola
- Estimation de stock via screenshots
- Strategies de vente et analyses
- Autres outils selon les besoins RP

## Pages publiques
1. **Accueil** (`/`) : landing page avec logo LTD, 3 cartes de navigation, galerie
2. **Produits** (`/produits`) : produits retail en 2 colonnes (left/right)
3. **Menus** (`/menus`) : formules et packs (produits referencees, choix multiples)
4. **Entreprises** (`/entreprises`) : tarifs specifiques par entreprise partenaire (protege par mot de passe cote client)
