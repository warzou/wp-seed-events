# Event Media Model

## Décision

WP Seed Events utilise une liste métier ordonnée unique pour représenter les visuels de communication d'un événement.

Cette liste est indépendante du rendu, des builders et des noms historiques utilisés dans le stockage WordPress.

Le modèle retenu est :

```text
communication_visuals
  -> communication_visual
  -> other_visuals

communication_visual
  -> featured_image WordPress

event_document
  -> document complémentaire indépendant
```

## Concepts métier

### communication_visuals

`communication_visuals` est une liste ordonnée, dédupliquée et composée uniquement de médias image valides.

Son ordre est significatif :

1. flyer recto ;
2. verso ;
3. pages suivantes ;
4. autres visuels de communication.

Cette liste constitue l'unique source de vérité métier pour les visuels de communication.

### communication_visual

`communication_visual` représente le flyer recto et le visuel principal métier de l'événement.

Il est toujours dérivé de la liste :

```text
communication_visuals[0] ?? null
```

Il n'est jamais stocké séparément.

### other_visuals

`other_visuals` représente le verso, les pages suivantes et les autres visuels de communication.

Il est toujours dérivé de la liste :

```text
communication_visuals[1...]
```

Il n'est jamais stocké séparément.

### event_document

`event_document` représente un document complémentaire indépendant, généralement un PDF : programme, brochure détaillée ou autre document à télécharger.

Il ne fait jamais partie de `communication_visuals`.

## Règles invariantes

- `communication_visuals` est la seule source de vérité métier pour les visuels.
- `communication_visual` et `other_visuals` sont toujours dérivés de cette liste.
- `featured_image` n'est pas une source métier.
- `featured_image` est une projection WordPress du premier visuel.
- La synchronisation est unidirectionnelle : `communication_visuals[0]` vers `featured_image`.
- Aucun élément nul ou invalide ne peut exister dans `communication_visuals`.
- La liste est soit vide, soit commence par un média image valide.
- Un même identifiant média ne peut apparaître qu'une seule fois dans la liste.
- L'ordre des médias est significatif et doit être conservé.
- `event_document` reste toujours séparé de la liste d'images.

## UX cible

L'éditeur présente les concepts métier séparément, même s'ils reposent sur une seule liste ordonnée.

### Visuels de communication

#### Flyer recto

Texte d'aide :

> Visuel principal de l'événement. Il sera utilisé notamment dans les cartes, les listes et les partages.

Actions :

- Choisir une image ;
- Remplacer ;
- Retirer.

Choisir ou remplacer le flyer recto place l'image en première position. Retirer le recto promeut automatiquement le visuel suivant lorsqu'il existe.

#### Autres visuels

Texte d'aide :

> Ajoutez le verso ou les autres pages de la brochure dans l'ordre souhaité.

Actions :

- Ajouter des visuels ;
- Monter ;
- Descendre ;
- Définir comme flyer recto ;
- Retirer.

Définir un visuel comme flyer recto le place en première position. L'ancien recto reste dans la liste parmi les autres visuels afin d'éviter une perte involontaire.

Le glisser-déposer peut compléter les actions Monter et Descendre, mais ne doit jamais être le seul moyen de réorganisation.

### Document complémentaire (PDF)

Texte d'aide :

> Programme, brochure détaillée ou document à télécharger.

Actions :

- Choisir un PDF ;
- Remplacer ;
- Retirer.

Le terme « Flyer PDF » est historique et doit disparaître de l'interface cible.

## Stockage actuel et cible

Le premier modèle cible réutilise les stockages existants :

| Concept métier | Stockage WordPress |
| --- | --- |
| `communication_visuals` | `_wp_seed_event_illustration_ids` |
| `featured_image` | `_thumbnail_id` |
| `event_document` | `_wp_seed_event_flyer_pdf_id` |

Les règles suivantes sont figées :

- aucun nouveau champ `flyer_recto` ;
- aucun nouveau champ `other_visuals` ;
- aucune seconde liste média ;
- les noms historiques peuvent rester en base ;
- le contrat métier ne reprend pas nécessairement les noms historiques du stockage.

Lors d'une future sauvegarde explicite, la liste métier sera validée, dédupliquée et conservée dans son ordre. Son premier élément sera projeté dans `_thumbnail_id`. Si la liste devient vide, la projection WordPress correspondante sera retirée.

## Rétrocompatibilité

Les événements existants sont normalisés virtuellement selon les règles suivantes :

| Données existantes | Résultat normalisé |
| --- | --- |
| Image mise en avant seule | Elle devient le flyer recto |
| Illustrations seules | La première illustration devient le flyer recto |
| Image mise en avant et illustrations | L'image mise en avant est placée en tête |
| Image mise en avant déjà présente dans les illustrations | Elle est déplacée en tête sans doublon |
| PDF seul | Aucun visuel ; le document est conservé |
| Aucun média | `communication_visuals` est une liste vide |

Cette compatibilité respecte quatre contraintes :

- aucune migration globale ;
- aucune écriture pendant la lecture ;
- persistance uniquement lors d'une sauvegarde explicite future ;
- après persistance du modèle normalisé, `communication_visuals` gagne toujours en cas de divergence avec `_thumbnail_id`.

Un marqueur technique de version du modèle média peut être utilisé lors de la future persistance pour distinguer les données historiques des événements déjà normalisés. Ce marqueur ne constitue pas une donnée métier.

## Contrat API définitif

### Objet Media

Chaque média est exposé sous la forme d'un objet neutre :

```text
{
  id: number,
  url: string,
  mime_type: string,
  title: string,
  alt: string,
  width: number|null,
  height: number|null
}
```

### Champs Event Data API

```text
featured_image: Media|null
communication_visual: Media|null
communication_visuals: Media[]
other_visuals: Media[]
event_document: Media|null
```

Conventions :

- un média absent vaut `null` ;
- une liste sans média vaut `[]` ;
- aucun HTML n'est retourné ;
- aucun bouton, aucune galerie et aucun style ne font partie du contrat.

Compatibilité :

- `illustrations` peut rester temporairement un alias déprécié de `communication_visuals` ;
- `flyer` et `flyer_pdf` peuvent rester temporairement des alias dépréciés de `event_document` ;
- `flyer` ne doit jamais être réaffecté silencieusement à une image.

## Constructeurs de page

Les providers exposent les données, jamais leur présentation.

Les builders peuvent consommer :

- `communication_visual` pour une image simple ;
- `communication_visuals` pour une galerie, une grille ou un carrousel ;
- `other_visuals` pour le verso et les pages complémentaires ;
- `event_document` pour un lien ou un bouton de téléchargement.

`featured_image` reste une donnée WordPress native distincte.

WP Seed Events ne choisit jamais :

- la galerie ;
- la grille ;
- le carrousel ;
- le style ;
- le bouton ;
- l'ordre de mise en page.

## Prochaines étapes

### Lot 1

Créer un normaliseur média interne en lecture seule, sans écriture en base et sans changement visible.

### Lot 2

Exposer le modèle média dans l'Event Data API sans supprimer les anciennes clés de compatibilité.

### Lot 3

Mettre en place la nouvelle UX de l'éditeur et la synchronisation unidirectionnelle avec `featured_image`.

### Lot 4

Exposer les données média par les futurs providers builders, sans imposer de rendu.

## Principe durable

WP Seed Events possède le modèle métier des médias.

WordPress reçoit une projection compatible de son visuel principal.

Les builders reçoivent des données neutres et restent entièrement responsables de la présentation.
