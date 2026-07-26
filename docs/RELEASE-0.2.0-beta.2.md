# WP Seed Events 0.2.0-beta.2

Cette beta optimise les collections sur les gros catalogues, ajoute un updater GitHub controle et formalise les contrats publics Event Data et Occurrences. Elle preserve les resultats, les donnees, les URL, les shortcodes et les integrations existantes.

## Collections et lifecycle v2

- selection des IDs indexes avant pagination ;
- hydratation Event Data limitee aux evenements de la page ;
- index lifecycle v2 reconstructible, reprenable et idempotent ;
- projections internes de type et d'occurrences actives ;
- fallback historique exact jusqu'a `ready=true` ;
- ordre, total et pagination inchanges.

Le passage de l'index v1 a v2 ne modifie aucune occurrence, aucun type, aucun contenu public et aucune date de modification des evenements.

## Updater GitHub controle

- depot officiel `warzou/wp-seed-events` uniquement ;
- canal stable par defaut ;
- prereleases sur consentement explicite ;
- ZIP et checksum SHA-256 obligatoires ;
- archive, tag, version interne et racine verifies ;
- cache de six heures, erreurs reseau isolees et multisite ;
- aucune mise a jour automatique en arriere-plan.

## Contrats publics

Les contrats Event Data, Event Occurrences, Collections, renderers, shortcodes, Dynamic Data et builders sont documentes. Les alias historiques restent pris en charge. Les routes d'apercu, options lifecycle, curseurs, verrous et details d'index restent internes.

## Installation ou mise a jour

1. Sauvegarder la base WordPress et le dossier plugin actif.
2. Verifier `wp-seed-events-0.2.0-beta.2.zip.sha256`.
3. Installer `wp-seed-events-0.2.0-beta.2.zip`.
4. Confirmer la version active `0.2.0-beta.2`.
5. Laisser la routine officielle reconstruire l'index lifecycle v2.
6. Confirmer `ready=true`, puis recetter une fiche, une collection, un ICS et les builders utilises.

## Compatibilite

- aucune migration metier ;
- aucun changement de stockage canonique ;
- aucune dependance a WP Seed Content Kit ;
- Spectra non requis ;
- Gutenberg et Divi facultatifs ;
- shortcodes et alias existants conserves.

## Limites connues

- patterns Gutenberg non synchronises par defaut ;
- certains apercus Divi restent neutres hors contexte evenement ;
- surveiller les tres gros catalogues dans leur environnement reel ;
- aucune mise a jour automatique en arriere-plan ;
- anciens slugs historiques non migres.

Consulter aussi :

- `docs/USER-GUIDE-BETA.md` ;
- `docs/MIGRATION-AND-ROLLBACK.md` ;
- `docs/KNOWN-LIMITATIONS-BETA.md` ;
- `docs/GITHUB-UPDATES.md` ;
- `CHANGELOG.md`.
## Artefacts officiels

- `wp-seed-events-0.2.0-beta.2.zip` : 135283 octets ;
- SHA-256 : `0705EBA0B68B70BA5FB3B9D299BDD84D02FCE3E98FB64B6C67FCC1177339FEDE` ;
- checksum : `wp-seed-events-0.2.0-beta.2.zip.sha256`.
