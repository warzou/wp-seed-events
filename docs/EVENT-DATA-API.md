# Event Data API

## Statut du contrat

`wp_seed_events_get_event_data( $event_id )` est l'API publique canonique qui expose les donnees publiques normalisees d'un evenement. Elle est independante des shortcodes, des builders et du rendu HTML.

```php
$event = wp_seed_events_get_event_data( 123 );
```

- `$event_id` accepte une valeur convertible en entier positif.
- La fonction retourne un tableau associatif pour un `wp_seed_event` publie.
- Elle retourne `array()` pour un ID nul, invalide, un autre type de contenu, un brouillon ou un evenement prive.
- Elle ne retourne pas de `WP_Error` et n'ecrit aucune donnee.
- `wp_seed_events_public_event_data( $event_id )` est un alias public compatible.

## Schema retourne

| Cle | Type | Contrat |
| --- | --- | --- |
| `id` | `int` | ID WordPress de l'evenement. |
| `title` | `string` | Titre public. |
| `slug` | `string` | Slug public de l'evenement, utilise avec `id` et `title` comme identite du theme. |
| `url` | `string` | URL publique absolue HTTP(S), ou chaine vide. |
| `types` | `string[]` | Libelles publics des types associes. |
| `occurrences` | `array[]` | Occurrences normalisees, annulees incluses. |
| `promotions` | `array[]` | Promotions publiques distinctes des occurrences. |
| `parcours_years` | `int[]` | Annees du parcours distinctes, triees de 1 a 4. |
| `active_occurrences` | `array[]` | Occurrences non annulees. |
| `next_occurrence` | `array` | Premiere occurrence active aujourd'hui ou dans le futur, ou `array()`. |
| `last_occurrence` | `array` | Derniere occurrence active chronologique, ou `array()`. |
| `display_occurrence` | `array` | `next_occurrence`, sinon `last_occurrence`. |
| `lifecycle` | `string` | `upcoming`, `past`, `undated` ou `cancelled_only`. |
| `place` | `array` | `id`, `name`, `address`, `details`, `link`, ou `array()`. |
| `place_address` | `string` | Projection texte de l'adresse. |
| `place_url` | `string` | URL publique absolue HTTP(S), ou chaine vide. |
| `people` | `array[]` | Personnes et coordonnees explicitement publiques seulement. |
| `description` | `string` | Contenu WordPress stocke de l'evenement. Le consommateur choisit son rendu et son echappement. |
| `excerpt` | `string` | Extrait public normalise. |
| `practical_info` | `string` | Informations pratiques publiques. |
| `event_document_filename` | `string` | Nom public sur du document PDF. |
| `event_document_url` | `string` | URL HTTP(S) du PDF public, ou chaine vide. |
| `featured_image` | `array|null` | Objet Media de l'image principale WordPress. |
| `communication_visual` | `array|null` | Premier visuel de communication normalise. |
| `communication_visuals` | `array[]` | Visuels de communication ordonnes. |
| `other_visuals` | `array[]` | Visuels apres le recto. |
| `event_document` | `array|null` | Objet Media du PDF public. |

Un objet Media expose : `id`, `url`, `mime_type`, `title`, `alt`, `caption`, `filename`, `width` et `height`. Aucune cle ne contient de chemin serveur.

Une personne publique expose `name`, `role_keys`, `roles`, `public_email`, `public_phone` et `public_url`. Les alias `email`, `phone` et `link` reproduisent uniquement ces valeurs deja autorisees ; ils ne contournent jamais les permissions de publication.

Le schema des occurrences est defini dans [Event Occurrences API](EVENT-OCCURRENCES-API.md).
Le schema Promotion et ses routes sont definis dans
[Promotions et annees du parcours](PROMOTION-DOMAIN-API.md).

## Alias medias historiques

Les identifiants suivants restent derives des objets Media normalises :

- `primary_image_id` : ID de `communication_visual` ;
- `featured_image_id` : ID de `featured_image` ;
- `illustration_ids` : IDs de `communication_visuals` ;
- `flyer_pdf_id` : ID de `event_document`.

Ils sont conserves pour compatibilite. Les nouveaux consommateurs utilisent les objets Media.

## Donnees publiques et securite

L'API ne retourne que les evenements publies. Les coordonnees Personnes sont filtrees association par association avant d'entrer dans Event Data. Les URLs exposees sont absolues et limitees a HTTP(S). Les metas, options, verrous, curseurs, chemins serveur et autorisations internes ne font pas partie du resultat.

Le tableau reste une donnee : un consommateur HTML doit appliquer l'echappement ou les APIs WordPress adaptees a sa surface.

## Compatibilite

Les cles documentees ici constituent le contrat public de la serie 1.x. Elles ne sont pas retirees ou renommees silencieusement. Les ajouts futurs doivent etre additifs. La politique complete est decrite dans [Compatibilite de l'API publique](PUBLIC-API-COMPATIBILITY.md).

## Architecture

```text
Stockage WordPress
  -> Event Occurrences / Media / People
  -> Event Data API
  -> renderers, shortcodes, Dynamic Data, Divi, Gutenberg et collections
```

Event Data ne produit pas de HTML, ne choisit pas une collection et ne depend d'aucun builder.
