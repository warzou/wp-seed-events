# Bloc Gutenberg Visuels de communication

## Rôle

Le bloc dynamique `WP Seed — Visuels de communication` expose la collection média d'un événement dans Gutenberg et dans les compositions Spectra.

Identifiant :

```text
wp-seed-events/event-visuals-block
```

Le bloc est un adaptateur mince. Il résout le contexte, charge l'Event Data API et délègue le HTML au renderer partagé. Il ne contient aucune logique métier média en JavaScript.

## Métadonnées

Le bloc utilise Block API v3. Le fichier source est `includes/integrations/gutenberg/event-visuals-block/src/block.json` et la copie runtime compilée se trouve dans `build/block.json`.

Contrat :

- catégorie `widgets` ;
- rendu dynamique PHP ;
- `save: () => null` ;
- aucun HTML métier sauvegardé ;
- aucun ID d'événement sérialisé ;
- contextes `postId`, `postType` et `queryId` ;
- aucun script frontend propre au bloc.

## Attributs et inspecteur

Le bloc expose exactement neuf attributs :

| Attribut | Type | Défaut | Contrôle |
| --- | --- | --- | --- |
| `title` | chaîne | `Visuels de communication` | Titre |
| `heading_level` | chaîne | `h2` | Niveau `h2` à `h6` |
| `show_flyer` | booléen | `true` | Afficher le recto |
| `show_visuals` | booléen | `true` | Afficher les autres visuels |
| `show_document` | booléen | `true` | Afficher le document |
| `show_captions` | booléen | `false` | Afficher les légendes |
| `image_size` | chaîne | `large` | Taille d'image |
| `link_original` | booléen | `true` | Lier vers le fichier original |
| `layout` | chaîne | `grid` | Grille ou liste |

L'inspecteur est en français. Les tailles proposées sont `thumbnail`, `medium`, `medium_large` et `large`. La normalisation PHP accepte toute taille WordPress enregistrée et revient à `large` pour une valeur inconnue.

## Rendu serveur

Le callback :

1. lit le contexte du bloc ;
2. résout un `wp_seed_event` valide ;
3. charge une fois `wp_seed_events_get_event_data()` ;
4. appelle une fois `wp_seed_events_render_public_event_visuals_section()` ;
5. ajoute `get_block_wrapper_attributes()` uniquement lorsque le renderer retourne du contenu.

Le wrapper Gutenberg porte la classe `wp-seed-events-event-visuals-block`. Le HTML métier intérieur reste exactement celui du renderer partagé. Une sortie vide retourne une chaîne vide, sans wrapper.

## Résolution du contexte

La règle est partagée avec le bloc Dates :

- contexte `postId`/`postType` explicite et compatible : utiliser cet événement ;
- contexte explicite incompatible ou événement invalide : sortie vide ;
- aucun contexte post explicite : autoriser le contexte public WP Seed Events puis le post courant compatible.

Le fallback public ne s'exécute jamais après un contexte explicite incompatible.

Conséquences :

- fiche événement : événement courant ;
- page modèle rendue depuis une fiche : contexte public de la fiche ;
- page ordinaire : sortie vide ;
- Query Loop Core : événement propre à chaque carte ;
- boucle Spectra : contexte propre à chaque élément ;
- boucle d'un autre type : sortie vide ;
- plusieurs instances : résolutions indépendantes.

## Aperçu éditeur

L'aperçu utilise le renderer PHP réel via :

```text
POST /wp-json/wp-seed-events/v1/gutenberg-event-visuals-preview
```

États :

- chargement avec `Spinner` ;
- rendu prêt ;
- contexte vide avec message explicite ;
- erreur avec `Notice` non masquable.

Les changements sont regroupés pendant 250 ms. `AbortController` annule la requête précédente lorsque possible et un compteur ignore les réponses obsolètes.

La route exige un utilisateur authentifié capable d'éditer des contenus. Elle vérifie `edit_post` pour un `postId` fourni, limite le contexte à `postId`, `postType` et `queryId`, n'écrit rien et n'accepte aucun champ meta arbitraire.

## Query Loop, Spectra et Astra

`wp_seed_event` est disponible dans le Query Loop natif via le contrôleur REST WordPress standard. Aucune meta métier privée n'est exposée par ce choix.

Le bloc fonctionne :

- dans Query Loop Core ;
- dans une boucle `spectra/post` ;
- au milieu de conteneurs Spectra ;
- avec Astra sans adaptateur spécifique ;
- avec les styles et variables `theme.json`.

Le Query Loop reste responsable de sa requête WordPress générique. Les Collections publiques WP Seed conservent leurs règles métier `upcoming`, `past`, `pinned`, type et lifecycle.

## Supports natifs

Le bloc déclare :

- couleurs de texte, fond et liens ;
- marge et padding ;
- taille de police et hauteur de ligne ;
- bordure et rayon ;
- ombre ;
- ancre HTML ;
- classe CSS personnalisée.

`useBlockProps()` dans l'éditeur et `get_block_wrapper_attributes()` au frontend transportent les styles natifs. Le CSS partagé du renderer conserve le responsive, les proportions d'image, le retour à la ligne et le focus visible.

## Accessibilité

Le bloc conserve :

- le heading facultatif `h2` à `h6` ;
- l'`aria-label` lorsque le titre est vide ;
- la liste ordonnée `ul`/`li` ;
- `figure` et `figcaption` ;
- les textes alternatifs WordPress sans fallback inventé ;
- les libellés de liens explicites ;
- le focus clavier visible ;
- l'absence de wrapper vide.

## Sécurité et performance

- aucun ID fixe, shortcode, SQL ou accès meta dans l'adaptateur ;
- aucune logique média ou HTML métier dans JavaScript ;
- route d'aperçu protégée par authentification et capacités ;
- un appel Event Data et un appel renderer par instance ;
- debounce de 250 ms et annulation des aperçus obsolètes ;
- aucun JavaScript frontend propre au bloc ;
- aucune dépendance à Divi, Astra, Spectra, iFolders ou Instant Images.

## Développement et build

Depuis la racine du plugin :

```powershell
npm ci
npm test
npm run lint
npm run build
```

Assets runtime :

- `build/block.json` ;
- `build/index.js` ;
- `build/index.asset.php`.

React et ReactDOM 18.3.1 sont verrouillés dans le workspace de développement. Les dépendances Node ne sont pas livrées au runtime.

## Packaging

Le ZIP inclut uniquement les trois assets compilés avec le PHP du bloc. Il exclut les sources JavaScript, tests, manifests Node, `node_modules`, fixtures, profils et fichiers temporaires.

## Pages de recette

- Gutenberg et Block Bindings : ID `998` ;
- Query Loop / Spectra : ID `1202` et pages temporaires supprimées après recette ;
- page modèle : ID `976` ;
- fiche de référence : événement ID `914` ;
- page multi-composants : ID `1295`.

## Limites V1

- aucune sélection individuelle des médias ;
- aucun sélecteur manuel d'événement ;
- aucun carrousel, lightbox, masonry ou animation ;
- un seul document PDF ;
- aucune ouverture imposée dans un nouvel onglet ;
- aucune requête métier de collection dans le bloc ;
- aucun adaptateur Astra ou Spectra spécifique.
