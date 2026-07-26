# WP Seed Events 0.2.0-beta.1

Premiere beta de la V1 de WP Seed Events. Cette version consolide les parcours
valides pendant les alphas et fige les contrats publics Collections, Dates et
Personnes. Elle ne modifie pas le stockage et ne migre aucune URL existante.

## Nouveautes principales

- collections metier composables dans Divi et Gutenberg ;
- pagination et tri par `1re date de l'evenement` ;
- patterns Gutenberg `Carte compacte` et `Carte detaillee` ;
- six choix explicites de dates ;
- controles avances des Personnes et confidentialite preservee ;
- synchronisation simplifiee entre le premier visuel et l'image principale ;
- reconnaissance des slugs localises des nouveaux auto-brouillons ;
- guide utilisateur, migration et rollback documentes.

## Compatibilite

- contrats et shortcodes historiques preserves ;
- aucune dependance a WP Seed Content Kit ;
- Spectra non requis ;
- Divi et Gutenberg facultatifs ;
- aucune migration automatique des anciens slugs `brouillon-auto-*` ;
- aucune republication automatique de coordonnee Personnes.

## Installation ou mise a jour

1. Sauvegarder les fichiers WordPress et la base de donnees.
2. Verifier le SHA-256 du ZIP officiel.
3. Installer `wp-seed-events-0.2.0-beta.1.zip`.
4. Confirmer que la version active est `0.2.0-beta.1`.
5. Verifier l'etat lifecycle, une fiche, une collection, un ICS et les builders
   utilises.

Le changement de version n'execute aucune migration automatique. Le premier
visuel reste projete dans `_thumbnail_id`, tandis que la metabox WordPress
native Image mise en avant est masquee uniquement pour `wp_seed_event`.

## Rollback

Conserver avant installation une copie exacte du dossier plugin actif et une
sauvegarde de la base. En cas d'echec, restaurer atomiquement le dossier
precedent, puis verifier version, REST, wp-admin, fiche publique, collections,
ICS, builders, lifecycle et confidentialite.

## Limites connues

- performance des collections a surveiller autour de 250 evenements et a
  optimiser avant de recommander un gros catalogue ;
- aucun mecanisme d'auto-update ;
- patterns Gutenberg non synchronises par defaut ;
- certains apercus Divi neutres hors contexte evenement ;
- Spectra non recete et non requis.

Consulter aussi :

- `docs/USER-GUIDE-BETA.md` ;
- `docs/MIGRATION-AND-ROLLBACK.md` ;
- `docs/KNOWN-LIMITATIONS-BETA.md` ;
- `CHANGELOG.md`.
