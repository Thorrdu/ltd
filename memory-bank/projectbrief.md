# Project Brief - Station LTD / Toolbox Lost MC

## Contexte
Application web multi-usage pour le MC (Motorcycle Club) **Lost MC** sur le serveur GTA RP **Bruxelles Life**.
Joueur : **Jacques Noir**, patron de la station LTD (Little Seoul, Anderlecht) et membre du Lost MC.

## Objectif principal
Fournir un outil complet pour la gestion quotidienne du MC et de la station LTD :
- **Site vitrine LTD** : affichage professionnel des produits, menus et tarifs entreprises
- **Panneau d'administration LTD** : gestion des donnees catalogue via Filament
- **Panneau Armurerie** : gestion complete des armes, stocks, mouvements, contrats et ventes
- **Simulateur d'armes** : outil de calcul de rentabilite base sur les matieres premieres et recettes de craft
- **Plateforme extensible** : futur systeme de gestion des drogues, stock general, cotisations, fiches membres, comptabilite MC

## Portee actuelle
- **Site public LTD (Blade)** : 4 pages catalogue (accueil, produits, menus, entreprises)
- **Simulateur armes (Blade + JS)** : page standalone avec onglets simulateur / espace membres
- **Panneau Filament Admin** (`/admin`) : CRUD catalogue LTD (categories, produits, menus, entreprises)
- **Panneau Filament Armurerie** (`/armurerie`) : CRUD armes, stocks, mouvements, contrats, ventes + page CraftWeapon
- **Authentification** : systeme de roles simple (champ `role` sur User) + PIN pour simulateur
- **Documentation** : reglement BXL Life, architecture, taches futures

## Exigences cles
- Design compact catalogue, optimise pour screenshot (panneau bois, dot leaders, zebra stripes)
- Modele Product unifie pour retail/enterprise/menus
- Domaine armurerie complet avec tracabilite des mouvements de stock
- Systeme de contrats clients pour les commandes d'armes
- Systeme de ventes avec suivi par vendeur
- Prix configurables en DB (matieres premieres, recettes, prix de vente)
- Stack : Laravel 12 + Filament 5 + MySQL 8 + Vite
