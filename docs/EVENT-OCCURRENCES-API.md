# Event Occurrences API

## Fonction publique

```php
$occurrences = wp_seed_events_get_event_occurrences( $event_id, $args );
```

Cette fonction lit et normalise les occurrences d'un evenement. Elle retourne toujours un tableau, sans ecriture et sans `WP_Error`.

## Parametres

| Parametre | Defaut | Valeurs | Effet |
| --- | --- | --- | --- |
| `include_cancelled` | `true` | booleen | Conserve ou retire les occurrences annulees. |
| `only_active` | `false` | booleen | Ne conserve que les occurrences non annulees. |
| `status` | `all` | `all`, `future`, `past` | Filtre sur les projections actives `is_future` et `is_past`. Une valeur inconnue est traitee comme `all` pour compatibilite. |

`$event_id` est normalise avec `absint()`. Une meta absente ou mal formee produit `array()`. Une entree sans `start_date` valide au format `YYYY-MM-DD` est ignoree.

## Schema d'une occurrence

| Cle | Type | Contrat |
| --- | --- | --- |
| `id` | `string` | UID valide, sinon identifiant derive. |
| `uid` | `string` | UUID valide stocke, ou chaine vide. |
| `derived_id` | `string` | Identifiant deterministe `occ-...` derive de l'evenement, de la position et des valeurs temporelles. |
| `event_id` | `int` | ID de l'evenement. |
| `promotion_id` | `int` | ID de la promotion valide, ou `0`. |
| `promotion` | `array` | Objet Promotion public normalise, ou `array()`. |
| `parcours_year` | `int` | Annee du parcours de 1 a 4, ou `0`. |
| `parcours_year_label` | `string` | Libelle public de l'annee, ou chaine vide. |
| `start_date` | `string` | Date `YYYY-MM-DD`, toujours presente. |
| `end_date` | `string` | Date de fin valide ou chaine vide. |
| `start_time` | `string` | Heure `HH:MM` valide ou chaine vide. |
| `end_time` | `string` | Heure `HH:MM` valide ou chaine vide. |
| `all_day` | `string` | Valeur historique `"1"` ou `""`. |
| `cancelled` | `string` | Valeur historique `"1"` ou `""`. |
| `start_sort` | `string` | `YYYY-MM-DD HH:MM`, avec `00:00` si necessaire. |
| `end_sort` | `string` | Fin normalisee, avec repli sur le debut. |
| `is_dated` | `bool` | Toujours `true` pour une occurrence retournee. |
| `is_active` | `bool` | `true` si l'occurrence n'est pas annulee. |
| `is_date_future` | `bool` | Date de debut aujourd'hui ou dans le futur. |
| `is_date_past` | `bool` | Date de debut strictement anterieure a aujourd'hui. |
| `is_future` | `bool` | Active et `is_date_future`. |
| `is_past` | `bool` | Active et `is_date_past`. |
| `is_cancelled` | `bool` | Projection booleenne de l'annulation. |
| `date_label` | `string` | Libelle de date localise. |
| `time_label` | `string` | Libelle d'horaire localise. |
| `datetime_label` | `string` | Date et horaire combines. |

## Temps et ordre

Les comparaisons utilisent la date courante du fuseau WordPress via `current_time( 'Y-m-d' )`. Elles ne comparent pas l'heure courante : une occurrence datee aujourd'hui reste future pendant toute la journee.

Le resultat est trie par `start_sort` croissant. Les occurrences annulees gardent une position chronologique mais ne sont jamais actives. Il n'existe pas d'objet public « occurrence sans date » : une entree sans date valide est rejetee.

## Projections publiques associees

- `wp_seed_events_get_next_active_occurrence( $event_id )` retourne la premiere occurrence active aujourd'hui ou future, sinon `array()`.
- `wp_seed_events_get_last_active_occurrence( $event_id )` retourne la derniere occurrence active chronologique, sinon `array()`.
- `wp_seed_events_get_event_lifecycle( $event_id )` retourne `upcoming`, `past`, `undated` ou `cancelled_only`.

## Garanties et elements internes

Le schema ci-dessus est public. La meta source, les projections SQL des collections, les options de version d'index, les curseurs et les verrous de reconstruction sont internes. Les consommateurs ne doivent ni lire la meta d'occurrences directement ni reconstruire le lifecycle.

Les identifiants derives restent deterministes pour une position et des valeurs identiques, mais un consommateur qui exige une identite durable doit privilegier un `uid` non vide.

Voir aussi [Event Data API](EVENT-DATA-API.md) et [Collections publiques](PUBLIC-COLLECTIONS.md).
Voir aussi [Promotions et annees du parcours](PROMOTION-DOMAIN-API.md).
