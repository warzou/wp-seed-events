# Composant public Personnes

## Role

Le composant public Personnes affiche les personnes associees a un evenement, leurs roles et uniquement les coordonnees dont la publication a ete explicitement autorisee pour cette association.

La chaine canonique reste :

```text
association evenement-personne et permissions
  -> Event Data publique filtree
  -> renderer partage Personnes
  -> template, shortcode, Divi ou Gutenberg
```

Aucun adaptateur builder ne lit directement les metas ou les coordonnees privees.

## Autorisations de publication

Chaque association peut autoriser independamment `publish_email`, `publish_phone` et `publish_link`. Elle porte aussi `phone_action` (`none`, `call` ou `sms`), qui definit le comportement du telephone pour cet evenement seulement. Seules les valeurs strictement vraies autorisent la projection publique. Le nom et les roles restent publics.

Pour une nouvelle association, chaque coordonnee valide est proposee publique par defaut et peut etre decochee avant la sauvegarde. Une association historique sans flag reste privee et les choix explicites existants sont preserves lors d'une modification sans rapport. Remplacer une coordonnee deja renseignee revoque uniquement son autorisation correspondante ; ajouter une coordonnee jusque-la absente la propose publique. Aucune autorisation n'est propagee entre evenements.

## Event Data publique

Chaque personne publique peut fournir :

- `name` ;
- `role_keys` et `roles` ;
- `public_email` ;
- `public_phone` ;
- `phone_public` et `phone_action` ;
- `public_url` et son alias canonique `website_url` ;
- `website_label`, resolu depuis la fiche Personne avec fallback sur l'URL.

Les trois coordonnees publiques sont absentes ou vides lorsque leur publication n'est pas autorisee. Les alias historiques suivent le meme filtrage.

## Renderer partage

La fonction canonique est :

```php
wp_seed_events_render_public_event_people_section( $event, $options = array() )
```

Elle consomme uniquement une Event Data publique deja filtree et n'accede ni aux metas, ni a SQL, ni au contexte WordPress.

### Options alpha.2

| Option | Defaut | Valeurs |
| --- | --- | --- |
| `title` | `Contacts et intervenants` | chaine, vide pour masquer |
| `heading_level` | `h2` | `h2` a `h6` |
| `roles` | `all` | `all` ou liste de roles |
| `role` | `all` | alias historique |
| `show_name` | `true` | booleen |
| `show_roles` | `true` | booleen |
| `show_phone` | `true` | booleen |
| `show_email` | `true` | booleen |
| `show_link` | `true` | booleen |
| `link_phone` | compatible | ancien choix de presentation, lu uniquement pour les instances historiques |
| `link_email` | `true` | booleen |
| `link_url` | `true` | booleen |
| `details` | compatible | alias historique des options de detail |
| `layout` | `list` | `list` ou `grid` |
| `contact_layout` | `stacked` | `stacked`, `inline` ou `with_name` |
| `contact_separator` | `—` | separateur entre coordonnees et, en mode `with_name`, entre le nom et la premiere coordonnee |

Les roles canoniques sont `organizer`, `speaker` et `contact`. Plusieurs roles utilisent une logique OU. `all` est exclusif dans les interfaces ; une liste explicite ne combine jamais un second mode de filtrage. `registration_contact` et `information_contact` restent des alias de lecture pour les contenus historiques.

`details` reste accepte pour les contenus historiques. Les nouvelles integrations doivent utiliser les options `show_*` et `link_*`. Un separateur explicitement enregistre reste inchange ; les nouvelles configurations utilisent un tiret cadratin.

## Confidentialite et accessibilite

Les options de rendu peuvent uniquement masquer des donnees deja publiques. Elles ne peuvent jamais recuperer ni afficher une coordonnee non autorisee.

Quand `show_name=false` et qu'une coordonnee est affichee, le nom est masque visuellement mais reste disponible sous une forme destinee aux lecteurs d'ecran afin de conserver une identification comprehensible.

Un resultat vide ne produit aucun conteneur. Toutes les valeurs sont echappees et les URLs publiques restent limitees aux schemas autorises.

## Shortcode

Le shortcode universel est :

```text
[wp_seed_event_people]
```

Il accepte `id`, `title`, `heading_level`, `roles`, l'alias `role`, les options `show_*`, les options `link_*`, l'alias `details` et `layout`.

Exemples :

```text
[wp_seed_event_people roles="organizer,speaker"]
[wp_seed_event_people show_phone="no" show_email="yes" link_email="no"]
[wp_seed_event_people show_name="no" layout="grid"]
```

Un ID explicite invalide retourne une sortie vide sans fallback. Le shortcode charge une seule Event Data et delegue une seule fois au renderer.

## Template natif

Le template natif delegue au meme renderer partage. Il conserve l'ordre et la confidentialite de l'Event Data, sans HTML Personnes concurrent.

## Module Divi 5

Le module `wp-seed-events/event-people` expose : titre, niveau, nom, roles, trois coordonnees, les choix de presentation email/site et les dispositions des personnes et coordonnees. Le mode telephone appartient a l'association evenement-personne et le libelle du site appartient a la fiche Personne ; les nouvelles instances Divi ne dupliquent aucun de ces champs.

Les roles actives sont combines en logique OU. Sans role specifique, tous les roles sont affiches. Le module ne contient ni shortcode, ni ID fixe, ni lecture de meta et n'ajoute aucune route REST Personnes.

Un contexte evenement explicite incompatible retourne vide sans fallback. Dans certains contextes hors evenement, le Visual Builder affiche un etat neutre ; le frontend reste la reference.

## Bloc Gutenberg

Le bloc dynamique `wp-seed-events/event-people-block` utilise Block API v3, `save: null`, le rendu serveur partage et les memes options que le renderer. Ses attributs persistants incluent `roles` et conservent `role` pour la compatibilite.

L'inspecteur permet une selection multiple des roles en logique OU et des controles distincts pour le nom, les roles et les coordonnees. Le telephone consomme toujours le mode canonique de l'association. Query Loop transmet un contexte isole a chaque carte. Une page ordinaire ou un contexte explicite incompatible reste vide en frontend.

## Contrat alpha.2 fige

- `roles` accepte `all` ou une liste de roles canoniques ;
- `role` et `details` restent des alias retrocompatibles ;
- les options `show_*` et `link_*` n'elargissent jamais la publication ;
- `layout` accepte uniquement `list` ou `grid` ;
- le renderer partage reste l'unique source de HTML metier ;
- Divi, Gutenberg, le shortcode et le template restent des adaptateurs fins.

## Limites

- aucune personne principale ;
- aucune republication automatique ;
- aucune coordonnee Personnes ajoutee a Dynamic Data ;
- aucun apercu de coordonnees force hors contexte evenement.
