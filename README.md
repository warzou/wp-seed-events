# WP Seed Events

WP Seed Events est un futur plugin WordPress autonome dédié au métier évènement.

Le plugin devra pouvoir fonctionner seul. Il pourra détecter WP Seed Content Kit et
s'y connecter de manière optionnelle, mais ne devra jamais en dépendre pour ses
fonctions essentielles.

## Vision produit

WP Seed Events vise à fournir une base simple, robuste et portable pour publier
des évènements dans WordPress :

- évènements ;
- occurrences ;
- lieux ;
- médias ;
- QR Code ;
- export ICS ;
- statut temporel ;
- rendu public minimal.

L'objectif est de couvrir correctement le coeur métier avant toute ambition de
présentation avancée.

## Architecture retenue

L'architecture validée est l'Option C :

- plugin autonome ;
- domaine évènement indépendant ;
- intégration optionnelle avec WP Seed Content Kit si celui-ci est détecté ;
- aucune dépendance obligatoire à WP Seed Content Kit ;
- rendu public minimal intégré ;
- séparation nette entre métier évènement et moteurs d'affichage avancés.

## Ce que le projet refuse

WP Seed Events ne doit pas devenir :

- un moteur de templates avancé ;
- une couche Divi avancée ;
- un constructeur d'affichage ;
- une extension dépendante de WP Seed Content Kit ;
- un projet qui mélange prématurément logique métier et logique de présentation.

## Statut actuel

Le dépôt est en phase d'initialisation supervisée.

Aucun code plugin n'existe encore. Aucun fichier d'entrée WordPress
`wp-seed-events.php` ne doit être créé à ce stade.

La prochaine étape est la conception produit détaillée, pas l'implémentation.

