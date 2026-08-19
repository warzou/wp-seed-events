# Dynamic Data publics des événements

## Rôle

WP Seed Events expose un registre unique de valeurs publiques simples pour les
constructeurs de page. Ce registre adapte l'Event Data API ; il ne lit jamais
directement le stockage WordPress et ne redéfinit aucune règle métier.

La chaîne de référence est :

```text
contexte builder
  -> registre Dynamic Data
  -> cache Event Data limité à la requête PHP
  -> valeur typée text, url ou image
  -> adaptateur Divi ou Gutenberg
```

Les données simples sont destinées aux champs natifs des builders. Les composants structurés Dates, Visuels de communication et Personnes restent dédiés, car ils portent un ordre, une cardinalité variable, des permissions ou des états qui ne peuvent pas être réduits à une chaîne.

## Registre canonique

Le registre `wp_seed_events_dynamic_data_fields()` contient exactement
17 sources : 13 textes, 3 URL et 1 image.

| Clé | Libellé | Type | Projection Event Data | Valeur absente |
| --- | --- | --- | --- | --- |
| `title` | Titre | `text` | `title` | chaîne vide |
| `types` | Types | `text` | `types[]`, séparés par une virgule | chaîne vide |
| `status` | Statut | `text` | `lifecycle`, libellé public localisé | chaîne vide |
| `next_date` | Prochaine date | `text` | `next_occurrence` formatée | chaîne vide |
| `next_time` | Prochaine heure | `text` | heure de `next_occurrence` | chaîne vide |
| `display_date` | Date affichée | `text` | `display_occurrence` formatée | chaîne vide |
| `display_time` | Heure affichée | `text` | heure de `display_occurrence` | chaîne vide |
| `place` | Lieu | `text` | `place.name` | chaîne vide |
| `place_address` | Adresse du lieu | `text` | `place_address` | chaîne vide |
| `description` | Description complète | `text` | `description` sans HTML ni shortcode | chaîne vide |
| `excerpt` | Description courte effective | `text` | `excerpt`, retours à la ligne conservés | chaîne vide |
| `practical_info` | Informations pratiques | `text` | `practical_info`, retours à la ligne conservés | chaîne vide |
| `event_document_filename` | Nom du document | `text` | `event_document_filename` | chaîne vide |
| `url` | URL de l'événement | `url` | `url` canonique | chaîne vide |
| `place_url` | URL du lieu | `url` | `place_url` | chaîne vide |
| `event_document_url` | URL du document | `url` | `event_document_url` | chaîne vide |
| `communication_visual` | Visuel de communication | `image` | `communication_visual` | objet vide |

Ces clés sont canoniques. Aucun alias supplémentaire ne doit être ajouté pour
un builder particulier.

## Cache par requête

`wp_seed_events_dynamic_data_get_event_data()` mémorise le résultat de
`wp_seed_events_get_event_data()` par identifiant d'événement dans une variable
statique PHP. Les résultats valides et vides sont tous deux mémorisés.

Le cache :

- vit uniquement pendant la requête PHP courante ;
- est partagé par les résolveurs texte, URL et image ;
- ne crée ni transient, ni option, ni cache objet persistant ;
- ne modifie aucune donnée ;
- conserve une entrée distincte par événement, sans contamination entre cartes ;
- invalide l'événement lorsque son contenu ou sa meta de description courte est ajouté, modifié ou supprimé.

Sur la recette de référence de sept événements et seize sources, le nombre
d'appels Event Data est passé de 112 à 7 et les passes Occurrences de 560 à 35.
Les mesures observées sont passées de 3,3908 ms à 0,5983 ms en moyenne et de
6,1099 ms à 2,0338 ms au p95.

## Résolution du contexte

La règle Gutenberg est impérative :

- un contexte explicite `postId`/`postType` désignant un `wp_seed_event` publié
  et valide utilise cet événement ;
- un contexte explicite incompatible ou invalide retourne une valeur vide ;
- le fallback vers le contexte public WP Seed Events ou le post courant est
  autorisé uniquement lorsqu'aucun contexte post explicite n'est fourni.

Les anciens arguments `eventId` et `event_id` restent tolérés pour la
compatibilité des contenus existants, mais ne peuvent jamais contourner un
contexte Gutenberg explicite incompatible. Aucun nouvel adaptateur ne doit
sérialiser un identifiant d'événement.

Divi applique la même isolation : l'élément de boucle compatible est prioritaire,
puis le contexte événement courant. Un élément de boucle réel mais incompatible
produit une valeur vide.

| Contexte | Résultat |
| --- | --- |
| fiche `wp_seed_event` | valeur de la fiche |
| page modèle rendue depuis une fiche | valeur de l'événement public |
| page modèle éditée seule | valeur vide |
| boucle d'événements | valeur propre à chaque carte |
| boucle d'un autre type | valeur vide |
| page ordinaire | valeur vide |
| brouillon non public | valeur vide |

## Contrat texte

Les valeurs texte sont neutres et sans HTML composite. Les shortcodes sont
retirés de la description et des informations pratiques. Les balises HTML sont
supprimées ; les retours à la ligne utiles des informations pratiques sont
conservés. Les types multiples sont rendus dans leur ordre sous forme de liste
textuelle séparée par des virgules.

Les valeurs absentes, les lieux absents, les documents absents, les contextes
incompatibles et les événements non publics retournent une chaîne vide. Le
registre n'invente aucun libellé de remplacement.

## Contrat URL

Les trois URL acceptent uniquement une URL absolue dont le schéma est `http` ou
`https` et dont l'hôte est présent. Les URL relatives et les schémas
`javascript:`, `data:`, `file:`, `mailto:` et `tel:` sont rejetés.

Le document complémentaire est exposé seulement lorsque l'Event Data API
fournit un document PDF valide. Une valeur refusée devient une chaîne vide ;
aucun chemin serveur n'est jamais exposé.

## Contrat image

`communication_visual` est l'unique source image. Elle représente le premier
élément normalisé de `communication_visuals` et expose aux builders :

- `id` ;
- `url` ;
- `alt` ;
- `title` ;
- `caption`.

L'objet est accepté seulement avec un identifiant de pièce jointe, une URL
publique absolue HTTP/HTTPS et un MIME `image/*`. Un PDF, un média invalide ou
une URL absente produit un objet vide. Un SVG peut être référencé comme média
image valide, mais n'est jamais injecté en ligne par le registre.

`featured_image` reste une projection WordPress distincte. Elle n'est jamais
utilisée comme fallback et n'est jamais ajoutée à `communication_visuals`.

## Divi 5

Divi enregistre un provider générique par type logique : texte, URL et image.
Les 17 options sont générées depuis le registre et apparaissent une seule fois
dans le groupe `WP Seed Events` avec leurs libellés français.

- les textes alimentent les champs texte natifs ;
- les URL alimentent les champs lien natifs avec le type Divi `url` ;
- le visuel alimente les champs image natifs avec le type Divi `image` ;
- `wp_seed_events_next_date` reste disponible comme nom historique compatible.

Les providers utilisent l'API class-based publique de Divi 5 déjà validée. Ils
ne contiennent ni shortcode, ni ID fixe, ni lecture de meta, ni HTML métier.

Dans une boucle Divi, la source image enregistre aussi la variante technique
`loop_wp_seed_events_communication_visual`. La route publique Divi
`/divi/v1/loop/query-results` est enrichie uniquement pour les items
`wp_seed_event` publics avec l'URL canonique de leur visuel. Le contexte de boucle
canonique est reutilise sans fallback vers le post global ; un evenement prive,
incompatible ou sans visuel reste vide, y compris apres un autre item renseigne.
## Gutenberg et Spectra

Gutenberg conserve la source Block Bindings événement historique :

```text
wp-seed-events/event-field
```

Il ajoute une source strictement contextuelle pour les collections
d’occurrences :

```text
wp-seed-events/occurrence-field
```

La seconde reste vide hors du contexte explicite fourni par une Collection
d’occurrences Gutenberg ou Divi. Les deux adaptateurs installent le même contexte
canonique et le restaurent après chaque élément. La source n’infère jamais une
occurrence depuis le seul post événement.

L'argument `field` sélectionne l'une des 17 clés du registre. Les usages validés
sont :

- `core/paragraph` et `core/heading` pour `content` ;
- `core/button` pour `url` ;
- `core/image` pour `id`, `url`, `alt`, `title` et `caption`.

La même source fonctionne dans une fiche, une page modèle et le Query Loop Core. Elle ne sérialise aucun ID d’événement et n’ajoute aucun shortcode. Spectra reste facultatif, absent du site de référence et sans compatibilité runtime avancée revendiquée dans cette alpha.

Exemple de binding texte :

```html
<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"wp-seed-events/event-field","args":{"field":"next_date"}}}}} -->
<p></p>
<!-- /wp:paragraph -->
```

Exemple de binding URL :

```html
<!-- wp:button {"metadata":{"bindings":{"url":{"source":"wp-seed-events/event-field","args":{"field":"url"}}}}} -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button">Voir l'événement</a></div>
<!-- /wp:button -->
```

## Valeurs simples et composants structurés

Dynamic Data convient aux valeurs unitaires : titre, date de référence, lieu,
URL ou visuel principal de communication.

Les collections suivantes utilisent le renderer partagé et leurs adaptateurs
dédiés :

- Dates : occurrences ordonnées, états, horaires et liens calendrier ;
- Visuels de communication : recto, autres visuels et document complémentaire ;
- Personnes : rôles multiples et coordonnées publiques filtrées.

Le shortcode reste le fallback universel. Les modules Divi et blocs Gutenberg
restent des adaptateurs minces autour des mêmes renderers. Aucun composant ne
doit dupliquer le métier dans React ou dans un builder.

## Sécurité

Le registre et ses adaptateurs garantissent :

- événements publics valides uniquement ;
- contexte explicite incompatible toujours vide ;
- aucune donnée privée de Personnes ni coordonnée publique automatique ;
- aucune lecture directe de meta, requête SQL ou écriture ;
- aucune URL autre que HTTP/HTTPS absolue ;
- aucun chemin serveur ;
- aucun SVG injecté en ligne ;
- aucune source Gutenberg concurrente ;
- aucun cache persistant.

L'échappement final reste la responsabilité du consommateur, selon son
attribut cible. Le registre fournit des projections neutres déjà normalisées.

## Limites

- aucune collection média n'est exposée comme Dynamic Data simple ;
- aucune occurrence complète n'est exposée comme valeur simple ;
- aucun sélecteur manuel d'événement n'est ajouté ;
- aucune donnée détaillée de Personnes n'est exposée ;
- aucune coordonnée de Personne n'est rendue automatiquement publique ;
- aucune nouvelle source ne doit être ajoutée sans contrat Event Data public et
  besoin builder validé.

Le composant Personnes existe comme renderer structuré et comme adaptateur Divi et Gutenberg. Les coordonnées restent volontairement absentes de Dynamic Data : elles ne peuvent être rendues que par le composant Personnes après filtrage des permissions de publication.

## Apercu image Divi dans une boucle

La source image `communication_visual` reste une URL publique HTTP/HTTPS scalaire. Pour le canvas Divi 5, l'adaptateur de preview recupere cette valeur dans l'item courant de `/divi/v1/loop/query-results` et la transmet au module Image natif. Il ne change ni le contrat Event Data ni la valeur frontend, et ne reutilise jamais le post global ou un item precedent.
