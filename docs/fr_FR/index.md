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
  <img src="../master/doc/images/widget.png" width="300" title="Widget dashboard">
</p>

## Installation du plugin
Par source Github:
* Aller dans Jeedom menu Plugins / Gestion des plugins
* Sélectionner le symbole + (Ajouter un plugin)
* Sélectionner le type de source Github (Il faut l'avoir autorisé au préalable dans le menu Réglages / Système / Configuration => Mise à jour/Market)
* Remplir les champs:
  * ID logique du plugin : **husqvarna_map**
  * Utilisateur ou organisation du dépôt : **lelas33**
  * Nom du dépôt : **plugin_husqvarna_map**
  * Branche : **master**
  * => Puis valider par le bouton "Enregister"
* Rafraichir la page: Le plugin "husqvarna_map" doit apparaitre. Sélectionner son symbole pour aller dans sa page de configuration.
* Sur la page de configuration du plugin:
  * Activer le plugin
  * Saisissez vos identifiant de compte Husqvarna. Puis Sauvegarder
  * Cochez la case :"Afficher le panneau desktop". Cela donne accès à la page du "panel" de l'équipement. Puis Sauvegarder

## Préparation pour le Widget
Le widget sera configuré automatiquement par le plugin lors de la création de l'équipement.

## Création de l'équiment (de votre tondeuse)
Aller dans le menu "plugins/objets connectés/Husqvarna-MAP" de jeedom pour créer votre équipement.
Lancer la détection de votre robot, ce qui crée l'équipement correspondant automatiquement.
* Puis, sur l'onglet "**Equipement**", choisissez l'objet parent
* Sur l'onglet "**Config.Carte**", vous pouvez définir l'image qui sera utilisée par le plugin pour l'utilisation de la position GPS de votre robot.
Comme indiqué sur cette page, il faut générer une image au format "png", de taille environ 500 x 500 pixels, qui représente votre terrain et qui soit géo-référencée. Le plus simple pour cela est d'utiliser Google map en mode satellite. (Vous faite une copie d'écran, en notant bien les coordonnées GPS des 2 coins supérieur/gauche et inférieur/droit, sous la forme latitude,longitude (exemple:48.858748, 2.293794)
(Attention, cette carte doit être orientée Nord en haut.)
Le fichier image obtenu doit être placé dans le dossier "ressources" du plugin, sous le nom "maison.png"
* Saisir ensuite les 3 facteurs de taille pour l'affichage respectivement sur les widgets dashboard, widgets mobile, et page panel. (attention, ces champs ne sont pas préremplis par le plugin, il faut les renseigner)
* Saisir ensuite les coordonnées GPS des 2 coins de l'image

<p align="left">
  <img src="../master/doc/images/installation_1.png" width="600" title="Configuration Carte">
</p>

* Sur l'onglet "**Panification**", vous pouvez définir les plages horaires de fonctionnement de votre robot.
Il y a 2 fonctions supplémentaires offerte par la planification du plugin par rapport à la planification intégrée au robot: - La gestion de 2 zones de fonctionnement du robot (voir la description de cette fonction plus bas), et le couplage au plugin "vigilence météo / prévision dans l'heure" pour suspendre la tonte lors de période pluie. (voir également plus bas le détail sur cette fonction)
* Si la fonction "Planification par zones" est utilisée, cocher la case "Gestion de 2 zones", et définissez en utilisant le sélecteur de commandes, les 2 commandes jeedom pour activer chaque zone.
Définissez ensuite le pourcentage de cycle de tonte à réaliser dans la zone 1, le pourcentage de la zone 2 sera bien sur le complément à 100%. (ces ratios sont à priori en rapport avec la surface relative de chaque zone)
* Si l'**option météo** est utilisée, il aut alors renseigner les commandes dans la section "Utilisation de la météo". 
Pour cela, il faut avoir au préalable installé le plugin "Vigilence météo/Pluie à 1h".
En utilisant le sélecteur de commande, indiquer les 2 liens "Pluie prévue dans l'heure" et "Prévision à 0-5 mn".
* Renseigner ensuite la section "Calendrier de fonctionnement".
Il est possible de définir 2 plages horaires par jour, pour chaque jour de la semaine.
(La zone "Initialisation plage horaire 1 ou 2" permet de remplir plus rapidement les informations hebdomadaires en recopiant une même défition sur chaque jour de la semaine).
Pour chaque plage horaire, on peux associer une zone de tonte, avec un chiffre entre 1 et 3:
  * 1: Zone de tonte 1, associée à la commande d'activation zone 1
  * 2: Zone de tonte 2, associée à la commande d'activation zone 2
  * 3: Alternance des zones 1 et 2, selon le pourcentage défini précédemment.
  
<p align="left">
  <img src="../master/doc/images/installation_2.png" width="600" title="Configuration Planification">
</p>

**Détails sur la planification: gestion de 2 zones de fonctionnement**
Les 2 zones de fonctionnement sont un artifice d'installation du robot husqvarna pour améliorer ses possibilités, qui consiste à passer 2 jeux de câbles (câbles périphérique et câble guide) et de commuter par relai ces 2 jeux de câbles entre 2 départ du robot depuis sa base.
En pratique, cela permet de gérer l'équivalent de 2 câbles guides pour un modèle de robot qui n'en a qu'un seul.<br>
Voir des explications plus détaillées sur le forum "automower-fans":<br>
http://automower-fans.les-forums.com/topic/5750/tutoriel-installation-2-zones-alternees/

Les commandes jeedom à définir sur la page de configuration permettent de sélectionner le relai sur une zone ou sur l'autre, en activant le relai On ou Off.
La zone 1 est considérée par le plugin comme la zone ou le relai est Off, car elle est activée lorsque le robot est à sa base. (cela économise un peu de courant)

**Détails sur la planification: option météo**
Le couplage de la planification avec le plugin Météo France permet de suspendre le fonctionnement du robot si de la pluie est prévue dans l'heure qui suit, selon les 2 principes suivants:<br>
Pour rappel, le plugin météo / prévision dans l'heure fourni une probabilité de pluie entre 1 et 4 par tranche de 5 mn, dans l'heure qui vient. (1: pas de pluie à 4:pluie forte)
* Si le robot est à sa base et que la quantité de pluie dans l'heure qui vient est supérieure à 18, le robot suspend le cycle de tonte. (12 correspond à aucune pluie prévue dans l'heure)
* Si le robot est dans un cycle de tonte et que la quantité de pluie dans les 15 mn qui suivent est supérieure à 6, le robot rentre à sa base. (3 correspond à aucune pluie prévue dans les 15 mn)

**Remarque sur la planification:**
La planification du plugin est complémentaire à celle intégrée dans le robot.
Il faut donc que les plages de fonctionnements du robot incluent celle du plugin.
(Une façon simple de faire est de configurer le robot en H24, 7j/7, et c'est dans ce cas la planification du plugin qui est 
prise en compte)

## Finalisation du Widget
* Organiser les infos et commandes du plugins sur le dashboard jeedom (par exemple comme dans l'image plus haut)

## Panel
Une page de type "panel" est disponible pour le plugin dans le menu Acceuil de jeedom.
Cette page permet de consulter des informations d'historique d'utilisation du robot.
(Le plugin maintient un fichier de log dans le dossier data, qui mémorise les positions et mode de fonctionnement du robot)

Il est possible de définir une période soit par 2 dates, soit par des racourcis ('Aujourd'hui', 'hier', 'les 7 derniers jours' ou 'tout'), puis d'afficher l'ensemble des positions du robot mémorisées sur cette période. <br>
(Il y a une position mémorisée toute les minutes.)

Cet affichage permet de visualiser si le robot couvre uniformément l’espace à tondre.
Il y a 2 modes d'affichage proposés selon les boutons lignes ou cercles.
Soit des traits pointillés entre chaque position, soit des cercles partiellement transparents.
Ce second mode d'affichage permet de mieux voir les zones tondues ou non sur des grandes quantités de points.

<p align="left">
  <img src="../master/doc/images/pannel_1.png" width="600" title="Configuration Planification">
</p>

Cette page permet également en sélectionnant le bouton "Config Robot" de lire la configuration courante du robot, et de l'afficher.
L'affichage est relativement "brut", et uniquement pour information.

<p align="left">
  <img src="../master/doc/images/pannel_2.png" width="450" title="Configuration Planification">
</p>
