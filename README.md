# plugin-husqvarna_map

## Fonctions

Ce plugin est basé sur la version de référence disponible sur le market Jeedom, et ajoute des fonctions complémentaires.
Il reste globalement compatible de cette version de référence, car les Infos et Commandes existantes ont été conservées.

Les fonctions ajoutées au plugin actuel sont:
* La position courante GPS du robot et des 50 dernières positions pour l’affichage sur une carte dans le widget.
* La gestion d’une planification du robot sur un rythme hebdomadaire, avec 2 plages horaires quotidiennes, et la possibilité de sélectionner une zone active du robot par commande Jeedom (pour ceux qui ont mis en place 2 zones de tontes avec commutation par relais)
* La possibilité d’interrompre et de reprendre la planification en fonction de la météo, par couplage avec le plugin «Météo France/ pluie à 1 h».
* La mise en place d’une page «panel» qui permet:
  * D’afficher sur une carte les positions du robot dans le temps. Cela permet de visualiser si le robot couvre uniformément l’espace à tondre.
  * D’afficher la configuration actuelle du robot (juste pour information pour le moment)

<p align="left">
  <img src="../master/docs/images/widget.png" width="300" title="Widget dashboard">
</p>

## Documentation
[Documentation](../../tree/master/docs/fr_FR/index.md)

## Versions
[Versions](../../tree/master/docs/fr_FR/changelog.md)

