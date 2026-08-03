# Classifications natives et tri par prochaine occurrence

## Objectif

WP Seed Events expose les classifications nécessaires aux collections avec les
primitives WordPress standard. Le contrat ne dépend d'aucun constructeur.

La source métier reste le stockage historique validé. Les taxonomies et la
valeur de tri sont des projections techniques reconstructibles par Lifecycle
V4 ; elles ne remplacent ni Event Data ni Event Occurrences.

## Taxonomie des types

- nom : `wp_seed_event_type` ;
- objet : `wp_seed_event` ;
- `show_in_rest=true` ;
- `show_ui=true`, sans menu ni métabox brute ;
- colonne taxonomie native masquée, car la colonne métier Type(s) existe déjà ;
- aucune archive publique et `publicly_queryable=false` ;
- utilisation standard avec `tax_query`.

Les slugs stables comprennent `stage`, `atelier`, `journee-decouverte`,
`reunion-information` et `seminaire`.

Le type principal n'est jamais déduit de l'ordre des relations WordPress.
`_wp_seed_event_primary_type` reste l'identifiant canonique explicite et doit
appartenir à l'ensemble historique `_wp_seed_event_types`. La taxonomie
projette l'ensemble des types, tandis qu'Event Data distingue :

- `primary_type` : objet `key`, `slug`, `label`, ou `null` ;
- `secondary_types` : liste d'objets ;
- `all_types` : liste ordonnée complète ;
- `types` : alias historique des libellés, inchangé.

## Événement épinglé

- taxonomie technique : `wp_seed_event_flag` ;
- terme : `featured` ;
- sens : événement épinglé, sans rapport avec `publish`, `draft` ou `private` ;
- `show_in_rest=true` ;
- aucune UI ni archive publique.

La case « Épingler cet événement » reste l'unique interface et la meta
`_wp_seed_event_pinned` reste la source canonique. Le terme est remplacé de
façon déterministe à chaque synchronisation. Les écritures REST directes sur
les deux taxonomies sont refusées afin d'empêcher toute divergence.

## Tri public WP_Query

Le token public est `wp_seed_next_occurrence` :

~~~php
$query = new WP_Query(
	array(
		'post_type' => 'wp_seed_event',
		'post_status' => 'publish',
		'tax_query' => array(
			array(
				'taxonomy' => 'wp_seed_event_type',
				'field' => 'slug',
				'terms' => array( 'atelier' ),
			),
		),
		'orderby' => 'wp_seed_next_occurrence',
		'order' => 'ASC',
		'wp_seed_next_occurrence_missing' => 'last',
	)
);
~~~

La projection `_wp_seed_event_next_occurrence_sort` contient uniquement le
`start_sort` de la première occurrence future active et non annulée, dans le
fuseau WordPress. Les événements sans prochaine occurrence sont placés en fin
avec `last`, ou exclus avec `exclude`. Les égalités sont départagées par l'ID
croissant. `DESC` inverse les dates mais conserve les événements sans date en
fin.

Tant que Lifecycle V4 n'est pas prêt, le plugin calcule les IDs depuis l'API
canonique en PHP et conserve exactement les mêmes règles. Aucun constructeur
ne lit le tableau sérialisé ni la table interne.

## REST

Les routes WordPress standard exposent les taxonomies grâce à
`show_in_rest=true`. Le champ en lecture seule
`wp_seed_event_classifications` expose `primary_type`, `secondary_types`,
`all_types`, `is_featured`, `next_occurrence` et `next_occurrence_sort`.

Aucun curseur, verrou, nom de table ou chemin serveur n'est exposé.

## Lifecycle V4 et migration

Lifecycle V4 conserve V3 comme étape historique et étend le même traitement
borné, verrouillé, idempotent et reprenable. Pour chaque événement il :

1. reconstruit les projections V3 ;
2. crée ou réutilise les termes par slug stable ;
3. remplace les relations de types ;
4. projette le terme `featured` depuis la case canonique ;
5. recalcule la prochaine occurrence active ;
6. contrôle l'intégrité avant `ready=true`.

La migration ne modifie aucune occurrence, date, URL, autorisation Personnes,
date éditoriale ou contenu. Une interruption laisse `ready=false`. La reprise
retraite sans doublonner les termes. Une installation neuve utilise le même
chemin.

Le rollback consiste à restaurer le runtime et la base sauvegardés. Les
taxonomies et la meta de tri sont reconstructibles et ne contiennent aucune
nouvelle donnée métier.

## Constructeurs

- `WP_Query` et REST : contrat complet natif ;
- Gutenberg Query Loop : taxonomies consommables nativement ; le token d'ordre
  reste utilisable par code ou par un contrôle qui accepte un `orderby` public ;
- Divi 5.9.0 : taxonomies détectables comme classifications WordPress ; le menu
  graphique des ordres personnalisés peut ne pas exposer le token ;
- autres constructeurs : compatibles s'ils transmettent `tax_query`,
  `orderby` et les query vars WordPress.

L'absence éventuelle d'un choix graphique `orderby` est une limite du
constructeur, pas une raison de dupliquer les règles métier dans un adaptateur.
