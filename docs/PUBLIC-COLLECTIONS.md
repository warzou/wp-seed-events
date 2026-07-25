# Public Collections

## Decision

WP Seed Events doit permettre d'afficher des collections publiques d'evenements sans devenir un builder, un calendrier avance ou un plugin de reservation.

Le modele retenu est simple :

```text
WP Seed Events choisit et ordonne les evenements
  -> Event Data API prepare chaque evenement
  -> event-card.php affiche chaque carte du shortcode
  -> Divi peut composer une carte et repeter la meme selection metier
```

## Role des collections publiques

Une collection publique repond a une question metier :

Quels evenements afficher ?

Elle ne repond pas a la question :

Comment designer la page ?

La collection selectionne les evenements, les ordonne, puis rend chaque evenement sous forme de carte.

## V1 attendue

La V1 doit fournir un shortcode unique :

```text
[wp_seed_events]
```

Ce shortcode affiche une liste sobre de cartes d'evenements publics.

Options autorisees en V1 :

- `limit` : nombre maximum d'evenements a afficher ;
- `status` : `upcoming`, `past` ou `all` ;
- `type` : type d'evenement a afficher ;
- `pinned` : `all` ou `only` ;
- `order` : `asc` ou `desc` pour la date de reference.

Exemples :

```text
[wp_seed_events]
[wp_seed_events limit="6"]
[wp_seed_events status="past"]
[wp_seed_events type="atelier"]
[wp_seed_events pinned="only"]
[wp_seed_events type="atelier" status="upcoming" order="asc"]
```

## Regles metier

### Evenements a venir

Un evenement a venir est un evenement publie qui possede au moins une occurrence future non annulee.

Par defaut, `[wp_seed_events]` affiche les evenements a venir.

### Evenements passes

Un evenement passe est un evenement publie dont les occurrences datees sont toutes passees.

Les evenements passes doivent etre affiches du plus recent au plus ancien.

### Tous les evenements

`status="all"` affiche les evenements publies, qu'ils soient a venir, passes ou sans date.

Les evenements dates restent tries selon leur date de reference.

Les evenements sans date apparaissent apres les evenements dates.

En ordre croissant, les dates de reference passees et futures partagent une
chronologie unique de la plus ancienne a la plus recente. En ordre decroissant,
l'ordre est inverse. La date de reference est la prochaine occurrence active
pour un evenement a venir et la derniere occurrence active pour un evenement
passe.

### Evenements sans date

Les evenements sans date ne doivent pas apparaitre dans `status="upcoming"` ni dans `status="past"`.

Ils peuvent apparaitre uniquement avec `status="all"`.

### Dates annulees

Une occurrence annulee reste visible dans la fiche d'un evenement.

Pour une collection publique V1, une occurrence annulee ne doit pas servir a classer un evenement comme a venir.

Si un evenement ne possede que des occurrences annulees, il ne doit pas etre considere comme un evenement a venir.

### Evenements epingles

Les evenements epingles peuvent etre affiches en priorite dans les collections.

`pinned="all"` affiche les evenements epingles et non epingles.

`pinned="only"` affiche uniquement les evenements epingles.

La notion d'epingle ne remplace pas le tri par date. Elle sert seulement a mettre certains evenements en tete d'une collection.

### Filtrage par type

L'option `type` limite la collection a un type d'evenement.

Le type passe au shortcode doit correspondre a un type public compréhensible, par exemple :

```text
[wp_seed_events type="atelier"]
```

Le type principal utilise dans les permaliens ne remplace pas les types multiples.

Le filtrage par type doit donc s'appuyer sur les types associes a l'evenement, pas uniquement sur le type principal.

## Contrat canonique et Divi

`wp_seed_events_query_event_collection()` est la source unique de selection,
de tri et de pagination. Le shortcode et l'adaptateur Divi consomment ce meme
resultat.

Cette API accepte `type`, `status`, `pinned`, `order`, `page` et `per_page`,
puis retourne les objets Event Data, les IDs ordonnés, le total et les
informations de pagination.

L’adaptateur Gutenberg Query Loop transforme ses réglages vers ces mêmes arguments et réutilise les IDs retournés. Un éventuel adaptateur Spectra devra suivre le même contrat après une recette dédiée. Aucun adaptateur ne doit relire les metas historiques, recalculer le lifecycle ou réimplémenter le tri par occurrences.

Divi conserve la composition visuelle des cartes. Son adaptateur de requete ne
produit aucun HTML et ne consulte aucune meta privee saisie par l'utilisateur.
Les filtres stables `wp_seed_events_type`, `wp_seed_events_status` et
`wp_seed_events_pinned` sont des cles virtuelles documentees, jamais stockees.
Le tri `wp_seed_events_business_date` est exposé sous le libellé « 1re date de l’événement ». Voir `DIVI-EVENT-COLLECTIONS.md`.

## Relation avec l'Event Data API

La collection choisit les evenements.

L'Event Data API prepare les donnees de chaque evenement selectionne.

La collection ne doit pas reconstruire elle-meme les donnees metier d'un evenement.

Elle ne doit pas relire directement les champs metier pour produire une carte.

## Relation avec event-card.php

`event-card.php` est le rendu de reference des cartes du shortcode. Divi et Gutenberg conservent la composition visuelle de leurs cartes avec leurs outils natifs, tout en consommant la même sélection métier. WP Seed Events ne crée aucun moteur de template concurrent.

## Ce que la V1 ne doit pas faire

La V1 ne doit pas ajouter :

- filtres interactifs ;
- calendrier ;
- recherche publique ;
- reservation ;
- paiement ;
- ICS ;
- Google Maps ;
- Open Graph ;
- design avance ;
- options de colonnes ;
- options de couleurs ;
- systeme de template complexe.

## Principe durable

Les collections publiques sont des boucles metier simples.

Elles creent une liste d'evenements.

Elles ne possedent pas le rendu unitaire.

Elles ne possedent pas les donnees metier.

Elles consomment l'Event Data API et reutilisent la carte publique existante.

## Contrat alpha.2 fige

La fonction `wp_seed_events_query_event_collection()` accepte publiquement :

- `type` ;
- `status` : `upcoming`, `past` ou `all` ;
- `pinned` : `all` ou `only` ;
- `order` : `asc` ou `desc` ;
- `page` ;
- `per_page` ;
- `limit`, conserve pour le shortcode historique.

Elle retourne les Event Data selectionnees, les IDs ordonnes, le total et les informations de pagination. Les occurrences annulees ne definissent jamais la date active de classement. Les evenements sans date exploitable apparaissent en fin de collection avec `status=all` et sont exclus de `upcoming` et `past`.

Le shortcode, Divi et Gutenberg consomment ce contrat. Aucun builder ne recalcule le lifecycle ou le tri par occurrence.
