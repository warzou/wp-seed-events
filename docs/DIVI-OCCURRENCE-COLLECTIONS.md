# Collection d’occurrences Divi 5

Le module `wp-seed-events/divi-occurrence-collection` adapte le contrat public de
collections d’occurrences à Divi 5. Il ne crée aucun post technique et ne remplace
pas le Loop Builder pour les collections d’événements.

## Choisir la bonne collection

- Le Loop Builder Divi répète une carte par événement.
- Le module Collection d’occurrences répète une ligne ou une carte par occurrence.
- Le mode groupé conserve Promotion, année du parcours, événement puis occurrence.

Les modes plat et groupé consomment respectivement
`wp_seed_events_query_occurrence_collection()` et
`wp_seed_events_query_grouped_occurrence_collection()`.

## Filtres et ordre

Le module expose Promotion, année du parcours, événement, état temporel,
occurrences annulées, ordre et taille de page. La valeur `0` d’un filtre est
normalisée en absence de filtre. Le mode plat est paginé par instance ; chaque
module possède une clé de pagination isolée. Le mode groupé applique une limite
globale bornée à 500 éléments et n’imbrique pas de pagination.

## Contenu affiché

Les réglages permettent d’afficher ou masquer le titre de l’événement, le type,
le statut, l’extrait, la date, les horaires, le lieu, les personnes publiques,
la Promotion, l’année du parcours et le lien vers la fiche. Les formats de date,
d’heure, les séparateurs et les libellés vide ou annulé sont configurables.

Le rendu est produit en PHP avec une structure sémantique. Les valeurs Event Data,
Occurrence et Promotion sont échappées selon leur contexte. Les coordonnées
Personnes restent limitées aux données déjà autorisées publiquement.

## Contexte et composition

Chaque élément reçoit temporairement le contexte composite canonique
`collection_instance_id + event_id + occurrence_uid + current_item_index`. Le
contexte précédent est restauré après le rendu, y compris en cas d’exception ou
d’imbrication. Les sources Dynamic Data occurrence restent vides en dehors de ce
contexte explicite.

Divi ne fournit pas d’API publique stable pour répéter arbitrairement un arbre de
modules enfants par occurrence. Le module utilise donc des champs de contenu
explicites et des contrôles Design Divi, sans introduire un moteur de templates
concurrent. L’aperçu Visual Builder est rendu par le serveur via une route interne
protégée ; le frontend reste l’autorité lorsque le contexte d’édition est incomplet.

## Compatibilité

Le bloc Gutenberg Collection d’occurrences et le module Divi consomment le même
contrat public et le même contexte canonique. Spectra et WP Seed Content Kit ne
sont pas requis. Les routes d’aperçu builder sont internes et ne constituent pas
une API publique parallèle.
