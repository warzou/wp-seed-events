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

## Consommateurs

Les consommateurs de cette API sont :

- les templates publics ;
- les shortcodes ;
- le registre Dynamic Data ;
- le provider Gutenberg ;
- un futur provider Divi ;
- une future compatibilite Spectra ;
- les futures boucles metier ;
- une eventuelle REST API plus tard.

Chaque consommateur adapte les donnees a son propre contexte.

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
