# Collection d'occurrences Gutenberg

Le bloc `wp-seed-events/occurrence-collection` repete ses `InnerBlocks` pour chaque entree de `wp_seed_events_query_occurrence_collection()`.

Le contexte `wpSeedEvents/occurrence` contient `event_id`, `occurrence_uid`, `collection_instance_id`, `current_item_index` et les valeurs publiques date/heure. La source de bindings est `wp-seed-events/occurrence-field`.

L'inspecteur expose les filtres evenement, type, statut, epinglage, annulation, bornes, ordre et pagination. L'apercu est limite a six occurrences ; le frontend rend la page complete.

Le bloc ne lit ni meta ni SQL directement. Il consomme exclusivement la collection publique plate. Les attributs historiques retires restent techniquement lisibles par WordPress mais ne sont ni exposes ni interpretes.
