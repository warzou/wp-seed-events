# WP Seed Events 0.2.0-beta.4

Cette beta corrective fiabilise le module Divi 5 Visuels de communication dans les boucles d'evenements. Chaque instance resout desormais l'evenement courant de son item, sans repli vers la page porteuse ni fuite depuis un autre item.

## Correctif Divi

- contexte canonique `loop_post_id` reutilise par le frontend et l'apercu serveur ;
- collections visuelles propres a chaque evenement ;
- option Autres visuels respectee ;
- deux modules independants sur une meme page ;
- sortie vide sans wrapper pour un evenement sans visuel ;
- contexte hors boucle et confidentialite existants inchanges.

La recette de reference utilise WordPress 7.0.2 et Divi 5.9.0. Elle couvre deux evenements avec des collections distinctes, un evenement sans visuel, la route d'apercu, le frontend et les largeurs 1440, 820, 390 et 320 px, sans erreur console, PHP, REST ou SQL.

## Installation ou mise a jour

1. Sauvegarder la base WordPress et le dossier plugin actif.
2. Verifier `wp-seed-events-0.2.0-beta.4.zip.sha256`.
3. Installer `wp-seed-events-0.2.0-beta.4.zip`, ou utiliser `Mettre a jour maintenant` depuis une beta precedente.
4. Confirmer la version active `0.2.0-beta.4`.
5. Verifier une fiche evenement et une boucle Divi utilisant le module Visuels de communication.

Le ZIP est runtime-only. La documentation reste disponible dans le depot GitHub et n'est pas installee dans `wp-content/plugins`.

## Compatibilite et donnees

- aucune migration du stockage metier ;
- aucun changement du contrat Media ou Event Data ;
- aucune creation de Promotion ou Seminaire reel ;
- Gutenberg et Divi restent facultatifs ;
- aucune dependance a WP Seed Content Kit ou Spectra.

Consulter aussi :

- `docs/DIVI-EVENT-VISUALS-MODULE.md` ;
- `docs/PUBLIC-EVENT-VISUALS.md` ;
- `docs/USER-GUIDE-BETA.md` ;
- `docs/MIGRATION-AND-ROLLBACK.md` ;
- `docs/KNOWN-LIMITATIONS-BETA.md` ;
- `docs/GITHUB-UPDATES.md`.

## Artefacts officiels

- `wp-seed-events-0.2.0-beta.4.zip` : 179860 octets ;
- SHA-256 : `084E3D6E6440DDE19329039E8DDBE8AC432DA515A7BDC554F3EBEB2277A10325` ;
- checksum : `wp-seed-events-0.2.0-beta.4.zip.sha256`.
