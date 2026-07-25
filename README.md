# WP Seed Events

Version actuelle : `0.2.0-alpha.3`

WP Seed Events est un plugin WordPress autonome pour administrer, publier et composer des evenements a occurrences multiples. Cette version alpha corrige la generation initiale des slugs dans les interfaces WordPress localisees, sans migration des URL existantes.

## Fonctionnalites principales

- evenements, occurrences, lieux, medias et personnes ;
- lifecycle admin, collections publiques et URLs canoniques ;
- exports ICS et partage minimal ;
- visuels de communication et document PDF complementaire ;
- coordonnees Personnes privees par defaut ;
- templates et shortcodes de compatibilite ;
- Dynamic Data public, incluant le statut de l'evenement ;
- modules Divi 5 Dates, Visuels et Personnes ;
- blocs Gutenberg Dates, Visuels et Personnes ;
- collections metier dans le Loop Builder Divi et le Query Loop Gutenberg ;
- filtres type, statut et epinglage, tri par `1re date de l'evenement` et pagination ;
- patterns Gutenberg `Carte compacte` et `Carte detaillee` modifiables librement.

## Statut alpha

Cette version est destinee a la validation. Une sauvegarde des fichiers et de la base WordPress est recommandee avant installation. La compatibilite ascendante totale n'est pas garantie avant une version stable.

## Prerequis et surfaces supportees

Le plugin respecte les APIs WordPress et fonctionne sans builder obligatoire. La recette de reference a ete realisee avec WordPress 7.0.2, PHP 8.4 et Divi 5.9.0. Les tests automatises sont egalement executes sous PHP 8.5.

Surfaces validees :

- administration et rendu WordPress natifs ;
- Gutenberg, Query Loop Core et patterns de collection ;
- Divi 5 et Loop Builder ;
- shortcodes comme fallback universel.

Astra et Spectra restent facultatifs. Spectra n'est pas installe sur le site de reference, aucun bloc Spectra n'est inclus dans les patterns officiels et aucune compatibilite runtime avancee Spectra n'est revendiquee par cette alpha.

## Installation

1. Sauvegarder les fichiers et la base WordPress.
2. Installer `wp-seed-events-0.2.0-alpha.3.zip` depuis Extensions > Ajouter une extension.
3. Activer WP Seed Events.
4. Ouvrir une fiche evenement puis verifier son rendu public.

## Mise a jour depuis une alpha anterieure

1. Conserver une copie exacte du dossier actif `wp-seed-events`.
2. Remplacer le plugin avec le ZIP `wp-seed-events-0.2.0-alpha.3.zip`.
3. Verifier la version active, une fiche evenement, les collections, les ICS et les builders utilises.

Le changement de version ne declenche aucune migration, aucun backfill, aucune republication de coordonnee et aucune modification des slugs existants.

## Rollback

Reinstaller la copie exacte du dossier plugin anterieur. Les evenements, metas et reglages restent dans la base WordPress et ne sont pas inclus dans le ZIP du plugin. Verifier ensuite la version active, REST, wp-admin et une fiche publique.

## Recette rapide

- ouvrir l'administration d'un evenement ;
- ouvrir une fiche publique et un export ICS ;
- verifier une collection, son tri et sa pagination ;
- verifier les modules ou blocs builders utilises ;
- confirmer qu'aucune coordonnee Personnes non autorisee n'est visible.

## Retours sur l'alpha

- creer une issue par symptome distinct et verifier les doublons avant creation ;
- ne jamais publier de secret ni de donnee personnelle ;
- indiquer la version testee dans le champ dedie ;
- donner la priorite aux anomalies reproductibles ;
- aucune nouvelle fonctionnalite n'est garantie pendant l'alpha.

## Limites connues

- les patterns Gutenberg sont non synchronises par defaut ;
- certains apercus Divi restent neutres hors contexte evenement ;
- la performance des collections devra etre mesuree avant beta sur de tres gros catalogues ;
- Spectra n'est ni requis ni recete sur le site de reference ;
- coexistence avec la boite native Image mise en avant ;
- placement du partage dependant du theme en page modele complete.

Voir `docs/RELEASE-0.2.0-alpha.3.md` et `CHANGELOG.md` pour les details de release.
