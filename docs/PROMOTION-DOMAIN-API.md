# Promotions et annees du parcours

## Statut

Ce document decrit le premier contrat public du domaine Promotion. Il ne met pas
en oeuvre le lifecycle v3, les collections groupees, ni les adaptateurs finaux
Divi, Gutenberg ou Spectra.

## Modele

Une Promotion est une entite officielle `wp_seed_promotion` geree sous le menu
WP Seed Events. Elle n'a pas de page frontend.

| Cle | Type | Contrat |
| --- | --- | --- |
| `id` | `int` | ID WordPress de la promotion. |
| `name` | `string` | Nom public. |
| `slug` | `string` | Slug unique dans le type de contenu. |
| `start_year` | `int` | Annee de debut, ou `0` si elle n'est pas renseignee. |
| `status` | `string` | `active` ou `archived`. |
| `order` | `int` | Ordre manuel stable. |
| `description` | `string` | Description publique filtree par WordPress. |

Une promotion archivee reste lisible sur les occurrences historiques. Elle
n'est plus proposable pour une nouvelle association. Une promotion referencee
ne peut pas etre mise a la corbeille ou supprimee : l'archivage est le chemin
normal.

## API PHP

```php
$promotion = wp_seed_events_get_promotion( 42 );
$promotion = wp_seed_events_get_promotion( 'promotion-2027' );

$active = wp_seed_events_get_promotions();
$all    = wp_seed_events_get_promotions(
	array(
		'status'  => 'all',
		'orderby' => 'order',
		'order'   => 'ASC',
	)
);
```

`wp_seed_events_get_promotion()` retourne `array()` pour un ID ou un slug
invalide. `wp_seed_events_get_promotions()` accepte :

- `status` : `active`, `archived` ou `all` ;
- `orderby` : `order`, `start_year` ou `name` ;
- `order` : `ASC` ou `DESC`.

Ces fonctions sont en lecture seule. Elles n'exposent ni meta brute, ni chemin
serveur, ni donnee privee.

## Occurrence et annee du parcours

Une occurrence peut porter :

- `promotion_id` ;
- `promotion`, objet public normalise ;
- `parcours_year`, entier de `1` a `4` ;
- `parcours_year_label`, par exemple `1re annee` ou `3e annee`.

La promotion et l'annee sont facultatives, mais toujours presentes ensemble.
Une annee n'est jamais deduite de la date, de l'annee de debut ou de la position
chronologique. Une combinaison invalide bloque la sauvegarde complete des
occurrences afin d'eviter toute perte partielle.

Le titre de l'evenement reste le theme du seminaire. Aucun objet Theme ni aucune
taxonomie concurrente n'est cree.

## Event Data

Event Data conserve toutes ses cles et ajoute :

- `promotions` : promotions distinctes rencontrees dans les occurrences ;
- `parcours_years` : annees du parcours distinctes, triees par ordre croissant.

Chaque entree de `occurrences` et `active_occurrences`, ainsi que les projections
`next_occurrence`, `last_occurrence` et `display_occurrence`, utilise le meme
schema enrichi.

## REST

Les routes publiques sont en lecture seule :

```text
GET /wp-json/wp-seed-events/v1/promotions
GET /wp-json/wp-seed-events/v1/promotions/<id-ou-slug>
GET /wp-json/wp-seed-events/v1/events/<event_id>/occurrences
```

La collection Promotions accepte `status`, `orderby` et `order`. Elle retourne
les promotions actives par defaut. La lecture directe d'une promotion archivee
reste possible pour les historiques.

La route Occurrences est publique pour un evenement publie. Un evenement non
publie exige la capacite `edit_post` correspondante. Elle accepte
`include_cancelled` et `status=all|future|past`.

Aucune route d'ecriture n'est fournie dans ce lot.

## Administration

L'ecran Promotions gere le nom, le slug WordPress, l'annee de debut, le statut,
l'ordre et la description. Les colonnes annee de debut, statut et ordre sont
triables sans modifier les autres listes d'administration.

Dans chaque occurrence, la section `Parcours (facultatif)` permet de choisir une promotion active et une annee de
1 a 4.

Les anciennes occurrences sans ces cles restent valides. Aucun backfill ni
migration n'est execute.

## Compatibilite et prochaines etapes

Le contrat est independant des builders et doit etre consomme par les futures
collections groupees, le lifecycle v3 et les adaptateurs de presentation. Ces
travaux restent separes.

Le site consommateur de formation reste bloque tant que le lifecycle v3 et les
collections par Promotion / annee du parcours / theme ne sont pas livres. Ce
premier lot rend les donnees fiables et reutilisables, sans pretendre lever ce
blocage a lui seul.

Hors perimetre : tarifs, inscriptions, disponibilites, paiement, conditions
speciales structurees, reservations, lieu ou personnes portes par occurrence,
WP Seed Core et dependance a Content Kit.
