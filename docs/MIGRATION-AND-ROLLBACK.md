# Migration et rollback

Ce document accompagne les versions alpha et bêta de WP Seed Events. Une
sauvegarde vérifiée reste obligatoire avant tout remplacement du plugin.

## Installation neuve

1. Sauvegarder WordPress.
2. Installer le ZIP officiel.
3. Activer le plugin.
4. Vérifier la version active.
5. Ouvrir les réglages WP Seed Events.
6. Initialiser l'index lifecycle s'il est absent.
7. Créer un événement de recette avant la saisie réelle.

Le ZIP contient le code du plugin, jamais les événements, utilisateurs,
médias, coordonnées ou options du site.

## Mise à jour alpha.1 vers beta.1

La mise à jour apporte les composants Dates, Visuels et Personnes, les
collections builders, le contrat Dynamic Data et les corrections de slugs.
Elle ne republie aucune coordonnée et ne migre pas les slugs existants.

Les compositions Gutenberg créées avec des blocs plus récents ne sont pas
garanties lorsqu'un ancien runtime est restauré. Leur contenu reste en base,
mais l'éditeur peut signaler un bloc indisponible ou inattendu.

## Mise à jour alpha.2 vers beta.1

La beta conserve la correction alpha.3 des slugs d'auto-brouillon localisés,
les collections builders et les contrôles avancés Dates et Personnes. Elle ne
réécrit ni les URL publiées ni les anciens slugs `brouillon-auto-*`.

## Mise à jour alpha.3 vers beta.1

La beta.1 masque l'interface native **Image mise en avant**
sur `wp_seed_event`, sans retirer le support `thumbnail`, `_thumbnail_id` ou
la projection du premier visuel. Aucune migration de média ou de stockage
n'est nécessaire.

Avant installation :

1. archiver exactement le dossier plugin actif ;
2. sauvegarder la base de données ;
3. produire un manifeste des fichiers avec taille et SHA-256 ;
4. relever la version, l'état lifecycle et les compteurs de contenus ;
5. vérifier le ZIP dans un dossier temporaire.

Après installation :

1. comparer le runtime au manifeste du ZIP ;
2. vérifier l'absence de seconde copie active ;
3. ouvrir une fiche, une collection, un ICS et les builders utilisés ;
4. contrôler le panneau Visuels et sa synchronisation avec l'image principale ;
5. vérifier la confidentialité des coordonnées.

## Lifecycle

### Index absent

L'administration affiche une action d'initialisation. Elle traite les
événements par lots et enregistre sa progression. Les filtres dépendants de
l'index ne doivent pas être considérés comme prêts avant la fin.

### Index déjà initialisé

Si la version attendue et la version enregistrée correspondent, aucune
réinitialisation n'est nécessaire.

### Reprise après interruption

Relancer l'action depuis les réglages. Le curseur et les erreurs enregistrées
permettent une reprise bornée. Ne supprimez pas manuellement les options de
progression pendant un traitement.

### Idempotence

Rejouer l'initialisation sur les mêmes événements doit produire le même index
sans dupliquer les occurrences ni modifier les contenus métier.

## Rollback des fichiers

1. Conserver le site en maintenance opérationnelle si nécessaire.
2. Vérifier le SHA-256 de l'archive de rollback.
3. Extraire l'archive hors du webroot.
4. Comparer son manifeste à la sauvegarde d'origine.
5. Remplacer atomiquement le dossier actif par le dossier restauré.
6. Conserver le runtime défaillant hors du webroot jusqu'à validation.
7. Purger uniquement les caches nécessaires.

Un rollback de fichiers ne supprime pas les événements ni les médias.

## Rollback SQL et options

Un rollback SQL n'est justifié que si une écriture de données a réellement
endommagé l'état WordPress. Restaurer toute la base peut écraser des contenus
créés entre-temps.

Procédure prudente :

1. comparer les données actuelles à la sauvegarde ;
2. identifier précisément tables, options et metas concernées ;
3. sauvegarder de nouveau l'état actuel ;
4. restaurer l'ensemble de la base uniquement si une restauration ciblée n'est
   pas sûre ;
5. vérifier utilisateurs, contenus et réglages après restauration.

Les options lifecycle sont la version de l'index, sa progression et son verrou
temporaire. Ne les modifiez manuellement qu'avec un diagnostic documenté.

## Contrôle du runtime

Le manifeste officiel de la version détermine le nombre attendu de fichiers.
Pour les alphas de référence, le contrôle était `52/52`. Une version future
peut avoir un autre total : utilisez toujours le manifeste livré avec son ZIP.

Vérifier :

- racine unique `wp-seed-events/` ;
- chemins Linux compatibles ;
- aucun test, source de build, secret ou temporaire ;
- taille et SHA-256 fichier par fichier ;
- aucun fichier manquant ou supplémentaire ;
- version du fichier principal ;
- syntaxe PHP.

## Vérifications après restauration

- accueil, REST et wp-admin répondent ;
- version active correcte ;
- fiche et modèle d'événement fonctionnels ;
- collections et pagination fonctionnelles ;
- ICS et partage sans erreur ;
- blocs et modules reconnus ;
- lifecycle cohérent ;
- confidentialité Personnes préservée ;
- aucun fatal, warning, notice, token ou shortcode brut.

Les sauvegardes autoritatives doivent rester hors du webroot et ne jamais être
committées.

## Preparation de beta.2 : index Collections v2

La version 2 de l'index lifecycle ajoute deux projections internes pour selectionner et paginer les collections avant hydratation Event Data. Elle ne modifie ni les occurrences, ni les types, ni les contenus metier.

Apres installation d'un runtime qui attend l'index v2 :

1. ouvrir le panneau lifecycle de WP Seed Events ;
2. lancer ou reprendre la reconstruction par lots ;
3. conserver `ready=false` jusqu'a la fin ;
4. verifier la version attendue et l'absence d'IDs en erreur ;
5. recetter `upcoming`, `past`, `all`, les types, les epingles et plusieurs pages.

La reconstruction est versionnee, bornee, idempotente et reprenable. Tant qu'elle n'est pas complete, les collections utilisent explicitement le selecteur PHP historique afin de conserver des resultats exacts. Une restauration d'un ancien runtime peut ignorer les nouvelles projections ; elles ne contiennent aucune donnee source et peuvent etre reconstruites.

## Mises a jour manuelles GitHub

Le canal stable reste actif par defaut et les prereleases restent desactivees. Une release installable doit fournir le ZIP officiel et son asset `.sha256`. L'installation est refusee avant remplacement si le checksum, la racine, les chemins ou la version interne divergent.

L'updater n'ajoute aucun rollback automatique. La sauvegarde exacte du dossier actif et de la base reste obligatoire. Voir [Mises a jour officielles depuis GitHub](GITHUB-UPDATES.md).

## Migration vers beta.3 : lifecycle v3 et collections d'occurrences

La mise a jour depuis beta.2 ne modifie ni les occurrences canoniques, ni les contenus, ni les slugs, ni les autorisations Personnes. Elle cree ou met a niveau uniquement la table de projections lifecycle v3, puis reconstruit les lignes techniques en lots bornes depuis `_wp_seed_event_occurrences`.

Avant la mise a jour, sauvegarder la base et le dossier plugin actif. Apres installation, executer le backfill officiel jusqu'a `ready=true`, verifier l'absence de doublon et d'orphelin, puis recetter Collections, REST, ICS, Gutenberg et Divi. Un rollback du plugin ne requiert aucune conversion des donnees metier : la table de projections peut etre reconstruite par une version compatible.

## Migration vers Lifecycle V4 : classifications natives

Lifecycle V4 projette les types existants vers `wp_seed_event_type`, la case
d'epinglage vers le terme `featured` de `wp_seed_event_flag`, et la prochaine
occurrence active vers la valeur de tri reconstructible. La migration est
bornee, verrouillee, idempotente et reprenable par le panneau lifecycle
existant. Elle conserve V3, ne modifie aucune donnee editoriale et reste
`ready=false` jusqu'au controle d'integrite final.

Avant migration, sauvegarder SQL et runtime. Apres migration, verifier les
termes, les types principaux et secondaires, les epingles, le tri ASC/DESC,
les evenements sans date, REST et les boucles. Un rollback restaure la
sauvegarde ; aucune conversion inverse de contenu metier n'est necessaire.
