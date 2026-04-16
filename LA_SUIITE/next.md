

Plan de développement .md avant de continuer

-Sépare les simulateurs d'arme et de munition en 2 endroits différents, car on ajoutera d'autres par la suite.
-Déplace l'option de login pour qu'elle soit présente en haut à droite "espace membre" au lieu de l'afficher quand on entre dnas l'onglet espace membre
-Revois l'app pour qu'elle soit un peu plus mobile friendly (Menu principal où l'on a des grands boutons: "Sim Armes", "Sim Munitions", "Stocks", "Historique",  "Contrats", ...
Il faudra revoir les droits afin que les rôles permettent de donner accès à l'une ou l'autre page.
-Il faut une page où ceux qui sont membre puissent très facilement indiquer "J'ai vendu 100 mun cal. 50 pour tel prix" (calcul auto du prix unitaire).
-Il faut un classement (global, par mois, par semaine) des membres les plus productifs en terme de vente.
-Il faut un historique des entrées et sorties du stock, savoir qui a quoi (X à retiré 3 armes du stock, il les a assignées à lui tant qu'il ne les indiuqe pas vendue ou que le trésorier n'a pas validé le retour en stock.


Le but principal est de faciliter grandement la vie de tous les jours et la gestion comptable du MC.
Tu peux être imaginatif et proposer des idées utiles.

Les objectifs:
Pouvoir déterminer aisément un prix de vente des armes/munitions grâce aux statistiques sur les matières premières
Pouvoir déterminer un prix d'achat pour le fer qui nous permette d'être rentable ( actuellement 30 )
Pouvoir, dans une page, configurer le prix de chaque chose utilisée dans les formules ( Recette des items, prix d'une pièce à l'achat, recette si craftée quand c est possible,...)
Permettre de savoir qui fait entrer ou sortir quelque chose du stock (Certaines choses demandent approbation du trésorier/président)
Permettre de savoir qui rapporte de l'argent.
Il faudra aussi permettre d'ajouter divers items au stock (Ex: on achète de la drogue ( meth, coke, ... ) aux organisations et on les revend. Avant la revente, c est confié aux membres/prospects/...  et il faut savoir ce qu'on a confié à qui et pour combien ils l'ont revendu. ou si ils se sont fait chopé et ont créé une perte en se faisant saisir les quantités.
A tout moment on doit connaître l'état du stock

Il faut aussi pouvoir importer l'état des stock, ce qui écrasera les quantités disponibles, mais pas ce que les gens ont empreunté des stock)  via un fichier csv/excell (qui sera généré sur base de capture d'écran prise en jeu et données à gpt avec l'instruction de générer le fichier)

important: tout ce qui est configurable doit être stocké en DB et adaptable dans une page "configuration" ou "paramètres", uniquement accessible par trésorier/président (prix défini manuellement pour les mun, armes, recettes, etc, comme indiqué plus haut)

les drogues à ajouter sont dans drogue_indicatif.png, mais il manque le prix d'achat aux orga.

Les armes blanches sont à ajouter aux items à vendre/stocker
switchblade:20k€
knife:20k€
machete:20k€
batte:12k€
queue de billard:12k€
golf  club:12k€
pied de biche:15k€
Hammer:15k€
Clé anglaise:15k€
le prix de vente de ces armes est par défaut en x1.5 (switchblade: 30k prix de vente par exemple)


pour les stock, il faudra indiquer ce qu'il y a en stock, la quantité en extérieure (avec possibilité d'accéder au détail de qui a pris quoi mais de l'a pas encore réconcilié via une vente, un retour, une perte, ou un don à la personne simplement)


Il faudra aussi permettre de loguer l'état des comptes (argent sâle, argent propre) et un système pour encoder (demande par les membres avc validation trésorier, ou encodage direct trésorier pour emboursement d'amende, entretient moto, etc)

Il faudra aussi permettre de gérer les cotisation des membres en fonction de leur rôle (Si prospect, doit 2k par semaine, si membre, doit 5k par semaine, si off , doit 10k par semaine)
il faudra pouvoir indiquer, le jour des cotisation, qui a payé, combien ( si décide de mettre d'avantage )...

Afficher des fiches de membres, avec ce qu'ils ont sur eux , pris des stock, ce qu'ils ont rapporté comme argent etc

TOUT doit être stocké en DB de manière correctement structurée.

les fiches ne sont accessibles que par les off ou le président

les classements permettront de définir l'aigle de la semaine