# Changelog

## 0.2.0-beta.4 - en preparation

### Correction Divi

- Le module Visuels de communication conserve l'identifiant de l'evenement courant dans chaque item du Loop Builder.
- L'apercu Visual Builder lit les attributs Divi ordinaires et Immutable sans repli vers la page porteuse ou un autre evenement.
- Le frontend donne priorite a l'attribut resolu du bloc repete, puis reutilise le resolveur canonique Divi existant.
- Aucun changement de donnees, de contrat media ou de version n'est applique par ce correctif.
## 0.2.0-beta.3 - 2026-07-30

Cette beta ajoute le domaine Promotion, le lifecycle v3, les collections publiques d'occurrences et leurs adaptateurs Gutenberg et Divi. Elle finalise aussi l'updater WordPress natif et clarifie le format ISO des filtres de date Divi, sans migration des donnees metier.

### Domaine Promotion et lifecycle v3

- Promotions et annees du parcours additives sur les occurrences.
- Projection SQL reconstructible des occurrences, sans devenir une source canonique.
- Backfill borne, reprenable, idempotent et verifie avant `ready=true`.
- Aucun Seminaire reel cree automatiquement.

### Collections d'occurrences

- API PHP et REST plate ou groupee par Promotion, annee et evenement.
- Bloc Gutenberg dynamique avec modele enfant editable et contexte occurrence isole.
- Module Divi 5 plat ou groupe, rendu serveur et pagination par instance.
- Aide visible `Format : AAAA-MM-JJ` pour les bornes de date Divi.

### Updater WordPress natif

- Liens « Afficher les details » et « Verifier les mises a jour » dans la ligne Extensions.
- Fiche native disponible meme lorsque GitHub est temporairement indisponible.
- Continuite automatique du canal prerelease pour une installation alpha, beta ou RC.
- Invalidation manuelle limitee au cache et a l'entree WordPress de WP Seed Events.
- Metadonnees de compatibilite, messages de resultat et erreurs administrateur bornes.
- Verification SHA-256, archive officielle et installation WordPress existantes conservees.

## 0.2.0-beta.2 - 2026-07-26

Cette beta optimise les collections sur les gros catalogues, ajoute un updater GitHub controle et formalise les contrats publics, sans modifier les resultats metier ni les donnees existantes.

### Collections et lifecycle

- Selection des IDs indexes avant pagination.
- Hydratation Event Data limitee aux evenements de la page rendue.
- Lifecycle index v2 reconstructible, reprenable et idempotent.
- Projections internes de type et d'occurrences actives.
- Fallback historique exact tant que l'index v2 n'est pas pret.
- Resultats, ordre, total et pagination inchanges.

### Mises a jour GitHub

- Depot officiel `warzou/wp-seed-events` uniquement.
- Canal stable par defaut et prereleases sur consentement explicite.
- ZIP et fichier SHA-256 obligatoires et verifies avant installation.
- Cache borne, erreurs reseau isolees et prise en charge multisite.
- Aucune mise a jour automatique en arriere-plan.

### Contrats publics

- Event Data API et Event Occurrences API documentees.
- Frontieres publiques et internes explicites.
- Shortcodes, builders, renderers et alias historiques preserves.
- Aucune migration metier et aucune dependance Content Kit ou Spectra.

## 0.2.0-beta.1 - 2026-07-26

Cette premiere beta fige les contrats publics de la V1 et consolide les parcours valides pendant les alphas, sans migration automatique des donnees ou des anciens slugs.

### Collections et builders

- Collections metier composables dans Divi et Gutenberg.
- Pagination et tri par `1re date de l'evenement`.
- Patterns Gutenberg `Carte compacte` et `Carte detaillee`, non synchronises par defaut.
- Six choix explicites de dates dans Gutenberg et Divi.
- Controles avances des Personnes, sans possibilite d'exposer une coordonnee privee.

### Medias et administration

- Une seule interface media pour les evenements : le panneau Visuels de communication.
- Premier visuel synchronise avec l'image principale WordPress via `_thumbnail_id`.
- Support `thumbnail` conserve pour les APIs, builders, partage et frontend.
- Slugs localises des nouveaux auto-brouillons correctement reconnus.

### Documentation et compatibilite

- Guide utilisateur beta, migration, rollback et limites connues documentes.
- Contrats et shortcodes historiques preserves.
- Divi et Gutenberg facultatifs ; aucune dependance a WP Seed Content Kit.
- Spectra non requis et aucune compatibilite runtime avancee revendiquee.
- Aucune migration automatique des anciens slugs `brouillon-auto-*`.

### Limites connues

- Performance des collections a surveiller autour de 250 evenements et a optimiser avant un gros catalogue.
- Aucun mecanisme d'auto-update.
- Patterns Gutenberg non synchronises par defaut.
- Certains apercus Divi restent neutres hors contexte evenement.
- Spectra non recete sur le site de reference.

## 0.2.0-alpha.3 - 2026-07-25

Cette alpha corrige la generation du slug lors du premier enregistrement d'un nouvel evenement dans une interface WordPress localisee. Elle ne modifie ni les donnees existantes, ni les contrats metier, ni les integrations builders.

### Corrections

- Generation correcte des slugs des nouveaux evenements dans les interfaces WordPress localisees.
- Reconnaissance des suffixes numeriques ajoutes aux slugs provisoires des auto-brouillons.
- Intervention limitee a la premiere sauvegarde pertinente d'un `wp_seed_event`.
- Preservation des slugs manuels, des sauvegardes suivantes et des URL existantes.
- Formulaire de retour alpha rendu independant d'une version fixe.

### Compatibilite

- Aucune migration automatique des evenements existants.
- Aucun changement de stockage ou de donnees.
- Contrats Collections, Dates et Personnes inchanges.
- Gutenberg et Divi inchanges en dehors de leur metadata de version.
- Content Kit et Spectra non requis.

## 0.2.0-alpha.2 - 2026-07-25

Cette alpha consolide la composition visuelle des collections d'evenements et les controles de rendu Dates et Personnes, sans migration ni changement de stockage.

### Collections et builders

- Collections d'evenements personnalisables dans le Loop Builder Divi et le Query Loop Gutenberg.
- Filtres metier par type, statut et epinglage.
- Tri par `1re date de l'evenement`, fonde sur les occurrences actives plutot que sur la date de publication WordPress.
- Pagination Gutenberg et ordre stable, avec les evenements sans date places en fin de collection.
- Patterns Gutenberg `Carte compacte` et `Carte detaillee`, non synchronises et modifiables librement.

### Dates et personnes

- Choix explicites : Prochaine date, Premiere date, Derniere date, Toutes les prochaines dates, Toutes les dates passees et Toutes les dates.
- Controles des horaires, occurrences annulees, format et liens calendrier.
- Filtrage de plusieurs roles Personnes en logique OU.
- Controles distincts du nom, des roles, du telephone, de l'e-mail, du lien et de leur caractere cliquable.
- Les permissions de publication restent l'autorite absolue ; un builder ne peut jamais rendre une coordonnee privee.

### Integration et compatibilite

- Apercus Gutenberg et pont Block Bindings ameliores.
- Statut public ajoute au registre Dynamic Data.
- Shortcodes et alias alpha.1 conserves sans rupture silencieuse.
- Divi et Gutenberg restent facultatifs ; Content Kit et Spectra ne sont pas requis.

### Limites connues

- Les patterns sont non synchronises par defaut.
- Certains apercus Divi restent neutres hors contexte evenement ; le frontend reste la reference.
- La performance des collections devra etre mesuree avant beta sur de tres gros catalogues.
- Spectra n'est pas installe sur le site de reference et aucune compatibilite runtime avancee n'est revendiquee.

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
