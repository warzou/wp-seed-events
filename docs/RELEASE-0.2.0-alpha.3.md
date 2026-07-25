# WP Seed Events 0.2.0-alpha.3

## Objet

Cette preversion corrige la generation initiale du slug des nouveaux evenements lorsque WordPress utilise une traduction de son auto-brouillon ou lui ajoute un suffixe numerique. Elle conserve les contrats et fonctionnalites de `0.2.0-alpha.2`.

## Corrections

- reconnaissance de `auto-draft`, de sa traduction WordPress active et de leurs suffixes numeriques ;
- generation du slug depuis le titre exploitable lors de la premiere sauvegarde pertinente ;
- preservation des slugs saisis manuellement ;
- preservation des slugs apres la premiere sauvegarde ;
- formulaire de retour alpha independant d'une version fixe.

## Compatibilite

- aucune migration, redirection ou modification des URL existantes ;
- aucune modification de stockage ou de donnee metier ;
- contrats Collections, Dates et Personnes inchanges ;
- Gutenberg et Divi inchanges fonctionnellement ;
- Content Kit et Spectra non requis.

Les evenements publies avec un ancien slug provisoire conservent volontairement leur URL. Une correction eventuelle doit etre decidee et accompagnee manuellement.

## Installation

1. Sauvegarder la base WordPress et le dossier actif du plugin.
2. Installer `wp-seed-events-0.2.0-alpha.3.zip` depuis l'administration WordPress.
3. Verifier que la version active est `0.2.0-alpha.3`.
4. Controler une fiche evenement, une collection, un export ICS et les builders utilises.

## Verification du correctif

Creer un nouvel evenement, lui donner un titre puis effectuer sa premiere sauvegarde. Le slug doit etre derive du titre. Une modification ulterieure du titre ne doit pas reecrire le slug, et un slug personnalise doit rester intact.

## Rollback

Reinstaller la sauvegarde exacte du dossier plugin precedent. Le ZIP ne contient aucune donnee WordPress ; les evenements, metas, reglages et slugs restent dans la base.
