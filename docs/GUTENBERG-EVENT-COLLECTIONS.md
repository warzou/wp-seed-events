# Collection d'evenements Gutenberg

## Principe

La variation `WP Seed Events - Collection d'evenements` adapte le bloc Core Query. WP Seed Events selectionne les evenements et leur ordre ; Gutenberg conserve la composition de la carte, son rendu et sa pagination.

La variation utilise le namespace `wp-seed-events/event-collection` et le type de contenu `wp_seed_event`. Elle n'agit jamais sur une Query Loop Core ordinaire.

## Reglages

Les attributs namespaced de `query` sont :

- `wpSeedEventsCollection` : marqueur strict ;
- `wpSeedEventsType` : slug public du type ;
- `wpSeedEventsStatus` : `upcoming`, `past` ou `all` ;
- `wpSeedEventsPinned` : `all` ou `only` ;
- `wpSeedEventsOrder` : `ASC` ou `DESC` ;
- `wpSeedEventsOrderBy` : `business_date`, presente a l'utilisateur comme `1re date de l'evenement`.

Le nombre d'elements par page reste l'attribut Core `perPage`.
## Regles metier

Le contrat canonique applique les memes regles que les autres consommateurs :

- `upcoming` classe chaque evenement sur sa prochaine occurrence future active et non annulee ;
- `past` classe chaque evenement sur sa derniere occurrence passee active et non annulee ;
- `all` utilise une date metier deterministe et place les evenements sans date exploitable en fin de liste ;
- les occurrences annulees ne definissent jamais la date de classement active ;
- `pinned=only` conserve uniquement les evenements epingles ;
- lorsque la priorite des epingles est active dans le contrat, elle precede le tri par date ;
- `ASC` et `DESC` inversent l'ordre metier sans remplacer ce tri par la date de publication WordPress.

Le libelle utilisateur du tri reste `1re date de l'evenement`. Les identifiants techniques ne sont pas exposes dans l'interface.


## Editeur et frontend

L'apercu editeur et le frontend passent par `wp_seed_events_gutenberg_apply_collection_query()`. Le frontend utilise le hook officiel `query_loop_block_query_vars`. L'editeur declare uniquement les parametres requis sur le controleur REST de `wp_seed_event`, via `rest_wp_seed_event_collection_params`, puis adapte `rest_wp_seed_event_query`.

Les deux chemins appellent `wp_seed_events_apply_collection_to_query_args()`, qui recupere les IDs du contrat canonique et preserve les arguments de pagination du builder. Un etat marque mais invalide retourne une selection vide. Les boucles non marquees restent inchangees.

## Composition

La carte initiale est composee de blocs Core et WP Seed Events modifiables : visuels, titre lie, type, statut, dates, extrait, lieu et personnes. Chaque bloc enfant herite du contexte de l'item courant. Aucun HTML de carte fixe, shortcode, ID d'evenement ou meta privee n'est serialise.

Plusieurs collections WP Seed Events peuvent coexister sur une page avec des reglages distincts. Leur namespace et leur marqueur isolent leurs requetes ; les Query Loops Core ordinaires restent inchangees. L'apercu REST et le frontend consomment la meme liste canonique d'IDs, dans le meme ordre, tout en laissant Core gerer la pagination.

## Dette de performance

Le contrat canonique charge actuellement tous les evenements publies avant filtrage et tri. Le harness de reference couvre 500 evenements et verifie la pagination, la stabilite et un garde-temps local genereux. Aucune limite arbitraire n'est appliquee, car elle pourrait omettre des resultats valides.

Cette dette devra etre mesuree sur des catalogues plus volumineux. Une optimisation future devra conserver exactement les memes IDs, le meme ordre, les memes totaux et les regles d'occurrences.
