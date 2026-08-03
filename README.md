# WP Seed Events

Version actuelle : `0.2.0-beta.6`

WP Seed Events est un plugin WordPress autonome pour administrer, publier et composer des evenements a occurrences multiples. Cette beta livre les Promotions, le lifecycle v3, les collections d'occurrences Gutenberg et Divi ainsi que l'updater WordPress natif, sans modifier le stockage canonique ni les contenus existants.

## Fonctionnalites principales

- evenements, occurrences, lieux, medias et personnes ;
- lifecycle admin, collections publiques et URLs canoniques ;
- exports ICS et partage minimal ;
- visuels de communication et document PDF complementaire ;
- coordonnees Personnes privees par defaut ;
- templates et shortcodes de compatibilite ;
- Dynamic Data public, incluant le statut de l'evenement ;
- modules Divi 5 Dates, Visuels, Personnes et Collection d’occurrences ;
- blocs Gutenberg Dates, Visuels et Personnes ;
- collections metier dans le Loop Builder Divi et le Query Loop Gutenberg ;
- collections publiques plates et groupees d'occurrences, avec filtres Promotion et annee du parcours ;
- filtres type, statut et epinglage, tri par `1re date de l'evenement` et pagination ;
- patterns Gutenberg `Carte compacte` et `Carte detaillee` modifiables librement.

## Statut beta

Cette version est destinee a la stabilisation avant 1.0. Une sauvegarde des fichiers et de la base WordPress reste recommandee avant installation. Les mises a jour officielles peuvent etre detectees depuis GitHub. La ligne Extensions fournit les details et une verification manuelle ; WordPress propose la mise a jour en un clic lorsqu'une release admissible, son ZIP officiel et son SHA-256 sont disponibles. Aucune mise a jour automatique en arriere-plan n'est activee.

## Prerequis et surfaces supportees

Le plugin respecte les APIs WordPress et fonctionne sans builder obligatoire. La recette de reference a ete realisee avec WordPress 7.0.2, PHP 8.4 et Divi 5.9.0. Les tests automatises sont egalement executes sous PHP 8.5.

Surfaces validees :

- administration et rendu WordPress natifs ;
- Gutenberg, Query Loop Core et patterns de collection ;
- Divi 5 et Loop Builder ;
- shortcodes comme fallback universel.

Astra et Spectra restent facultatifs. Spectra n'est pas installe sur le site de reference, aucun bloc Spectra n'est inclus dans les patterns officiels et aucune compatibilite runtime avancee Spectra n'est revendiquee par cette beta.

## Installation

1. Sauvegarder les fichiers et la base WordPress.
2. Installer `wp-seed-events-0.2.0-beta.6.zip` depuis Extensions > Ajouter une extension.
3. Activer WP Seed Events.
4. Ouvrir une fiche evenement puis verifier son rendu public.

## Mise a jour depuis une alpha

1. Conserver une copie exacte du dossier actif `wp-seed-events`.
2. Remplacer le plugin avec le ZIP `wp-seed-events-0.2.0-beta.6.zip`.
3. Verifier la version active, une fiche evenement, les collections, les ICS et les builders utilises.

Le changement de version ne declenche aucune migration metier, republication de coordonnee ou modification des slugs existants. Beta.3 reconstruit uniquement les projections techniques du lifecycle v3 depuis les occurrences canoniques.

## Rollback

Reinstaller la copie exacte du dossier plugin anterieur. Les evenements, metas et reglages restent dans la base WordPress et ne sont pas inclus dans le ZIP du plugin. Verifier ensuite la version active, REST, wp-admin et une fiche publique.

## Recette rapide

- ouvrir l'administration d'un evenement ;
- ouvrir une fiche publique et un export ICS ;
- verifier une collection, son tri et sa pagination ;
- verifier les modules ou blocs builders utilises ;
- confirmer qu'aucune coordonnee Personnes non autorisee n'est visible.

## Retours sur la beta

- creer une issue par symptome distinct et verifier les doublons avant creation ;
- ne jamais publier de secret ni de donnee personnelle ;
- indiquer la version testee dans le champ dedie ;
- donner la priorite aux anomalies reproductibles ;
- aucune nouvelle fonctionnalite n'est garantie pendant la stabilisation beta.

## Documentation beta

- [Guide utilisateur beta](docs/USER-GUIDE-BETA.md) : parcours utilisateur complet ;
- [Migration et rollback](docs/MIGRATION-AND-ROLLBACK.md) : installation, mise a jour et restauration ;
- [Limites connues](docs/KNOWN-LIMITATIONS-BETA.md) : limites classees par jalon ;
- [Mises a jour GitHub](docs/GITHUB-UPDATES.md) : interface native, canaux, integrite, cache, erreurs et rollback.

## Limites connues

- les patterns Gutenberg sont non synchronises par defaut ;
- certains apercus Divi restent neutres hors contexte evenement ;
- l'index lifecycle v3 doit etre reconstruit avant d'activer ses projections SQL optimisees ;
- Spectra n'est ni requis ni recete sur le site de reference ;
- la boite native Image mise en avant est masquee pour les evenements ; le premier visuel reste synchronise techniquement ;
- placement du partage dependant du theme en page modele complete.

## Developpeurs

- [Classifications natives et tri](docs/NATIVE-EVENT-CLASSIFICATIONS.md)
- [Event Data API](docs/EVENT-DATA-API.md)
- [Event Occurrences API](docs/EVENT-OCCURRENCES-API.md)
- [Occurrence Collections](docs/OCCURRENCE-COLLECTIONS.md)
- [Gutenberg Occurrence Collections](docs/GUTENBERG-OCCURRENCE-COLLECTIONS.md)
- [Divi Occurrence Collections](docs/DIVI-OCCURRENCE-COLLECTIONS.md)
- [Domain Model](DOMAIN-MODEL.md)
- [Occurrence Projection and Lifecycle V3](docs/OCCURRENCE-PROJECTION-LIFECYCLE-V3.md)
- [Promotions et annees du parcours](docs/PROMOTION-DOMAIN-API.md)
- [Collections publiques](docs/PUBLIC-COLLECTIONS.md)
- [Dynamic Data](docs/PUBLIC-EVENT-DYNAMIC-DATA.md)
- [Compatibilite et deprecations](docs/PUBLIC-API-COMPATIBILITY.md)

Voir [la note de release beta.6](docs/RELEASE-0.2.0-beta.6.md) et [le changelog](CHANGELOG.md) pour les details de release.
