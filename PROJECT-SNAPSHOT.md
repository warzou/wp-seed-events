# Project Snapshot

## Projet

Nom : WP Seed Events

Nature : plugin WordPress autonome, à concevoir puis développer progressivement.

Etat actuel : dépôt initialisé pour cadrage produit uniquement.

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
- logique WordPress écrite avant validation de conception.

## Etat du dépôt

Ce dépôt ne contient pour l'instant que des fichiers de cadrage.

Il ne contient pas :

- de fichier plugin principal ;
- de code PHP fonctionnel ;
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

