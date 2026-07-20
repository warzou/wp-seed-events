# WP Seed Events 0.2.0-alpha.1

## Statut

Premiere alpha identifiable de la V1 fonctionnellement terminee. Elle est destinee a la validation avant beta et stable.

Une sauvegarde des fichiers et de la base WordPress est recommandee avant toute installation. La compatibilite ascendante totale n'est pas garantie avant une version stable.

## Environnement de reference

- WordPress 7.0.2 ;
- PHP 8.4.21 ;
- Divi 5.9.0 ;
- Gutenberg natif ;
- Astra et Spectra valides sans adaptateur metier.

Divi, Astra et Spectra restent optionnels. Le plugin fonctionne avec WordPress natif.

## Installation neuve

1. Sauvegarder le site.
2. Installer `wp-seed-events-0.2.0-alpha.1.zip` depuis l'administration WordPress.
3. Activer le plugin.
4. Creer un evenement de test et verifier la fiche publique.

## Mise a jour depuis 0.1.23-dev

1. Archiver exactement le dossier plugin actif.
2. Installer le ZIP alpha par remplacement du plugin.
3. Verifier que la version active est `0.2.0-alpha.1`.
4. Controler une fiche, les collections, les ICS, le partage et les builders utilises.

La mise a jour ne comporte aucune migration, aucun changement de schema, aucun backfill automatique et aucune activation de coordonnee Personnes.

## Rollback

1. Remplacer le dossier alpha par l'archive exacte du runtime precedent.
2. Confirmer le retour a `0.1.23-dev`.
3. Verifier accueil, REST, wp-admin et une fiche evenement.

Le rollback du plugin ne supprime ni ne restaure la base WordPress. Les donnees doivent etre protegees par la sauvegarde generale du site.

## Surfaces a recetter

- administration Evenements ;
- fiche publique et page modele ;
- collections et etats vides ;
- ICS individuel et multi-occurrences ;
- partage ;
- shortcodes ;
- Dynamic Data ;
- Divi Dates, Visuels et Personnes ;
- Gutenberg Dates, Visuels et Personnes ;
- Query Loop et Loop Builder ;
- confidentialite des coordonnees Personnes.

## Limites connues

- la boite native Image mise en avant coexiste avec Visuels de communication ;
- le cas d'un slug accentue degrade reste a reproduire et a isoler ;
- le placement du partage en page modele complete depend du theme.

Ces limites sont non bloquantes pour l'alpha et ne sont pas corrigees dans ce lot de release.
