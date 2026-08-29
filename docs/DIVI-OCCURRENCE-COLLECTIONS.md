# Collection d'occurrences Divi

Le module `wp-seed-events/divi-occurrence-collection` consomme `wp_seed_events_query_occurrence_collection()` et rend une entree plate par occurrence.

Il expose les filtres evenement, type, statut, epinglage, annulation, bornes, ordre et pagination. Chaque element recoit un contexte distinct par `collection_instance_id`, `occurrence_uid` et index courant.

Le Visual Builder utilise la route d'apercu interne du plugin ; le frontend utilise la meme normalisation serveur. Aucun post technique et aucune lecture directe de meta ne sont introduits.

Les options visuelles portent sur la collection, l'element, le titre, les libelles, les valeurs, l'etat vide et la pagination. Les anciens controles metier retires ne sont plus enregistres dans une nouvelle instance et sont ignores lorsqu'ils subsistent dans un contenu historique.
