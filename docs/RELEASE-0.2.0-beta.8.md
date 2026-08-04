# WP Seed Events 0.2.0-beta.8

Cette beta corrective fiabilise les filtres de collections d'evenements dans
plusieurs instances de boucle Divi 5.9.0.

## Correctif

- prise en charge du format Divi categorise `selectedOptions` ;
- selection vide interpretee comme tous les types ;
- plusieurs types combines par une seule clause native `IN`, donc en OR ;
- aucun filtre `featured` lorsque **Epinglage** vaut **Tous** ;
- injection avant le cache Divi via `divi_loop_data_before_execution` ;
- parite entre section Hero et section standard ;
- pastilles limitees au libelle du terme dans le controle dedie.

La recette reelle valide les types **Journee decouverte** et
**Reunion d'information**, avec les evenements 2414 et 2417.

## Installation ou mise a jour

1. Sauvegarder la base WordPress et le dossier plugin actif.
2. Verifier `wp-seed-events-0.2.0-beta.8.zip.sha256`.
3. Installer `wp-seed-events-0.2.0-beta.8.zip`, ou utiliser la mise a jour
   native depuis une beta precedente.
4. Confirmer la version active `0.2.0-beta.8`.
5. Verifier les filtres de types et d'epinglage dans les boucles Divi.

Le ZIP est runtime-only. La documentation reste disponible dans le depot GitHub.

## Compatibilite et donnees

- WordPress 7.0.2 ;
- PHP 8.4 et PHP 8.5 ;
- Divi 5.9.0 ;
- Lifecycle V4 inchange ;
- aucune migration de donnees metier ;
- aucun contournement dans le theme ou le site consommateur.

## Documentation

- `docs/DIVI-EVENT-COLLECTIONS.md` ;
- `docs/NATIVE-EVENT-CLASSIFICATIONS.md` ;
- `docs/GITHUB-UPDATES.md` ;
- `docs/MIGRATION-AND-ROLLBACK.md`.
## Artefacts officiels

- `wp-seed-events-0.2.0-beta.8.zip` : 190 451 octets ;
- entrees : 68 ;
- SHA-256 : `15751824622EC9CB3B9D630ACD0F5F4B426A9BB8A7752021EFDF25586A3F64AE` ;
- checksum : `wp-seed-events-0.2.0-beta.8.zip.sha256`.
