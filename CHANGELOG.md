# Changelog

## 0.2.0-alpha.1 - 2026-07-20

Cette version alpha est destinee a la validation de la premiere V1 complete de WP Seed Events.

### Donnees et domaine evenement

- Event Data API et Event Occurrences API comme contrats metier canoniques.
- Occurrences multiples, UID stables, projections temporelles et lifecycle admin indexe.
- Collections publiques, URLs canoniques, partage minimal et exports ICS individuel ou multi-occurrences.

### Medias, documents et personnes

- Visuels de communication ordonnes, Flyer recto, autres visuels et document PDF complementaire.
- Renderers partages et composants publics Dates, Visuels et Personnes.
- Coordonnees Personnes privees par defaut et publiees uniquement par autorisation explicite, champ par champ et evenement par evenement.

### Integration WordPress et builders

- Templates natifs et shortcodes de compatibilite.
- Dynamic Data public type texte, URL et image.
- Modules Divi 5 Dates, Visuels et Personnes, avec Loop Builder.
- Blocs Gutenberg Dates, Visuels et Personnes, avec Query Loop Core et compatibilite Astra/Spectra validee.

### Stabilisation alpha

- Cache Event Data limite a la requete PHP et contextes builders strictement isoles.
- Renderers Divi defensifs lorsque des metadonnees techniques sont absentes.
- Aide de sauvegarde globale apres l'enregistrement d'une date.
- Vocabulaire PDF harmonise autour du Document complementaire.
- Recette complete sur un evenement neuf et non-regressions historiques validees.

### Important avant installation

- Sauvegarder les fichiers et la base WordPress avant installation ou mise a jour.
- Cette alpha est destinee a la validation ; la compatibilite ascendante totale n'est pas garantie avant une version stable.
- Aucune coordonnee Personnes historique n'est republiee automatiquement.

### Limites connues

- La boite native Image mise en avant coexiste encore avec le panneau Visuels de communication.
- Le cas d'un slug accentue degrade reste a reproduire et isoler.
- Le placement du bloc Partager en page modele complete depend encore du theme.

## 0.1.0-dev

### Plugin

- Bootstrap du plugin.
- Creation d'un evenement.
- Plusieurs dates.
- Lieu reutilisable.
- Contacts.
- Image principale.
- Flyer PDF.
- Illustrations.

### Ameliorations UX

- Description native.
- Vocabulaire simplifie.
- Ajout progressif des dates.
- Ajout progressif des personnes.
