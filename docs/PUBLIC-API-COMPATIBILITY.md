# Compatibilite de l'API publique

## Politique

WP Seed Events ne retire et ne renomme aucun contrat public silencieusement.

- Une deprecation est annoncee dans la documentation et le changelog avant tout retrait.
- Un contrat annonce stable est maintenu pendant la serie 1.x.
- Une suppression incompatible est reservee a une version majeure.
- Les nouvelles cles de donnees sont additives.
- Les alias ne permettent jamais de contourner une validation ou une permission.

## APIs publiques

Les surfaces suivantes sont publiques et reutilisables :

- `wp_seed_events_get_event_data()` et son alias `wp_seed_events_public_event_data()` ;
- `wp_seed_events_get_event_occurrences()`, `wp_seed_events_get_next_active_occurrence()`, `wp_seed_events_get_last_active_occurrence()` et `wp_seed_events_get_event_lifecycle()` ;
- `wp_seed_events_get_promotion()` et `wp_seed_events_get_promotions()` ;
- `wp_seed_events_query_event_collection()` ;
- `wp_seed_events_query_occurrence_collection()` et `wp_seed_events_query_grouped_occurrence_collection()` ;
- `wp_seed_events_get_event_collection()`, alias retournant uniquement les Event Data ;
- les renderers documentes Dates, Visuels et Personnes ;
- les shortcodes documentes ;
- les IDs de blocs, modules et sources Dynamic Data documentes.

Aucun hook PHP propre au plugin n'est actuellement declare comme extension publique generale. Les hooks WordPress utilises par les adaptateurs restent des details d'integration.

## Alias conserves

| Alias | Cible ou comportement |
| --- | --- |
| `wp_seed_events_public_event_data()` | Alias de `wp_seed_events_get_event_data()`. |
| `wp_seed_events_get_event_collection()` | Retour simplifie de la collection canonique. |
| `role` | Alias Personnes historique d'un filtre de role unique ; `roles` est le contrat fin. |
| `details` | Booleen Personnes historique ; les options `show_*` et `link_*` sont prioritaires. |
| `show_time` | Alias Dates de `show_times` ; `show_times` est prioritaire. |
| `wp_seed_events_next_date` | Nom historique de la source Dynamic Data « prochaine date ». |
| `primary_image_id` | ID derive de `communication_visual`. |
| `featured_image_id` | ID derive de `featured_image`. |
| `illustration_ids` | IDs derives de `communication_visuals`. |
| `flyer_pdf_id` | ID derive de `event_document`. |
| `email`, `phone`, `link` | Alias Personnes des seules valeurs `public_email`, `public_phone`, `public_url` deja autorisees. |

## Frontiere publique

Sont publics :

- les schemas documentes Event Data et Occurrences ;
- le schema Promotion et les routes REST publiques en lecture seule documentees ;
- le contrat Collections d'evenements `type`, `status`, `pinned`, `order`, `page`, `per_page`, `limit` ;
- les collections d'occurrences plates et groupees, leurs schemas et leurs routes REST en lecture seule ;
- les signatures et options des renderers documentes ;
- les shortcodes ;
- les IDs de blocs Gutenberg, modules Divi et sources Dynamic Data documentes.

Sont internes et peuvent evoluer sans devenir un contrat externe :

- les routes d'apercu des builders ;
- les metas et requetes SQL ;
- les options lifecycle et updater ;
- les versions, projections, curseurs et verrous d'index ;
- les caches et transients ;
- les callbacks et hooks d'adaptation propres a Divi ou Gutenberg non declares publics ;
- les fichiers de build et leurs fonctions privees.

Un consommateur externe utilise les APIs publiques et ne lit pas les metas, options ou routes d'apercu.

## References

- [Event Data API](EVENT-DATA-API.md)
- [Event Occurrences API](EVENT-OCCURRENCES-API.md)
- [Promotions et annees du parcours](PROMOTION-DOMAIN-API.md)
- [Collections publiques](PUBLIC-COLLECTIONS.md)
- [Dynamic Data](PUBLIC-EVENT-DYNAMIC-DATA.md)
- [Renderer Dates](PUBLIC-DATE-RENDERER.md)
- [Renderer Visuels](PUBLIC-EVENT-VISUALS.md)
- [Renderer Personnes](PUBLIC-EVENT-PEOPLE.md)
