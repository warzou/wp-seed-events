# Collections d'occurrences

`wp_seed_events_query_occurrence_collection( $args )` fournit une collection plate : une entree par occurrence.

## Filtres publics

| Argument | Defaut | Description |
| --- | --- | --- |
| `event_id` | `0` | Evenement cible. |
| `type` | vide | Type canonique actif. |
| `status` | `upcoming` | `upcoming`, `past` ou `all`. |
| `pinned` | `all` | `all` ou `only`. |
| `include_cancelled` | `false` | Inclut explicitement les occurrences annulees. |
| `from` / `to` | vide | Bornes ISO `YYYY-MM-DD`. |
| `order` | `chronological` | `upcoming`, `chronological` ou `chronological_desc`. |
| `page` | `1` | Page positive. |
| `per_page` | `20` | De 1 a 100. |

La reponse contient `items`, `total_items`, `total_pages`, `page`, `per_page` et les arguments normalises. Les donnees proviennent de la projection reconstruisible des occurrences canoniques.

## REST

```text
GET /wp-json/wp-seed-events/v1/occurrences
```

La route REST applique le meme contrat et expose `X-WP-Total` et `X-WP-TotalPages`.

Les anciens selecteurs de cohorte/annee et le mode hierarchique ont ete retires. Les cles historiques eventuellement encore presentes dans un contenu sauvegarde sont ignorees ; aucune migration de contenu n'est requise.
