# WP Seed Events 0.2.0-alpha.2

## Objet

Cette preversion ajoute des collections d'evenements composables dans Divi et Gutenberg, deux patterns Gutenberg de depart et des controles plus fins pour les Dates et les Personnes. Elle ne modifie ni le stockage, ni les autorisations existantes, ni les donnees WordPress.

## Nouveautes

- collections metier Divi et Gutenberg avec filtres type, statut et epinglage ;
- tri visible `1re date de l'evenement`, fonde sur les occurrences actives ;
- pagination Gutenberg et ordre stable ;
- patterns `Carte compacte` et `Carte detaillee` ;
- six choix explicites pour les dates affichees ;
- horaires, occurrences annulees, format et liens calendrier configurables ;
- roles Personnes multiples en logique OU ;
- visibilite et liens cliquables des coordonnees controles separement ;
- apercu Gutenberg et Block Bindings ameliores ;
- statut public disponible dans Dynamic Data.

## Compatibilite

- les shortcodes et alias de `0.2.0-alpha.1` sont conserves ;
- les permissions de publication des coordonnees restent prioritaires ;
- aucune migration, aucun backfill et aucune republication automatique ;
- Divi et Gutenberg sont facultatifs ;
- Content Kit et Spectra ne sont pas requis ;
- Spectra n'est pas installe sur le site de reference, aucun bloc Spectra n'est inclus dans les patterns officiels et aucune compatibilite runtime avancee n'est revendiquee.

## Installation

1. Sauvegarder la base WordPress et le dossier actif du plugin.
2. Installer `wp-seed-events-0.2.0-alpha.2.zip` depuis l'administration WordPress.
3. Verifier que la version active est `0.2.0-alpha.2`.
4. Controler une fiche evenement, une collection, un export ICS et les builders utilises.

## Rollback

Reinstaller la sauvegarde exacte du dossier plugin precedent. Le ZIP ne contient aucune donnee WordPress ; les evenements, metas et reglages restent dans la base.

## Limites connues

- patterns Gutenberg non synchronises par defaut ;
- certains apercus Divi neutres hors contexte evenement ;
- performance a mesurer avant beta sur de tres gros catalogues ;
- Spectra non recete et non requis.
