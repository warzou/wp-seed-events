# Composant public Visuels de communication

## Rôle

Le composant public Visuels de communication affiche les images de communication et le document PDF d'un événement WP Seed Events.

La chaîne de référence est :

`normaliseur média -> Event Data API -> renderer partagé Visuels -> adaptateurs`.

Les adaptateurs V1 sont :

- le shortcode universel `[wp_seed_event_visuals]` ;
- le module Divi 5 `wp-seed-events/event-visuals` ;
- le bloc Gutenberg/Spectra `wp-seed-events/event-visuals-block`.

Ils résolvent le contexte et transmettent des options. Ils ne lisent aucune meta, ne recalculent pas le modèle média et ne reconstruisent pas le HTML métier.

## Contrat Media

Chaque média public est un objet neutre :

```text
{
  id: number,
  url: string,
  mime_type: string,
  title: string,
  alt: string,
  caption: string,
  filename: string,
  width: number|null,
  height: number|null
}
```

`caption` provient de la légende WordPress de la pièce jointe. Le normaliseur retire le HTML et retourne une chaîne vide lorsqu'elle est absente.

`filename` est dérivé du chemin de l'URL publique, décodé puis réduit à un nom de fichier sûr. Aucun chemin serveur n'est exposé. Une valeur impossible à déterminer sûrement devient une chaîne vide.

Une pièce jointe invalide, sans type MIME ou sans URL publique est ignorée. Les dimensions peuvent être `null` lorsque WordPress ne les fournit pas.

## Champs Event Data

- `featured_image` : image mise en avant WordPress, distincte du composant ;
- `communication_visual` : premier visuel normalisé, ou `null` ;
- `communication_visuals` : liste ordonnée et dédupliquée des visuels ;
- `other_visuals` : éléments suivant le recto ;
- `event_document` : document PDF unique, ou `null`.

Le composant ne consulte jamais `featured_image` et ne l'ajoute jamais à la liste reçue. La compatibilité historique est traitée en amont par le normaliseur : si une image mise en avant historique a été projetée dans `communication_visuals`, le renderer la traite uniquement comme le premier élément de ce contrat normalisé.

Le recto est toujours `communication_visuals[0]`. L'ordre des autres visuels vient du métier. Le renderer conserve cet ordre, filtre les médias invalides et déduplique par identifiant.

Le document reste séparé des images. La clé historique du PDF n'est jamais réaffectée à un visuel.

## Renderer partagé

Signature :

```php
wp_seed_events_render_public_event_visuals_section( $event, $options = array() )
```

Le premier argument est un résultat Event Data déjà chargé. Le renderer n'accepte ni ID d'événement, ni contexte WordPress, ni accès direct au stockage.

Options :

| Option | Défaut | Valeurs |
| --- | --- | --- |
| `title` | `Visuels de communication` | texte libre, vide autorisé |
| `heading_level` | `h2` | `h2` à `h6` |
| `show_flyer` | `true` | booléen ou valeur booléenne reconnue |
| `show_visuals` | `true` | booléen ou valeur booléenne reconnue |
| `show_document` | `true` | booléen ou valeur booléenne reconnue |
| `show_captions` | `false` | booléen ou valeur booléenne reconnue |
| `image_size` | `large` | taille d'image WordPress enregistrée |
| `link_original` | `true` | booléen ou valeur booléenne reconnue |
| `layout` | `grid` | `grid` ou `list` |

Les valeurs invalides reviennent à un défaut sûr. Une taille d'image inconnue devient `large`.

Ordre de sortie :

1. flyer recto si demandé ;
2. autres visuels si demandés ;
3. document PDF si demandé.

Si aucun élément valide ne reste, le renderer retourne une chaîne vide et ne produit aucun wrapper.

## HTML public

La structure métier est neutre :

- `section.wp-seed-event-visuals` ;
- aucun heading integre ; le titre appartient au builder ;
- `ul.wp-seed-event-visuals__list` ;
- `li.wp-seed-event-visuals__item` ;
- `figure.wp-seed-event-visuals__figure` ;
- image `.wp-seed-event-visuals__image` ;
- légende facultative `.wp-seed-event-visuals__caption` ;
- lien image facultatif `.wp-seed-event-visuals__image-link` ;
- lien PDF `.wp-seed-event-visuals__document-link`.

Les classes `is-layout-grid` et `is-layout-list` indiquent la disposition. Les wrappers natifs éventuels de Divi ou Gutenberg restent extérieurs à cette structure ; le HTML interne du renderer est identique pour tous les adaptateurs.

## CSS partagé

`includes/public/event-visuals.css` fournit uniquement les garanties transversales nécessaires :

- la grille est appliquée à la liste et non à la section ;
- les colonnes utilisent `auto-fit` et restent valides dans un conteneur étroit ;
- les images restent proportionnelles et ne débordent pas ;
- les légendes et noms de PDF longs peuvent revenir à la ligne ;
- les liens conservent une couleur héritée et une décoration explicite ;
- le focus clavier reste visible.

Le thème et les builders restent responsables de la présentation éditoriale. Le CSS partagé n'impose ni palette, ni typographie, ni mise en page décorative.

## Shortcode universel

```text
[wp_seed_event_visuals]
```

Exemple :

```text
[wp_seed_event_visuals title="Documents et visuels" heading_level="h3" show_captions="yes" image_size="large" link_original="yes" layout="grid"]
```

Attributs :

| Attribut | Défaut |
| --- | --- |
| `id` | contexte courant |
| `title` | `Visuels de communication` |
| `heading_level` | `h2` |
| `show_flyer` | `true` |
| `show_visuals` | `true` |
| `show_document` | `true` |
| `show_captions` | `false` |
| `image_size` | `large` |
| `link_original` | `true` |
| `layout` | `grid` |

Les valeurs booléennes reconnues sont `1`, `yes`, `true`, `on` et leurs opposés `0`, `no`, `false`, `off`.

Un `id` explicite invalide produit une sortie vide sans fallback. Sans `id`, le shortcode utilise le contexte public WP Seed Events puis le post courant compatible. Il effectue un appel Event Data et un appel au renderer, sans wrapper supplémentaire.

## Accessibilité

- heading limité à `h2`-`h6` ;
- `aria-label` sur la section lorsque le titre est masqué ;
- liste `ul`/`li` dans l'ordre métier ;
- `figure` et `figcaption` pour les images et légendes ;
- texte alternatif provenant uniquement de WordPress, sans texte inventé ;
- libellés explicites pour l'ouverture des originaux et le PDF ;
- focus visible sur les liens ;
- aucun nouvel onglet imposé.

## Sécurité et performance

- aucune lecture de meta hors normaliseur média ;
- aucun SQL, HTTP distant, shortcode imbriqué ou écriture dans le renderer ;
- URL, attributs et textes échappés côté serveur ;
- médias filtrés par identifiant, URL et type MIME ;
- un appel Event Data et un appel renderer par instance d'adaptateur ;
- une génération WordPress d'image par image rendue ;
- aucune vérification HTTP du fichier à chaque affichage.

Le rendu public ne dépend ni d'iFolders, ni d'Instant Images, ni d'un builder. iFolders intervient uniquement dans l'organisation de la médiathèque en administration. Instant Images crée des pièces jointes WordPress standards.

## Limites V1

- pas de carrousel, lightbox, masonry ou animation ;
- pas de sélection individuelle des médias dans un builder ;
- un seul document PDF ;
- pas d'ouverture automatique dans un nouvel onglet ;
- pas de migration ni de backfill ;
- pas de détection au rendu d'un fichier physique manquant lorsque la pièce jointe et son URL existent ;
- les textes alternatifs et légendes peuvent être vides ; aucun contenu éditorial n'est inventé ;
- la boîte native Image mise en avant est masquée sur les événements ; le support `thumbnail` et la projection du premier visuel dans `_thumbnail_id` restent actifs.
