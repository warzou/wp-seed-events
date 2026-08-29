# Projection des occurrences et lifecycle

La table interne `{$wpdb->prefix}wp_seed_event_occurrences` accelere les lectures multi-evenements. `_wp_seed_event_occurrences` reste l'unique source canonique.

Chaque ligne projette l'identite `(event_id, occurrence_uid)`, les bornes triees, l'annulation, le type principal, le statut, l'epinglage et la date de mise a jour. Elle ne contient ni coordonnees, ni lieu, ni media, ni HTML.

Le lifecycle utilise des lots bornes, un curseur persistant, un verrou atomique expirant et des reprises idempotentes. La table est reconstruisible et n'est pas une API publique. Les controles d'integrite couvrent les doublons et les lignes orphelines.

Les collections publiques consomment cette projection uniquement par les adaptateurs internes du plugin.

La version interne 6 retire les anciennes colonnes et leurs index. L'installation supprime ces colonnes techniques si elles existent, puis reconstruit les projections depuis le stockage canonique sans modifier les evenements.
