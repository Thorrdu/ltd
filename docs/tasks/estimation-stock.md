# Estimation de Stock via Screenshots

## Informations

| Champ     | Valeur          |
|-----------|-----------------|
| Type      | Analyse         |
| Statut    | Idee            |
| Priorite  | Haute           |
| Date      | 4 mars 2026     |

## Contexte

En jeu, le stock de la station LTD est visible via l'interface du coffre/inventaire. En prenant une capture d'ecran de cet inventaire, on pourrait estimer la valeur totale du stock en se basant sur les prix connus des produits.

## Objectif

Pouvoir soumettre une capture d'ecran de l'inventaire en jeu et obtenir :
- Liste des produits identifies et leurs quantites
- Valeur unitaire de chaque produit (depuis la base de donnees)
- Valeur totale estimee du stock
- Eventuellement, des alertes sur les produits en rupture

## Details

Approches possibles :
- **Manuelle** : formulaire ou l'on saisit les quantites, calcul automatique
- **Semi-automatique** : OCR sur la capture d'ecran pour extraire les quantites
- **Via IA** : analyse d'image pour identifier les produits et quantites

Les prix de reference sont deja dans la base de donnees (table products).

## Notes

- L'interface d'inventaire en jeu a un format relativement standardise
- La precision de l'OCR dependra de la qualite de la capture
- Commencer par l'approche manuelle, puis automatiser
