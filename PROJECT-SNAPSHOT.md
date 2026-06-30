# Project Snapshot

## Projet

Nom : WP Seed Events

Nature : plugin WordPress autonome, à développer progressivement.

Etat actuel : dépôt initialisé, documentation de conception consolidée et
bootstrap plugin minimal réalisé.

## Décision structurante

Architecture retenue : Option C.

WP Seed Events portera son propre domaine métier. WP Seed Content Kit pourra être
utilisé plus tard comme partenaire optionnel si présent, mais il ne sera pas une
dépendance obligatoire.

## Périmètre futur inclus

- métier évènement ;
- gestion des occurrences ;
- gestion des lieux ;
- médias liés aux évènements ;
- génération ou association de QR Code ;
- export ICS ;
- statut temporel ;
- rendu public minimal.

## Périmètre exclu

- moteur de templates avancé ;
- logique Divi avancée ;
- constructeur d'affichage ;
- dépendance obligatoire à WP Seed Content Kit ;
- logique WordPress hors lot validé.

## Etat du dépôt

Ce dépôt contient :

- les fichiers de cadrage produit, fonctionnel, architecture et validation ;
- le fichier principal minimal `wp-seed-events.php` créé au Lot 0.

Il ne contient pas encore :

- de fonctionnalité métier ;
- de CPT ;
- de shortcode ;
- d'écran admin ;
- de champs évènement ;
- de dépendance Composer ;
- de dépendance npm ;
- de test runtime ;
- de configuration WordPress ;
- de secret.

## Workflow de supervision

Chaque étape doit être validée humainement avant de passer à la suivante.

Ordre attendu :

1. cadrage produit ;
2. cadrage architecture ;
3. modèle de données conceptuel ;
4. contrats fonctionnels ;
5. squelette plugin minimal ;
6. implémentation incrémentale ;
7. tests et validation WordPress.

Les commits ne doivent pas être créés sans validation humaine explicite.
