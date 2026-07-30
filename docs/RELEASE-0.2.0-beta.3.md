# WP Seed Events 0.2.0-beta.3

Cette beta livre les fondations Promotion et lifecycle v3, les collections d'occurrences plates ou groupees, leurs integrations Gutenberg et Divi, ainsi que l'updater WordPress natif. Elle preserve les 29 evenements historiques, leurs occurrences canoniques, les URL, les shortcodes et les integrations deja publiees.

## Promotion et lifecycle v3

- Promotions et annees du parcours additives ;
- aucun Seminaire reel cree automatiquement ;
- projection SQL reconstructible depuis les occurrences canoniques ;
- backfill borne, reprenable et idempotent ;
- validation d'integrite obligatoire avant `ready=true`.

## Collections d'occurrences

- API PHP et REST plate ou groupee ;
- filtres Promotion, annee, evenement, type, statut, annulation et dates ;
- contexte occurrence composite isole ;
- bloc Gutenberg a modele enfant editable ;
- module Divi 5 plat ou groupe, avec pagination par instance ;
- aide `Format : AAAA-MM-JJ` dans les filtres Divi.

## Updater WordPress natif

- liens `Afficher les details` et `Verifier les mises a jour` ;
- continuation du canal prerelease pour une installation beta ;
- ZIP officiel et `.sha256` obligatoires ;
- controle taille, SHA-256, racine, version et traversal ;
- dossier final `wp-seed-events/` et activation preserves ;
- aucune mise a jour automatique en arriere-plan.

## Installation ou mise a jour

1. Sauvegarder la base WordPress et le dossier plugin actif.
2. Verifier `wp-seed-events-0.2.0-beta.3.zip.sha256`.
3. Installer `wp-seed-events-0.2.0-beta.3.zip`, ou utiliser `Mettre a jour maintenant` depuis `0.2.0-beta.2`.
4. Confirmer la version active `0.2.0-beta.3`.
5. Executer le backfill lifecycle v3 puis confirmer `ready=true`.
6. Recetter une fiche, une collection, REST, ICS et les builders utilises.

Le ZIP est runtime-only. La documentation reste disponible dans le depot GitHub et n'est pas installee dans `wp-content/plugins`.

## Compatibilite et limites

- aucune migration du stockage metier ;
- aucune creation de Promotion ou Seminaire reel ;
- Gutenberg et Divi restent facultatifs ;
- aucune dependance a WP Seed Content Kit ou Spectra ;
- patterns Gutenberg non synchronises par defaut ;
- certains apercus Divi restent neutres hors contexte compatible.

Consulter aussi :

- `docs/USER-GUIDE-BETA.md` ;
- `docs/MIGRATION-AND-ROLLBACK.md` ;
- `docs/KNOWN-LIMITATIONS-BETA.md` ;
- `docs/GITHUB-UPDATES.md` ;
- `docs/PROMOTION-DOMAIN-API.md` ;
- `docs/OCCURRENCE-PROJECTION-LIFECYCLE-V3.md` ;
- `docs/OCCURRENCE-COLLECTIONS.md` ;
- `docs/GUTENBERG-OCCURRENCE-COLLECTIONS.md` ;
- `docs/DIVI-OCCURRENCE-COLLECTIONS.md`.

## Artefacts officiels

- `wp-seed-events-0.2.0-beta.3.zip` : 179580 octets ;
- SHA-256 : `9CFACC6F7009ED6CB9AA3CDE013D949146AC4A1EA10FBF80E96F225C91FAD16A` ;
- checksum : `wp-seed-events-0.2.0-beta.3.zip.sha256`.
