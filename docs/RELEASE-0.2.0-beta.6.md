# WP Seed Events 0.2.0-beta.6

Cette beta corrective affiche le vrai visuel dynamique d'un evenement dans le module Image natif du Visual Builder Divi 5. Le frontend et les liens vers les fiches evenements restent inchanges.

## Correctif Divi

- resolution stricte de l'item courant avec le store de boucle public Divi ;
- URL publique scalaire transmise aux attributs runtime du module Image natif ;
- aucun fallback vers le post global ni reutilisation de l'item precedent ;
- valeur vide pour un evenement prive, incompatible ou sans visuel ;
- deux boucles et deux images distinctes sans fuite de contexte ;
- aucune modification du module Image Divi, du theme, du stockage ou du contrat Event Data.

La recette de reference utilise WordPress 7.0.2, PHP 8.4.21 et Divi 5.9.0. Le Visual Builder et le frontend affichent les memes images, sans erreur console, REST, PHP ou SQL.

## Installation ou mise a jour

1. Sauvegarder la base WordPress et le dossier plugin actif.
2. Verifier `wp-seed-events-0.2.0-beta.6.zip.sha256`.
3. Installer `wp-seed-events-0.2.0-beta.6.zip`, ou utiliser `Mettre a jour maintenant` depuis une beta precedente.
4. Confirmer la version active `0.2.0-beta.6`.
5. Verifier une boucle Divi utilisant la donnee dynamique Visuel de communication dans un module Image natif.

Le ZIP est runtime-only. La documentation reste disponible dans le depot GitHub et n'est pas installee dans `wp-content/plugins`.

## Compatibilite et donnees

- aucune migration du stockage metier ;
- aucun changement des evenements, occurrences, projections ou medias ;
- Lifecycle V3 et collections publiques inchanges ;
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

- `wp-seed-events-0.2.0-beta.6.zip` : 183080 octets ;
- entrees : 66 ;
- SHA-256 : `FE6A44D3F57E1D821EEC2CBB3A9A9F8DD50E722AED26493535F3400291FCC7DA` ;
- checksum : `wp-seed-events-0.2.0-beta.6.zip.sha256`.