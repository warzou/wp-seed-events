# Event Lifecycle Index

## Décision

WP Seed Events utilisera un index technique persistant pour permettre le filtrage SQL de la liste admin selon l'état temporel d'un événement.

Cet index est une projection reconstruisible. Il ne constitue jamais une source de vérité métier.

La chaîne cible est :

```text
Stockage historique
  -> Event Occurrences API
  -> calculateur d'index
  -> projections techniques
  -> WP_Query admin
  -> filtre temporel
```

Le futur filtre visible sera une liste déroulante :

- Toutes les dates ;
- À venir ;
- Passés ;
- Sans date ;
- Annulés.

Son paramètre GET sera `wp_seed_event_lifecycle`. Les valeurs internes autorisées seront `upcoming`, `past`, `undated` et `cancelled_only`.

L'interface et la requête du filtre ne font pas partie du présent lot documentaire.

## Objectif de l'index

L'index sert uniquement à rendre le lifecycle interrogeable par `WP_Query` et `meta_query`, tout en conservant :

- la pagination native ;
- les statuts de publication WordPress ;
- la recherche admin ;
- les actions groupées ;
- les futurs filtres admin.

Il ne doit pas être utilisé par les templates, shortcodes, Dynamic Data, providers ou autres consommateurs métier.

La liste admin ne doit jamais scanner tous les événements pour recalculer cet état à chaque affichage.

## Source de vérité

L'Event Occurrences API est l'unique source de vérité.

Le calculateur d'index doit :

- consommer les occurrences normalisées retournées par `wp_seed_events_get_event_occurrences()` ;
- utiliser les états déjà exposés par l'API, notamment `is_active` et `is_cancelled` ;
- ignorer les entrées que l'API considère comme invalides ;
- ne jamais relire et interpréter directement `_wp_seed_event_occurrences` ;
- ne jamais recalculer une seconde définition du lifecycle.

L'index peut toujours être reconstruit depuis l'API. En cas de divergence, il est recalculé depuis l'API ; les données métier ne sont jamais modifiées depuis l'index.

## Projections techniques

Deux metas sont retenues. Aucun nom identique ou équivalent n'existe actuellement dans le plugin.

### `_wp_seed_event_lifecycle_index_dated_count`

Nombre total d'occurrences datées valides retournées par l'API :

- inclut les occurrences annulées ;
- exclut les occurrences invalides ou sans date exploitable ;
- est stocké comme entier positif ou nul ;
- permet de distinguer `undated` de `cancelled_only`.

### `_wp_seed_event_lifecycle_index_last_active_date`

Date maximale au format `Y-m-d` parmi les occurrences actives non annulées :

- est stockée comme chaîne vide lorsqu'aucune occurrence active datée n'existe ;
- ne dépend pas du jour courant ;
- n'utilise pas l'heure ;
- suit le fuseau et les conventions de l'Event Occurrences API ;
- permet de distinguer `upcoming`, `past` et `cancelled_only`.

Ces deux metas sont des projections techniques supprimables et reconstruisibles sans perte métier. `_wp_seed_event_next_occurrence_sort` n'est pas réutilisée : son contrat historique ne représente pas fidèlement les quatre états temporels.

## Table de vérité

Notation :

- `dated_count` : `_wp_seed_event_lifecycle_index_dated_count` ;
- `last_active_date` : `_wp_seed_event_lifecycle_index_last_active_date` ;
- `today` : date actuelle dans le fuseau WordPress.

| Lifecycle | dated_count | last_active_date |
| --- | ---: | --- |
| `undated` | `0` | vide |
| `cancelled_only` | `> 0` | vide |
| `upcoming` | indifférent | non vide et `>= today` |
| `past` | indifférent | non vide et `< today` |

Les événements mixtes sont classés uniquement selon leurs occurrences actives. Les occurrences annulées comptent dans `dated_count`, mais ne participent jamais à `last_active_date`.

Le lifecycle fonctionne à la journée : une occurrence datée aujourd'hui reste `upcoming` pendant toute la journée.

## Cas de référence

| Cas | dated_count | last_active_date | Lifecycle |
| --- | ---: | --- | --- |
| Aucune occurrence | `0` | vide | `undated` |
| Une occurrence future active | `1` | date future | `upcoming` |
| Actives passées et futures | total valide | dernière date active future | `upcoming` |
| Uniquement des actives passées | total valide | date `< today` | `past` |
| Uniquement des annulées | `> 0` | vide | `cancelled_only` |
| Actives et annulées | total valide | maximum des actives | dérivé des actives |
| Occurrence datée aujourd'hui | `> 0` | `today` | `upcoming` |
| Données invalides seules | `0` | vide | `undated` |

Les données invalides sont ignorées conformément à l'Event Occurrences API.

## Maintien après sauvegarde

L'index doit être recalculé après toute sauvegarde explicite ayant persisté une modification du bloc Dates :

- ajout, modification ou suppression d'une occurrence ;
- annulation ou réactivation ;
- changement de date, de journée entière ou d'horaire.

Le recalcul intervient après validation et persistance des occurrences. Il relit ensuite leur représentation normalisée via l'Event Occurrences API, sans dupliquer le parsing dans le callback de sauvegarde.

Les deux metas sont toujours enregistrées, y compris `0` et la chaîne vide. Une absence de valeur ne doit pas être représentée par une absence de meta après indexation.

Aucune réécriture quotidienne et aucun cron récurrent ne sont nécessaires. Le passage du temps est géré par la comparaison SQL entre `last_active_date` et `today`.

## Backfill historique

Le backfill est une opération explicite, idempotente, non destructive, bornée et reprenable.

Il doit :

1. sélectionner uniquement les posts `wp_seed_event`, tous statuts WordPress utiles inclus ;
2. parcourir les IDs par ordre croissant avec un curseur, sans requête globale non bornée ;
3. traiter un nombre limité d'IDs par lot ;
4. calculer les deux projections via l'Event Occurrences API ;
5. enregistrer les deux metas pour chaque événement, y compris les valeurs vides ;
6. mémoriser la progression après chaque lot réussi ;
7. reprendre après le dernier ID confirmé sans retraiter les lots terminés ;
8. produire un rapport de progression sans donnée sensible ;
9. permettre un recalcul complet déclenché explicitement.

Après succès, tous les événements ciblés possèdent les deux projections. Aucun contenu sans index ne doit être réparé silencieusement pendant l'affichage de la liste.

## État, complétude et version

La version attendue du contrat d'index est `1`. Elle est indépendante de la version du plugin.

Option de complétude retenue :

```text
wp_seed_events_lifecycle_index_version
```

Elle contient la version complète installée et ne prend la valeur `1` qu'après réussite de tous les lots.

État de progression retenu pour la future implémentation :

```text
wp_seed_events_lifecycle_index_progress
```

Cet état technique doit au minimum contenir la version cible, le statut, le dernier ID confirmé et le nombre traité. Les statuts attendus sont `pending`, `running`, `complete` et `failed`.

Les situations se déduisent ainsi :

- jamais démarré : aucune version complète et aucune progression ;
- en cours : progression `running` ;
- complet : version stockée égale à la version attendue ;
- obsolète : version stockée différente de la version attendue.

Le filtre ne doit être actif que si la version stockée correspond à la version attendue. Une évolution du contrat, du format ou de la normalisation impose un recalcul contrôlé.

Ces options ne sont pas créées dans le présent lot.

## Aucune écriture à la lecture

Règles figées :

- afficher la liste admin ne calcule et n'écrit aucun index ;
- ouvrir un événement ne déclenche aucun backfill ;
- appeler l'Event Occurrences API reste une lecture ;
- un contenu sans index n'est jamais réparé par la requête admin ;
- `pre_get_posts` ne lance aucune réparation globale ;
- l'initialisation historique reste explicite et contrôlée.

## Requête future

Les futures conditions conceptuelles sont :

- `upcoming` : `last_active_date >= today`, comparaison `DATE` ;
- `past` : `last_active_date` non vide et `< today`, comparaison `DATE` ;
- `cancelled_only` : `dated_count > 0`, comparaison `NUMERIC`, et `last_active_date` vide ;
- `undated` : `dated_count = 0`, comparaison `NUMERIC`.

`today` vient du fuseau WordPress. La future implémentation ajoute ses conditions à la `meta_query` existante sans la remplacer. La pagination, la recherche, les statuts et les autres filtres WordPress restent natifs.

## Interface future

La décision produit est une liste déroulante, sans vue lifecycle ni compteur dédié.

Libellés visibles :

- Toutes les dates ;
- À venir ;
- Passés ;
- Sans date ;
- Annulés.

Le terme `lifecycle` n'apparaît pas dans l'interface. Le paramètre `wp_seed_event_lifecycle` doit se combiner avec les paramètres WordPress utiles. Un changement utilisateur du filtre réinitialise `paged`.

## Compatibilité

Le mécanisme doit couvrir les événements publiés, brouillons, privés, planifiés, en attente et, lorsque la requête l'inclut, ceux de la Corbeille.

Il doit conserver le contrat actuel pour :

- les événements historiques ;
- les contenus sans occurrence ;
- les contenus uniquement annulés ;
- les occurrences invalides ;
- le fuseau WordPress et les changements d'heure.

Aucune migration destructive et aucune modification de l'Event Occurrences API ne sont attendues.

## Sécurité et robustesse

La future implémentation doit garantir :

- la vérification du type de post ;
- une capacité appropriée et un nonce pour tout déclenchement admin manuel ;
- des lots bornés ;
- un verrou temporaire empêchant deux exécutions concurrentes ;
- une reprise sûre après interruption ;
- l'idempotence des écritures ;
- l'absence de suppression ou de modification des occurrences ;
- des rapports sans secret.

Aucune interface admin complète de backfill n'est définie à ce stade.

## Lots futurs

### Lot 1 — Documentation du contrat d'index

Présent lot. Aucun code, aucune meta et aucune option.

### Lot 2 — Calculateur et maintien après sauvegarde

Calcul des projections depuis l'Event Occurrences API et maintien pour les événements explicitement enregistrés. Aucun backfill global.

### Lot 3 — Backfill historique

Initialisation bornée, versionnée et reprenable. Aucun filtre visible avant complétude.

### Lot 4 — Filtre admin

Ajout de la liste déroulante et des conditions de requête, uniquement lorsque la version d'index attendue est complète.

### Lot 5 — Recette site

Validation des combinaisons publication/lifecycle, recherche, pagination, brouillons, Corbeille et performance.

Chaque lot reste indépendant et doit être validé avant le suivant.
