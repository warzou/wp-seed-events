# Event Data API

## Decision

WP Seed Events doit formaliser une API metier interne stable pour les donnees d'un evenement.

Cette API ne remplace pas les shortcodes, le registre Dynamic Data, Gutenberg, Divi, Spectra ou les futures boucles.

Elle devient leur source commune.

## Pourquoi Dynamic Data est trop etroit

Dynamic Data repond a une question precise :

Comment un builder accede-t-il a une donnee du contexte courant ?

WP Seed Events a besoin d'un socle plus large :

Comment le plugin expose-t-il proprement le modele metier d'un evenement a tous les consommateurs ?

Les shortcodes, les templates publics, Gutenberg, Divi, Spectra, les futures boucles et une eventuelle REST API plus tard ne doivent pas chacun relire le stockage ou reconstruire la logique metier.

## Role de l'Event Data API

L'Event Data API doit :

- recevoir un evenement ou un identifiant d'evenement ;
- verifier que l'evenement est valide ;
- lire les donnees WordPress et les donnees metier necessaires ;
- normaliser les dates, lieu, personnes, types, medias et description ;
- resoudre les valeurs calculees comme la prochaine occurrence ;
- masquer les details de stockage ;
- retourner une structure de donnees stable ;
- rester independante du rendu, des builders et des shortcodes.

## Contrat temporel

L'Event Data API conserve trois projections distinctes :

- `next_occurrence` : premiere occurrence active aujourd'hui ou dans le futur ;
- `last_occurrence` : derniere occurrence active dans l'ordre chronologique ;
- `display_occurrence` : `next_occurrence` si elle existe, sinon `last_occurrence`.

Les adaptateurs derivent `next_date` et `next_time` de `next_occurrence`.
Ils derivent `display_date` et `display_time` de `display_occurrence` pour les
surfaces qui ont besoin d'une date de reference, notamment les cartes. Une
occurrence annulee n'est jamais une occurrence active.

Chaque occurrence normalisee expose egalement deux projections temporelles
neutres :

- `is_date_future` vaut `true` lorsque sa date de debut est aujourd'hui ou dans
  le futur ;
- `is_date_past` vaut `true` lorsque sa date de debut est strictement anterieure
  a aujourd'hui.

Ces deux projections sont independantes de `is_active` et `is_cancelled`. Elles
permettent donc de situer chronologiquement une occurrence annulee sans la
considerer comme active. Les contrats existants restent inchanges :

- `is_future` designe une occurrence active aujourd'hui ou dans le futur ;
- `is_past` designe une occurrence active passee.

La comparaison utilise la date courante du fuseau WordPress, sans comparaison
d'heure. Une occurrence datee aujourd'hui est donc `is_date_future=true` et
`is_date_past=false` pendant toute la journee.

## Consommateurs

Les consommateurs de cette API sont :

- les templates publics ;
- les shortcodes ;
- le renderer public partage des dates ;
- le registre Dynamic Data ;
- les providers Dynamic Content Divi 5 ;
- la source Gutenberg Block Bindings ;
- les blocs Gutenberg et les boucles Spectra ;
- les futures boucles metier ;
- une eventuelle REST API plus tard.

Chaque consommateur adapte les donnees a son propre contexte.

Le renderer public des dates utilise directement `occurrences`,
`is_date_future`, `is_date_past`, `is_active` et `is_cancelled`. Il conserve
l'ordre fourni par l'Event Occurrences API et ne relit ni le stockage ni le
normaliseur. Son contrat de rendu est documente dans
`docs/PUBLIC-DATE-RENDERER.md`.

## Ce que l'API ne doit pas faire

L'API ne doit pas :

- produire du HTML par defaut ;
- imposer un design ;
- connaitre Divi, Gutenberg ou Spectra ;
- remplacer les shortcodes ;
- exposer les meta keys comme contrat public ;
- exposer les options internes du plugin ;
- gerer les reglages d'administration ;
- choisir quels evenements afficher dans une liste ;
- devenir un framework ;
- introduire WP Seed Core.

## Premier lot technique safe

Le premier lot technique doit etre strictement conservateur.

Objectif :

Extraire et nommer la source de donnees deja existante sans changer le comportement.

Plan minimal :

1. Creer `includes/public/event-data.php`.
2. Y definir la fonction canonique `wp_seed_events_get_event_data( $event_id )`.
3. Deplacer la logique actuelle de `wp_seed_events_public_event_data( $post_id )` vers cette fonction canonique.
4. Conserver `wp_seed_events_public_event_data( $post_id )` comme alias de compatibilite.
5. Charger le nouveau fichier avant `includes/public/rendering.php`.
6. Ne pas modifier les shortcodes.
7. Ne pas modifier les templates publics.
8. Ne pas modifier le registre Dynamic Data.
9. Ne pas modifier le provider Gutenberg.
10. Ne pas ajouter de nouveaux champs.

Validation attendue :

- aucun changement de rendu public ;
- aucun changement de sortie shortcode ;
- aucun changement Gutenberg ;
- `git diff --check` ;
- controle UTF-8 sans BOM ;
- `php -l` sur les fichiers PHP modifies.

## Principe durable

Le modele cible est :

```text
Stockage WordPress
  -> Event Data API
  -> consommateurs
       -> templates publics
       -> shortcodes
       -> Dynamic Data
       -> providers
       -> boucles
```

WP Seed Events reste proprietaire du metier.

Les consommateurs utilisent les donnees.

Ils ne possedent pas le metier.
