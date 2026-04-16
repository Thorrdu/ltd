# Product Context - Station LTD / Toolbox Lost MC

## Pourquoi ce projet existe
Le Lost MC a besoin d'outils numeriques pour :
- Afficher professionnellement les prix de la station LTD (screenshot + Discord)
- Gerer l'armurerie du MC (craft, stocks, ventes, contrats)
- Calculer la rentabilite des armes et munitions via un simulateur
- Suivre les activites economiques des membres (ventes, stocks, argent rapporte)
- Faciliter la gestion comptable quotidienne du MC

## Problemes resolus
- Gestion centralisee du catalogue LTD (produits, menus, tarifs entreprise)
- Suivi complet du cycle de vie des armes : craft -> stock -> attribution -> vente/retour
- Calcul automatique de rentabilite armes/munitions base sur les matieres premieres
- Tracabilite des mouvements de stock (qui a pris quoi, quand, pourquoi)
- Gestion des contrats clients avec suivi des livraisons

## Experience utilisateur

### Visiteur (client LTD en jeu)
- Arrive sur le catalogue, choisit une section (Produits, Menus, Entreprises)
- Design panneau bois compact, lisible en screenshot
- Mode clean (?clean) pour captures sans navigation

### Membre du MC
- Accede au simulateur d'armes pour calculer les prix de vente
- Se connecte via PIN personnel pour acceder a l'espace membre
- Enregistre ses ventes, consulte ses stats

### Officier / Tresorier / President
- Accede aux panneaux Filament (admin LTD + armurerie)
- Gere les stocks, valide les mouvements, cree des contrats
- Consulte les fiches membres et classements

### Futur (extensible)
- Gestion des drogues (achat orga, distribution, revente, pertes)
- Armes blanches et autres items a vendre/stocker
- Gestion des cotisations par role
- Comptabilite MC (argent sale/propre, remboursements)
- Fiches membres detaillees
- Classements (aigle de la semaine)
- Import stock via CSV/Excel

## Pages et interfaces
1. **Accueil LTD** (`/`) : landing page avec logo, 3 cartes, galerie
2. **Produits** (`/produits`) : produits retail en 2 colonnes
3. **Menus** (`/menus`) : formules et packs
4. **Entreprises** (`/entreprises`) : tarifs specifiques par partenaire (protege par mot de passe)
5. **Simulateur armes** (`/simulateur-armes`) : calcul de prix, espace membres, craft munitions
6. **Admin LTD** (`/admin`) : panneau Filament catalogue
7. **Armurerie** (`/armurerie`) : panneau Filament domaine armes
