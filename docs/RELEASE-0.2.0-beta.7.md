# WP Seed Events 0.2.0-beta.7

Cette beta corrective rend les classifications natives d'événements visibles dans les constructeurs fondés sur le registre WordPress, notamment Divi 5.9.0 et Gutenberg.

## Correctif

- `wp_seed_event_type` et `wp_seed_event_flag` utilisent `public=true` ;
- `publicly_queryable=false`, `rewrite=false` et `show_in_rest=true` restent inchangés ;
- aucune archive publique ni métabox technique supplémentaire ;
- quatre types réels et `featured` disponibles dans les filtres Divi ;
- `tax_query`, REST, Gutenberg et le tri `1re date de l'événement` préservés ;
- Lifecycle V4 et les données historiques restent inchangés.

## Installation ou mise à jour

1. Sauvegarder la base WordPress et le dossier plugin actif.
2. Vérifier `wp-seed-events-0.2.0-beta.7.zip.sha256`.
3. Installer `wp-seed-events-0.2.0-beta.7.zip`, ou utiliser la mise à jour native depuis une beta précédente.
4. Confirmer la version active `0.2.0-beta.7`.
5. Vérifier les filtres taxonomiques et le tri métier dans les constructeurs utilisés.

Le ZIP est runtime-only. La documentation reste disponible dans le dépôt GitHub.

## Compatibilité et données

- WordPress 7.0.2 ;
- PHP 8.4 et PHP 8.5 ;
- Divi 5.9.0 ;
- aucune migration supplémentaire après Lifecycle V4 ;
- aucun changement des événements, occurrences, projections ou coordonnées ;
- aucun contournement dans le thème ou le site consommateur.

## Documentation

- `docs/NATIVE-EVENT-CLASSIFICATIONS.md` ;
- `docs/DIVI-EVENT-COLLECTIONS.md` ;
- `docs/GUTENBERG-EVENT-COLLECTIONS.md` ;
- `docs/GITHUB-UPDATES.md` ;
- `docs/MIGRATION-AND-ROLLBACK.md`.

## Artefacts officiels

- `wp-seed-events-0.2.0-beta.7.zip` : 186 826 octets ;
- entrées : 67 ;
- SHA-256 : `2402AAC9028A01CAA6DBA47D2667561F187E82F87D738F43B1F483F4A868B23B` ;
- checksum : `wp-seed-events-0.2.0-beta.7.zip.sha256`.
