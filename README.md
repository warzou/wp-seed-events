# WP Seed Events

Version actuelle : `0.2.0-alpha.1`

WP Seed Events est un plugin WordPress autonome pour administrer, publier et composer des evenements a occurrences multiples. Cette version est une alpha de validation de la V1.

## Fonctionnalites principales

- evenements, occurrences, lieux, medias et personnes ;
- lifecycle admin, collections publiques et URLs canoniques ;
- exports ICS et partage minimal ;
- visuels de communication et document PDF complementaire ;
- coordonnees Personnes privees par defaut ;
- templates et shortcodes ;
- Dynamic Data ;
- modules Divi 5 Dates, Visuels et Personnes ;
- blocs Gutenberg Dates, Visuels et Personnes ;
- Query Loop Core et Loop Builder Divi.

## Statut alpha

Cette version est destinee a la validation. Une sauvegarde des fichiers et de la base WordPress est recommandee avant installation. La compatibilite ascendante totale n'est pas garantie avant une version stable.

## Prerequis et surfaces supportees

Le plugin respecte les APIs WordPress et fonctionne sans builder obligatoire. La recette de reference a ete realisee avec WordPress 7.0.2, PHP 8.4.21 et Divi 5.9.0.

Surfaces validees :

- administration et rendu WordPress natifs ;
- Gutenberg et Query Loop Core ;
- Astra et Spectra sans adaptateur metier ;
- Divi 5 et Loop Builder ;
- shortcodes comme fallback universel.

## Installation

1. Sauvegarder les fichiers et la base WordPress.
2. Installer `wp-seed-events-0.2.0-alpha.1.zip` depuis Extensions > Ajouter une extension.
3. Activer WP Seed Events.
4. Ouvrir une fiche evenement puis verifier son rendu public.

## Mise a jour depuis 0.1.23-dev

1. Conserver une copie exacte du dossier actif `wp-seed-events`.
2. Remplacer le plugin avec le ZIP `0.2.0-alpha.1`.
3. Ne lancer aucun backfill sauf besoin explicite indique dans l'administration.
4. Verifier la version active, une fiche evenement, les collections, les ICS et les builders utilises.

Le changement de version ne declenche aucune migration, aucun backfill et aucune republication de coordonnee.

## Rollback

Reinstaller la copie exacte du dossier plugin anterieur. Les evenements, metas et reglages restent dans la base WordPress et ne sont pas inclus dans le ZIP du plugin. Verifier ensuite la version active, REST, wp-admin et une fiche publique.

## Recette rapide

- ouvrir l'administration d'un evenement ;
- verifier l'aide Dates et le panneau Document complementaire ;
- ouvrir une fiche publique et un export ICS ;
- verifier une collection et une page modele ;
- verifier les modules ou blocs builders utilises ;
- confirmer qu'aucune coordonnee Personnes non autorisee n'est visible.

## Retours sur l'alpha

- creer une issue par symptome distinct et verifier les doublons avant creation ;
- ne jamais publier de secret ni de donnee personnelle ;
- indiquer la version concernee : 0.2.0-alpha.1 ;
- donner la priorite aux anomalies reproductibles ;
- aucune nouvelle fonctionnalite n'est garantie pendant l'alpha.

## Limites connues

- coexistence avec la boite native Image mise en avant ;
- cas du slug accentue degrade encore a isoler ;
- placement du partage dependant du theme en page modele complete.

Voir `docs/RELEASE-0.2.0-alpha.1.md` et `CHANGELOG.md` pour les details de release.
