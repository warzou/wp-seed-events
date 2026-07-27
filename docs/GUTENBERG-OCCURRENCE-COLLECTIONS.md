# Collections d’occurrences dans Gutenberg

Le bloc dynamique `wp-seed-events/occurrence-collection` compose une collection
à partir des contrats publics :

- `wp_seed_events_query_occurrence_collection()` pour une collection plate ;
- `wp_seed_events_query_grouped_occurrence_collection()` pour la hiérarchie
  Promotion → année du parcours → événement/thème → occurrence.

Il ne transforme pas les occurrences en posts techniques et ne lit ni SQL ni
metas métier. La Query Loop Gutenberg existante reste réservée aux événements.

## Contexte canonique

Pendant le rendu d’un item, le plugin empile temporairement :

- `event_id` ;
- `occurrence_uid` ;
- `collection_instance_id` ;
- `promotion_id` ;
- `parcours_year` ;
- `current_item_index` ;
- l’item public normalisé.

La clé technique est composée de l’instance, de l’événement, de l’occurrence et
de l’index. Le contexte parent est restauré même si le rendu d’un bloc enfant
échoue. Hors contexte explicite, les champs occurrence, Promotion et année
restent vides.

## Block Bindings

La source `wp-seed-events/occurrence-field` expose :

### Événement

- `event_title`
- `event_slug`
- `event_type`
- `event_status`
- `event_is_pinned`

### Occurrence

- `occurrence_uid`
- `occurrence_start`
- `occurrence_end`
- `occurrence_start_date`
- `occurrence_end_date`
- `occurrence_start_time`
- `occurrence_end_time`
- `occurrence_is_cancelled`

### Promotion et parcours

- `promotion_id`
- `promotion_name`
- `promotion_slug`
- `promotion_start_year`
- `promotion_status`
- `parcours_year`
- `parcours_year_label`

Cette source ne donne accès à aucune coordonnée Personnes ni donnée privée.
La source historique `wp-seed-events/event-field` reste inchangée.

## Attributs du bloc

Le bloc accepte :

- `mode` : `flat` ou `grouped` ;
- `promotion` : ID ou slug ;
- `parcoursYear` : 0 ou 1 à 4 ;
- `eventId` ;
- `eventType` ;
- `status` : `upcoming`, `past` ou `all` ;
- `pinned` : `all` ou `only` ;
- `includeCancelled` ;
- `from` et `to` ;
- `order` pour le mode plat ;
- `page` et `perPage` pour le mode plat ;
- `groupedLimit`, 200 par défaut et 500 maximum ;
- `emptyMessage` ;
- `collectionInstanceId`, facultatif.

La pagination du mode plat utilise un paramètre d’URL isolé par instance. Le
mode groupé applique une limite globale et n’imbrique pas de paginations.

## Modèle enfant

Un seul modèle `InnerBlocks` est enregistré dans le contenu. Il reste librement
modifiable puis est rejoué sur le frontend pour chaque occurrence sous son
contexte propre. Le même événement peut donc apparaître plusieurs fois sans
perdre l’identité de ses occurrences ou Promotions.

Le plugin ajoute seulement les enveloppes techniques `collection`, `item`,
`empty` et `pagination`. Il n’impose aucune carte ou feuille de style propre à
un site consommateur.

## Éditeur et frontend

L’éditeur affiche :

- les contrôles de la collection ;
- un aperçu public borné à six occurrences ;
- le modèle unique éditable sous le contexte du premier item.

Gutenberg ne fournit pas une API permettant de matérialiser plusieurs copies
éditables indépendantes des mêmes `InnerBlocks`. L’éditeur ne prétend donc pas
le faire. Le frontend rend la collection complète et répète réellement le
modèle.

Les états chargement, vide et erreur sont distincts. Les requêtes d’aperçu
utilisent les routes REST publiques des collections, sont temporisées et
annulent la requête précédente.

## Compatibilité

- WordPress 7.0.2 et Block API v3 ;
- aucune dépendance Divi, Content Kit ou Spectra ;
- Query Loop événements et Block Bindings événements inchangés ;
- rendu visiteurs non connectés limité aux événements publiés par les contrats
  publics ;
- mode groupé structuré côté serveur avec un modèle occurrence commun.

Le module Divi dédié aux occurrences reste hors de ce jalon.
