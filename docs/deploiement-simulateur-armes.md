# Simulateur armes — déploiement (production)

Les prix **Vente** / **Marge** du tableau et de l’objectif viennent des colonnes `sell_price` (et `reference_purchase_price` uniquement pour le SNS) sur la table `weapons`. Si ces champs sont vides après un déploiement, le front affiche des tirets.

## Commandes recommandées

```bash
php artisan migrate --force
php artisan db:seed --class=WeaponSeeder --force
```

Sur une **base déjà peuplée**, `WeaponSeeder` fait un `updateOrCreate` par `slug` : les recettes et les prix sont mis à jour sans écraser les autres tables.

Sur une **base vide**, exécuter plutôt `php artisan db:seed` (le `DatabaseSeeder` appelle déjà `WeaponSeeder`).

## Vérification

En SQL ou Tinker : les armes actives doivent avoir `sell_price` non nul (sauf configuration volontaire).
