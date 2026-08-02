# WP Seed Events 0.2.0-beta.5

Cette beta corrective permet au Visual Builder Divi 5 d'afficher l'apercu reel de la donnee dynamique `WP Seed Events - Visuel de communication` dans une boucle d'evenements. Le frontend et l'editeur utilisent le meme contexte canonique par item.

## Correctif Divi

- variante technique `loop_wp_seed_events_communication_visual` liee a la source image historique ;
- enrichissement borne de `/divi/v1/loop/query-results` pour les seuls items `wp_seed_event` publics ;
- URL canonique propre au visuel de chaque evenement ;
- aucun fallback vers le post global ni reutilisation du visuel de l'item precedent ;
- valeur vide pour un evenement prive, incompatible ou sans visuel ;
- lien dynamique vers la fiche detaillee de l'evenement inchange.

La recette de reference utilise WordPress 7.0.2 et Divi 5.9.0. Elle couvre deux evenements aux visuels distincts, un evenement sans visuel, deux boucles independantes, le Visual Builder et le frontend, sans erreur console, REST, PHP ou SQL.

## Installation ou mise a jour

1. Sauvegarder la base WordPress et le dossier plugin actif.
2. Verifier `wp-seed-events-0.2.0-beta.5.zip.sha256`.
3. Installer `wp-seed-events-0.2.0-beta.5.zip`, ou utiliser `Mettre a jour maintenant` depuis une beta precedente.
4. Confirmer la version active `0.2.0-beta.5`.
5. Verifier une boucle Divi utilisant la donnee dynamique Visuel de communication.

Le ZIP est runtime-only. La documentation reste disponible dans le depot GitHub et n'est pas installee dans `wp-content/plugins`.

## Compatibilite et donnees

- aucune migration du stockage metier ;
- aucun changement du contrat Media ou Event Data ;
- aucun changement des evenements, occurrences, projections ou medias ;
- Gutenberg et Divi restent facultatifs ;
- aucune dependance a WP Seed Content Kit ou Spectra.

Consulter aussi :

- `docs/DIVI-EVENT-VISUALS-MODULE.md` ;
- `docs/PUBLIC-EVENT-DYNAMIC-DATA.md` ;
- `docs/PUBLIC-EVENT-VISUALS.md` ;
- `docs/USER-GUIDE-BETA.md` ;
- `docs/MIGRATION-AND-ROLLBACK.md` ;
- `docs/KNOWN-LIMITATIONS-BETA.md` ;
- `docs/GITHUB-UPDATES.md`.

## Artefacts officiels

- `wp-seed-events-0.2.0-beta.5.zip` : 181198 octets ;
- entrees : 65 ;
- SHA-256 : `6CA2274535ED2022405BC7C72C58AEFE43850CB524DEE8B9D2F2C8EDAD28BD3A` ;
- checksum : `wp-seed-events-0.2.0-beta.5.zip.sha256`.