# WP Seed Events 0.2.0-beta.9

Cette beta corrective rend les controles dedies des filtres Divi Events seuls responsables des types et de l'epinglage.

## Correctif

- **Types d'evenement** est la source unique de `wp_seed_event_type` ; plusieurs types sont combines en OR ;
- **Epinglage** est la source unique de `wp_seed_event_flag=featured` ;
- types et epinglage se combinent avec une logique AND ;
- les taxonomies Events sont retirees du selecteur natif Divi, sans retirer les autres taxonomies ;
- les anciennes boucles sont migrees lors de leur sauvegarde, de facon idempotente et compatible avec le slashing WordPress ;
- la page reelle 1901 conserve les resultats 2414 et 2417 dans le Hero et sur le frontend.

## Installation ou mise a jour

1. Sauvegarder la base WordPress et le dossier plugin actif.
2. Verifier `wp-seed-events-0.2.0-beta.9.zip.sha256`.
3. Installer `wp-seed-events-0.2.0-beta.9.zip`, ou utiliser la mise a jour native depuis une beta precedente.
4. Confirmer la version active `0.2.0-beta.9`.
5. Verifier les filtres de types et d'epinglage dans les boucles Divi existantes.

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

- `wp-seed-events-0.2.0-beta.9.zip` : 192 755 octets ;
- entrees : 68 ;
- SHA-256 : `E175D7C4396E6A57793F8CE5A2008DD98E9448A5FF5C5B98CCD315D43DB84D16` ;
- checksum : `wp-seed-events-0.2.0-beta.9.zip.sha256`.
