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

L'insertion de `WP Seed Events - Collection d'evenements` ouvre le choix natif
des patterns du Query Loop. WP Seed Events enregistre une categorie
`WP Seed Events - Collections` et deux presentations de depart :

- `WP Seed Events - Carte compacte` :
  visuel principal, titre lie, dates sans horaires, lieu et lien vers la fiche ;
- `WP Seed Events - Carte detaillee` :
  visuel principal, titre lie, type, statut, extrait, dates et horaires, lieu,
  personnes et lien vers la fiche.

Les slugs stables sont respectivement
`wp-seed-events/event-collection-compact` et
`wp-seed-events/event-collection-detailed`. Les deux patterns sont associes a
`core/query` par l'API publique `register_block_pattern()`. Ils reprennent le
namespace et les attributs metier de la variation ; aucun second moteur de
requete n'est cree.

Chaque carte est composee uniquement de blocs Core et WP Seed Events
modifiables. Les blocs enfants heritent de `postId`, `postType` et `queryId`
pour l'item courant. Aucun HTML de carte fixe, shortcode, ID d'evenement ou
meta privee n'est serialise.

## Reutilisation

Les deux presentations sont des points de depart non synchronises. Une fois
inseree, chaque collection peut etre modifiee librement sans recevoir les
changements futurs du pattern fourni par le plugin.

L'utilisateur peut enregistrer sa collection comme composition WordPress pour
la reutiliser. Une composition synchronisee convient uniquement lorsque la
structure de carte et les reglages de requete doivent rester identiques dans
toutes ses occurrences. Pour des collections aux filtres differents, utiliser
une composition non synchronisee puis ajuster les controles metier.

Plusieurs collections WP Seed Events peuvent coexister sur une page avec des reglages distincts. Leur namespace et leur marqueur isolent leurs requetes ; les Query Loops Core ordinaires restent inchangees. L'apercu REST et le frontend consomment la meme liste canonique d'IDs, dans le meme ordre, tout en laissant Core gerer la pagination.

## Dette de performance

Le contrat canonique charge actuellement tous les evenements publies avant filtrage et tri. Le harness de reference couvre 500 evenements et verifie la pagination, la stabilite et un garde-temps local genereux. Aucune limite arbitraire n'est appliquee, car elle pourrait omettre des resultats valides.

Cette dette devra etre mesuree sur des catalogues plus volumineux. Une optimisation future devra conserver exactement les memes IDs, le meme ordre, les memes totaux et les regles d'occurrences.

## Experience alpha.2

Le bouton natif `Modifier le design` ouvre les presentations `Carte compacte` et `Carte detaillee`. Les deux utilisent uniquement des blocs Core et WP Seed Events et restent modifiables visuellement apres insertion.

La carte compacte utilise le bloc Dates en mode Prochaine date, sans horaires ni liens calendrier et sans bloc Personnes par defaut. La carte detaillee utilise Toutes les prochaines dates, affiche les horaires et inclut Personnes avec Organisateurs et Intervenants. Les coordonnees restent soumises aux permissions de publication.

Le bloc parent expose Type d'evenement, Statut, Evenements epingles, Trier par, Ordre et Elements par page. Les patterns officiels sont non synchronises ; l'utilisateur peut ensuite enregistrer une composition WordPress synchronisee ou non selon son besoin.

## Taxonomies natives

Depuis `0.2.0-beta.7`, les taxonomies de type et d'épinglage sont publiques
dans le registre WordPress tout en restant sans archive publique. Gutenberg et
les blocs de requête compatibles peuvent donc découvrir leurs termes et
transmettre un `tax_query`, sans lire les métadonnées historiques.
