# Collections d'événements dans Divi 5

## Rôle

WP Seed Events sélectionne et ordonne les événements. Divi construit la carte,
sa grille et sa pagination avec le Loop Builder natif. L'adaptateur ne produit
aucun HTML de carte et ne remplace pas le moteur de boucle de Divi.

La chaîne de référence est :

```text
contrat canonique de collection WP Seed Events
  -> adaptateur de requête Divi borné à wp_seed_event
  -> WP_Query et pagination Divi
  -> contexte loop_id de chaque carte
  -> Dynamic Data et modules WP Seed Events
```

## Configuration stable

Dans le Loop Builder, sélectionner le type de publication `wp_seed_event`.
Les filtres métier utilisent le panneau natif **Meta Query** avec des clés
virtuelles publiques. Ces clés ne sont pas des metas WordPress et ne sont
jamais enregistrées :

| Clé | Valeurs |
| --- | --- |
| `wp_seed_events_type` | slug public, par exemple `atelier` |
| `wp_seed_events_status` | `upcoming`, `past` ou `all` |
| `wp_seed_events_pinned` | `all` ou `only` |

Utiliser l'opérateur `=` pour chaque filtre. L'adaptateur retire ces clauses
avant la requête WordPress et les transmet à
`wp_seed_events_query_event_collection()`.

Pour le tri, sélectionner **1re date de l’événement**, puis l'ordre
croissant ou décroissant proposé par Divi.

Ce libellé utilisateur désigne la date de classement calculée depuis les
occurrences actives. Sa définition exacte dépend de `status` et reste
celle du contrat canonique ci-dessous.

## Règles métier

- `upcoming` utilise la prochaine occurrence future active et non annulée ;
- `past` utilise la dernière occurrence passée active et non annulée ;
- `all` utilise cette même date de référence selon le lifecycle de chaque
  événement ;
- les événements sans date et ceux composés uniquement d'occurrences annulées
  restent après tous les événements datés, quel que soit l'ordre ;
- les épinglés restent avant les non-épinglés ;
- à date égale, l'identifiant WordPress croissant garantit un ordre stable.

Quand `order` est croissant, les dates de référence vont de la plus ancienne à la
plus récente. Quand il est décroissant, elles vont de la plus récente à la plus
ancienne. Dans `all`, cela signifie que passés et futurs partagent une seule
chronologie déterministe.

## Isolation et compatibilité

L'adaptateur s'active seulement quand la boucle contient exclusivement le CPT
`wp_seed_event` et au moins une clé virtuelle ou le tri par occurrence. Les autres
requêtes WordPress et les boucles d'autres CPT restent inchangées.

Le frontend utilise le filtre Divi `divi_loop_data_after_execution`. L'aperçu
du Visual Builder utilise
`divi_module_options_loop_post_type_results_query_args`. Les deux chemins
appellent le même adaptateur et reçoivent la même liste ordonnée d'identifiants.

Divi 5.9 envoie le paramètre pluriel `post_types` à son endpoint d'options de
tri, alors que son contrôleur lit `post_type`. Un pont REST WordPress limité à
la route `/divi/v1/loop/query-order-by` et à la valeur exacte
`wp_seed_event` normalise ce paramètre. Il permet au filtre officiel
`et_builder_loop_order_by_options_wp_seed_event` d'ajouter le choix visible
**1re date de l’événement** sans modifier les boucles Articles, les boucles
mixtes ou les autres routes Divi.

La pagination, les inclusions et exclusions natives restent appliquées par
Divi. Deux boucles présentes sur une même page sont résolues indépendamment.

## Limite connue du Visual Builder

Dans Divi 5.9, l'aperçu d'un module métier placé dans une boucle peut
rester vide lorsque le canvas ne transmet pas le contexte de l'item au module
enfant. Cette limite peut notamment affecter l'aperçu de **Visuels de
communication** sans affecter le rendu frontend.

Le frontend résout le contexte de l'ancêtre répété avec le mécanisme Divi
prévu pour `loop_id` et reste le rendu de référence. Une collection doit donc
être contrôlée sur le frontend avant publication. Aucun ID fixe, shortcode ou
fallback vers un autre événement ne doit être ajouté pour forcer l'aperçu.

Le shortcode `[wp_seed_events]` reste le fallback universel et consomme le
même contrat canonique. Aucun utilisateur ne doit renseigner les metas privées
historiques de WP Seed Events dans Divi.

## Reutiliser une carte avec les outils Divi

Le parcours recommande reste entierement natif :

1. activer le Loop Builder sur un Group, une ligne ou un autre conteneur ;
2. choisir `wp_seed_event`, puis regler type, statut, epingles et ordre ;
3. construire la carte dans le conteneur repete ;
4. utiliser les modules Dates, Visuels et Personnes pour les collections
   structurees ;
5. utiliser Dynamic Content pour les champs simples ;
6. enregistrer la structure dans la Divi Library ;
7. appliquer des presets aux modules pour mutualiser les styles ;
8. utiliser un element global seulement si la structure et la requete doivent
   rester liees ;
9. utiliser Theme Builder pour les affectations globales.

La responsabilite de chaque mecanisme est distincte :

- **Loop Builder** : selection et repetition metier ;
- **Library** : structure reutilisable, copiee lors d'une insertion ordinaire ;
- **Presets** : styles reutilisables et actualisables globalement ;
- **Element global** : structure et reglages propages a toutes les occurrences ;
- **Theme Builder** : affectation globale d'une composition.

WP Seed Events ne cree aucun modele Divi concurrent et n'enregistre aucune
carte Divi imposee.
