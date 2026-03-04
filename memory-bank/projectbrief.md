# Project Brief - Station LTD / Toolbox Jacques Noir

## Contexte
Application web pour la station-service LTD de Little Seoul (Anderlecht), sur le serveur GTA RP **Bruxelles Life**.
Joueur : **Jacques Noir**, patron de l'entreprise LTD.

## Objectif principal
Fournir un outil multi-usage pour Jacques Noir :
- **Site vitrine** : affichage professionnel des produits, menus et tarifs entreprises (capture screenshot, partage Discord)
- **Panneau d'administration** : gestion complete des donnees via Filament (produits, categories, menus, entreprises)
- **Assistant/Toolbox** : plateforme extensible pour de futures mecaniques (lotto/tombola, estimation de stock, strategies de vente, etc.)

## Portee actuelle
- **Site public (Blade)** : 4 pages reproduisant fidelement le design original (panneau bois, carte de restaurant)
  - Page d'accueil (landing page avec logo, navigation, galerie)
  - Page produits (snacks, boissons, coin festif, objets du quotidien)
  - Page menus (formules et promotions)
  - Page entreprises (tarifs reserves aux partenaires)
- **Panneau Filament** : administration CRUD de toutes les donnees avec filtres, relations et actions bulk
- **Documentation** : reglement BXL Life, structure de taches futures

## Exigences cles
- Design compact, optimise pour screenshot (panneau bois, dot leaders, zebra stripes)
- Logo reel (PNG) integre dans toutes les pages
- Modele Product unifie (source unique pour retail, enterprise et menus)
- Prix specifiques par entreprise partenaire via table pivot
- Menus referencant les produits existants (avec gestion des choix via choice_group)
- Mode clean (?clean) pour captures sans navigation
- Stack : Laravel 11 + Filament 3 + MySQL + Vite
