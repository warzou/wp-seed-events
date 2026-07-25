# Public Date Renderer

## Role

Le renderer public des dates centralise le HTML d'une liste d'occurrences.

Il consomme uniquement l'Event Data API et les occurrences normalisees par
l'Event Occurrences API. Il ne lit aucune meta, ne recalcule aucune date et
n'ecrit aucune donnee.

La fonction canonique est :

```php
wp_seed_events_render_public_event_dates_section( $event, $args = array() )
```

`$event` peut etre la structure Event Data deja chargee ou un identifiant
d'evenement. Un identifiant declenche une seule resolution Event Data. Une
structure deja chargee ne declenche aucune nouvelle resolution.

## Options V1

- `title` : titre facultatif, `Dates` par defaut ;
- `heading_level` : `h2` a `h6`, `h2` par defaut ;
- `mode` : `next`, `first`, `last` ou `all`, `all` par defaut ;
- `scope` : `all`, `upcoming` ou `past`, `all` par defaut ;
- `show_cancelled` : affiche les occurrences annulees, `true` par defaut ;
- `show_times` : affiche les horaires, `true` par defaut ;
- `show_calendar_links` : affiche les actions calendrier, `true` par defaut.
- `format` : `long` ou `short`, `long` par defaut.

Les builders présentent ces combinaisons sous des libellés explicites : `next+upcoming` correspond à Prochaine date, `all+upcoming` à Toutes les prochaines dates, `all+past` à Toutes les dates passées et `all+all` à Toutes les dates. Dans les builders, Première date et Dernière date utilisent `scope=all` pour éviter un second contrôle ambigu. Les shortcodes conservent la combinaison avancée `mode` + `scope`. Le contrat PHP reste fondé sur `mode` et `scope`.

Une valeur `heading_level` invalide revient a `h2`. Une valeur `scope`
invalide revient a `all`.

Le renderer n'expose ni limite, ni tri configurable, ni pagination, ni option
de mise en page.

## Filtrage

Le renderer conserve l'ordre canonique fourni par l'Event Occurrences API.

- `all` conserve toutes les occurrences datees valides ;
- `upcoming` utilise exclusivement `is_date_future` ;
- `past` utilise exclusivement `is_date_past` ;
- `show_cancelled=false` exclut ensuite toutes les occurrences annulees.

Les projections `is_date_future` et `is_date_past` sont neutres. Une occurrence
annulee future ou passee reste donc eligible dans la portee correspondante si
`show_cancelled=true`.

Si aucune occurrence ne reste, le renderer retourne une chaine vide et ne
produit aucun conteneur.

## HTML public

Le renderer produit une section, une liste et un element `time` par occurrence.
Il conserve les classes historiques du renderer et du template natif, puis
ajoute les classes d'etat stables suivantes :

- `is-future` ;
- `is-past` ;
- `is-cancelled` ;
- `is-all-day`.

Les classes BEM principales sont :

- `wp-seed-event-dates__title` ;
- `wp-seed-event-date__date` ;
- `wp-seed-event-date__time` ;
- `wp-seed-event-date__status`.

Le texte visible `Annulée` est toujours present pour une occurrence annulee.
Un titre vide supprime le heading et ajoute un `aria-label` a la section.

Toutes les valeurs sont echappees. Les donnees invalides sont ignorees avant
toute sortie afin d'eviter un HTML partiel.

## Horaires

Le renderer reutilise le formateur horaire existant :

- journee entiere ;
- heure de debut seule ;
- plage horaire ;
- aucune sortie lorsque le libelle est vide.

`show_times=false` supprime completement les elements horaires.

## Calendrier

Une action individuelle est rendue uniquement pour une occurrence active
future. Une occurrence passee ou annulee n'est jamais exportable.

L'action globale est rendue uniquement si le resultat filtre contient au moins
deux occurrences actives futures. Les helpers calendrier acceptent cette liste
deja normalisee et ne relisent pas l'Event Occurrences API.

Le format ICS et les URLs de telechargement restent inchanges.

## Consommateurs

Le template natif `templates/event-single.php` utilise le renderer partage.
Le shortcode historique `[wp_seed_event_dates]` delegue integralement au meme
renderer, sans wrapper ou HTML supplementaire.

## Shortcode de compatibilite

La syntaxe sans attribut utilise l'evenement du contexte courant :

```text
[wp_seed_event_dates]
```

Sur une page ordinaire sans contexte evenement, le shortcode retourne une
chaine vide. L'attribut facultatif `id` permet de choisir explicitement un
evenement :

```text
[wp_seed_event_dates id="914" heading_level="h3"]
```

Les attributs V1 sont :

- `id` : identifiant d'evenement facultatif ;
- `title` : `Dates` par defaut, chaine vide autorisee ;
- `heading_level` : `h2` a `h6`, `h2` par defaut ;
- `mode` : `next`, `first`, `last` ou `all`, `all` par defaut ;
- `scope` : `all`, `upcoming` ou `past`, `all` par defaut ;
- `show_cancelled` : `yes` ou `no`, `yes` par defaut ;
- `show_times` : `yes` ou `no`, `yes` par defaut ;
- `show_calendar_links` : `yes` ou `no`, `yes` par defaut.

Les valeurs invalides reviennent aux valeurs par defaut. Les occurrences
annulees restent situees dans `upcoming` ou `past` selon leur date et sont
ensuite incluses uniquement lorsque `show_cancelled="yes"`.

Exemples :

```text
[wp_seed_event_dates scope="upcoming"]
[wp_seed_event_dates scope="past" show_cancelled="no"]
[wp_seed_event_dates title="" show_calendar_links="no"]
[wp_seed_event_dates id="914" heading_level="h3"]
```

Pour compatibilite ascendante, `format="long|short"` reste accepte et
`show_time="yes|no"` reste un alias de `show_times`. Lorsque les deux attributs
sont presents, `show_times` est prioritaire. Aucun alias historique
`show_calendar` n'existe.

Les futurs composants Dates de Gutenberg ou Divi devront reutiliser ce
renderer ou le meme contrat d'occurrences, sans acces au stockage.

## Hors perimetre

Ce contrat n'ajoute aucun shortcode, module builder, bloc Gutenberg, style
inline, tri configurable, grille, migration, backfill ou changement de
stockage.

## Contrat alpha.2 fige

Le contrat public reste `mode`, `scope`, `show_cancelled`, `show_times`, `format` et `show_calendar_links`. Les builders traduisent leurs six libelles explicites vers ce contrat sans modifier les regles metier. Le shortcode conserve les combinaisons avancees et les alias historiques.
