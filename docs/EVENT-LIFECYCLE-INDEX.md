# Event Lifecycle and Collection Index

## Role

L'index est une projection technique persistante et reconstruisible de l'Event Occurrences API. Il sert a deux usages sans devenir une source de verite :

1. filtrer la liste d'administration par lifecycle ;
2. selectionner, trier et paginer les collections publiques avant d'hydrater Event Data.

Aucune meta ni table d'index n'est une API publique. Une divergence se repare depuis les occurrences et types canoniques ; l'index ne modifie jamais les donnees metier.

## Version 3

La version attendue est `3`, independamment de la version du plugin. L'option `wp_seed_events_lifecycle_index_version` ne prend cette valeur qu'apres le traitement complet de tous les lots sans erreur et la verification de la projection d'occurrences. `wp_seed_events_is_lifecycle_index_ready()` reste faux pendant une initialisation, une reprise ou une migration depuis une version precedente.

La progression est stockee dans `wp_seed_events_lifecycle_index_progress` avec une version cible, un statut, un curseur d'ID, des compteurs et une liste bornee d'erreurs. Le verrou temporaire empeche deux traitements concurrents.

## Projections internes

### Lifecycle administrateur

- `_wp_seed_event_lifecycle_index_dated_count` : nombre d'occurrences datees valides, annulees incluses ;
- `_wp_seed_event_lifecycle_index_last_active_date` : date maximale `Y-m-d` des occurrences actives.

Elles distinguent `upcoming`, `past`, `undated` et `cancelled_only` dans le fuseau WordPress.

### Collections publiques

- `_wp_seed_event_collection_occurrence_sort` : une valeur `YYYY-MM-DD HH:MM` par occurrence active ;
- `_wp_seed_event_collection_type` : une valeur par cle canonique de type associee.

Les occurrences annulees ne sont jamais projetees pour le tri actif. La requete Collections calcule a la lecture la prochaine valeur superieure ou egale a aujourd'hui et la derniere valeur passee. Le passage d'un jour a l'autre ne necessite donc ni cron ni reecriture quotidienne.

La meta historique `_wp_seed_event_next_occurrence_sort` reste hors du contrat de l'index v2.

### Projection d'occurrences

La table interne `{$wpdb->prefix}wp_seed_event_occurrences` contient une ligne par occurrence normalisee. Elle projette l'identite, la promotion, l'annee du parcours, les bornes temporelles, l'annulation, le type principal, le statut et l'epingle de l'evenement.

La meta `_wp_seed_event_occurrences` reste l'unique source de verite. La table est reconstruisible, ne contient aucune coordonnee, aucun media, aucun lieu, aucun HTML et n'est jamais lue directement par un consommateur public.

## Maintien

Le calculateur consomme uniquement `wp_seed_events_get_event_occurrences()` et les fonctions canoniques de types. Il est execute :

- apres une sauvegarde pertinente d'un evenement ;
- apres un changement programme d'occurrences lorsque le post est sauvegarde ;
- apres un remappage ou reclassement de type ;
- pendant la reconstruction versionnee.

Les valeurs multiples sont remplacees de maniere idempotente et dedupliquee. La projection d'un evenement est remplacee atomiquement dans une transaction. Une sauvegarde de dates ne declenche pas deux recalculs. La suppression definitive d'un evenement retire toutes ses projections.

## Reconstruction

Le backfill :

1. selectionne des IDs croissants par lots bornes ;
2. inclut les statuts WordPress utiles ;
3. calcule toutes les projections depuis les APIs canoniques ;
4. persiste le curseur apres chaque lot ;
5. reprend apres interruption et retraite les erreurs de maniere bornee ;
6. verifie les doublons, les lignes orphelines et les paires promotion/annee ;
7. ne marque la version 3 complete qu'en l'absence d'erreur ;
8. peut etre relance integralement sans duplication.

La migration depuis la version 1 ou 2 utilise la meme reconstruction. Elle n'ecrit ni occurrence canonique, ni type, ni contenu public.

## Fallback

Tant que l'index v3 n'est pas pret, la liste admin n'active pas ses filtres indexes et les collections utilisent le selecteur PHP historique. Le lecteur interne de projection reconstruit les lignes demandees depuis l'API Occurrences canonique. Si une requete indexee echoue, la collection publique revient egalement a son fallback exact. Aucun ordre WordPress approximatif ne remplace silencieusement le tri metier.

## Lifecycle

- `undated` : aucune occurrence datee valide ;
- `cancelled_only` : occurrences datees presentes, aucune active ;
- `upcoming` : au moins une occurrence active aujourd'hui ou future ;
- `past` : occurrences actives presentes, toutes strictement passees.

Une occurrence datee aujourd'hui reste `upcoming` pendant toute la journee WordPress.

## Securite et exploitation

La reconstruction manuelle exige la capacite et le nonce admin appropries. Les rapports ne contiennent aucune donnee sensible. L'affichage public et la liste admin ne lancent jamais de reparation globale. Les options, metas, curseurs, verrous et details SQL restent internes.

Voir [Occurrence Projection and Lifecycle V3](OCCURRENCE-PROJECTION-LIFECYCLE-V3.md), [Event Occurrences API](EVENT-OCCURRENCES-API.md), [Collections publiques](PUBLIC-COLLECTIONS.md) et [Migration et rollback](MIGRATION-AND-ROLLBACK.md).

## Livraison beta.2

La version `0.2.0-beta.2` introduit officiellement l'index lifecycle version 2. Une mise a jour depuis beta.1 reconstruit uniquement les projections techniques ; elle ne modifie ni occurrences, ni types, ni contenus, ni dates de modification des evenements.

## Fondation lifecycle v3

Le lot Promotion Domain ajoute la projection SQL par occurrence et fait evoluer l'index attendu vers la version 3. Cette fondation ne cree aucune collection groupee publique et ne modifie aucun builder. Son contrat technique, sa strategie d'identite et sa procedure de reprise sont documentes dans [Occurrence Projection and Lifecycle V3](OCCURRENCE-PROJECTION-LIFECYCLE-V3.md).
