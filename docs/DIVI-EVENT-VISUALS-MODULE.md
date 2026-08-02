# Module Divi Visuels de communication

## Rôle

Le module Divi 5 `WP Seed — Visuels de communication` est un adaptateur builder autour du renderer public partagé.

Identifiants :

- module : `wp-seed-events/event-visuals` ;
- dossier : `wp-seed-events` (`WP Seed Events`) ;
- classe PHP : `WP_Seed_Events_Divi_Event_Visuals_Module` ;
- classe frontend : `wp_seed_events_divi_event_visuals`.

Les visuels constituent une collection ordonnée. Ils utilisent donc un composant métier dédié, contrairement aux valeurs Dynamic Content scalaires. Le module ne devient pas la source du modèle média ou du HTML.

## Architecture

Le callback frontend :

1. résout l'événement depuis le contexte Divi ;
2. charge une fois `wp_seed_events_get_event_data()` ;
3. normalise les neuf options ;
4. appelle une fois `wp_seed_events_render_public_event_visuals_section()` ;
5. place le HTML dans le wrapper natif du module Divi uniquement s'il n'est pas vide.

Le JavaScript du Visual Builder ne contient aucune logique média et ne construit aucun HTML métier. Aucun ID d'événement, shortcode ou accès meta n'est persisté dans le module.

## Options Contenu

Les valeurs sont conservées dans `content.innerContent.desktop.value` :

| Option | Défaut | Contrôle |
| --- | --- | --- |
| `title` | `Visuels de communication` | texte, vide autorisé |
| `heading_level` | `h2` | sélection `h2` à `h6` |
| `show_flyer` | `on` | afficher ou masquer le recto |
| `show_visuals` | `on` | afficher ou masquer les autres visuels |
| `show_document` | `on` | afficher ou masquer le PDF |
| `show_captions` | `off` | afficher ou masquer les légendes |
| `image_size` | `large` | taille d'image WordPress |
| `link_original` | `on` | activer ou désactiver le lien vers l'original |
| `layout` | `grid` | grille ou liste |

Les tailles proposées sont `thumbnail`, `medium`, `medium_large`, `large` et `full`. Le renderer reste l'autorité de normalisation finale.

## Réglages Design

Les groupes Divi ciblent les sélecteurs stables du renderer :

| Attribut Design | Sélecteur |
| --- | --- |
| `sectionStyle` | `.wp-seed-event-visuals` |
| `titleStyle` | `.wp-seed-event-visuals__title` |
| `listStyle` | `.wp-seed-event-visuals__list` |
| `gridStyle` | `.is-layout-grid .wp-seed-event-visuals__list` |
| `listLayoutStyle` | `.is-layout-list .wp-seed-event-visuals__list` |
| `itemStyle` | `.wp-seed-event-visuals__item` |
| `figureStyle` | `.wp-seed-event-visuals__figure` |
| `imageStyle` | `.wp-seed-event-visuals__image` |
| `captionStyle` | `.wp-seed-event-visuals__caption` |
| `documentStyle` | `.wp-seed-event-visuals__document` |
| `imageLinkStyle` | `.wp-seed-event-visuals__image-link` |
| `documentLinkStyle` | `.wp-seed-event-visuals__document-link` |

Les contrôles couvrent les besoins pertinents selon l'élément : typographie, fond, dimensions, espacement, bordure, rayon, ombre, filtres d'image et variantes responsive. Le module racine conserve également les réglages standard Divi de fond, taille, marges, padding, bordure, ombre, visibilité et position.

## Résolution du contexte

La résolution partagée Divi donne priorité à l'élément de boucle, puis au `post_id`, puis au contexte public WP Seed Events et enfin au post courant compatible.

- fiche événement : événement courant ;
- page modèle rendue depuis une fiche : événement public porteur ;
- page ordinaire : sortie vide ;
- Loop Builder : `loop_id` propre à chaque carte ;
- boucle incompatible : sortie vide sans fallback vers la page porteuse.

Le module ne propose pas de sélecteur d'événement et ne persiste aucun ID fixe.

Le champ image natif Divi peut utiliser en parallele la donnee dynamique
`WP Seed Events - Visuel de communication`. Dans le Visual Builder, sa variante
technique `loop_wp_seed_events_communication_visual` recoit l'URL canonique du
visuel de l'evenement courant depuis `/divi/v1/loop/query-results`. Le frontend
et l'apercu resolvent le meme item ; aucun post global ni visuel precedent ne sert
de fallback.
Dans le Loop Builder, Divi injecte `__loop_post_id` dans le bloc répété. Le renderer PHP donne priorité à cette valeur résolue, puis utilise le résolveur d'ancêtre canonique. L'aperçu React accepte les attributs Divi ordinaires et Immutable ; il transmet le même `loop_id` à la route REST. Aucun contexte n'est conservé entre deux items ou deux instances.

## Aperçu REST

Route :

```text
GET /wp-json/wp-seed-events/v1/divi-event-visuals-preview
```

La route exige `edit_posts` et, lorsqu'un contexte est fourni, la capacité `edit_post` correspondante. Elle accepte uniquement le contexte et les neuf options, puis renvoie le HTML du renderer partagé. Elle n'écrit aucune donnée.

Le Visual Builder affiche un chargement temporaire, un rendu serveur réel, un état vide propre ou une erreur lisible. Les requêtes obsolètes sont annulées ou ignorées. L'état vide attendu est : `Aucun visuel à afficher dans ce contexte.`

## Enregistrement Visual Builder

Le bundle utilise les API Divi 5 `registerFolder()` et `registerModule()` après `divi.moduleLibrary.registerModuleLibraryStore.after`. Le dossier et le module sont enregistrés une seule fois.

Le bundle est chargé uniquement dans l'application Visual Builder avec les dépendances Divi déclarées. Il n'est pas chargé sur le frontend public.

## Accessibilité et responsive

Le module conserve sans modification le HTML sémantique du renderer. Le CSS partagé garantit les dimensions sûres, le retour à la ligne et le focus visible. Les contrôles Divi peuvent enrichir le design sans supprimer ces garanties.

Une sortie métier vide ne produit aucun wrapper Divi. Le module n'impose ni nouvel onglet, ni animation, ni interaction inaccessible.

## Sécurité et performance

- aucun `get_post_meta()` dans l'adaptateur ;
- aucun SQL, shortcode ou ID fixe ;
- route d'aperçu protégée par les capacités WordPress ;
- un appel Event Data et un appel renderer par instance ;
- aucun JavaScript frontend propre au module ;
- aucune dépendance à iFolders, Astra, Spectra ou un plugin média.

## Développement et build

Le workspace Node est partagé à la racine du plugin :

```powershell
npm ci
npm test
npm run lint
npm run build
```

Sources : `includes/integrations/divi/event-visuals-module/visual-builder/src`.

Bundle runtime :

```text
includes/integrations/divi/event-visuals-module/visual-builder/build/wp-seed-events-event-visuals.js
```

React et ReactDOM 18.3.1 sont des dépendances de développement. Divi fournit ses objets runtime dans le Visual Builder.

## Packaging

Le ZIP inclut le bundle compilé et `src/module.json`, requis par l'enregistrement serveur Divi. Il exclut les sources JSX, tests, configuration Webpack, manifests Node et `node_modules`.

## Pages de recette

- module ordinaire et multi-composants : ID `1295` ;
- page modèle : ID `976` ;
- Loop Builder : ID `1205` ;
- fiche de référence : événement ID `914`.

## Limites V1

- aucune sélection individuelle des médias ;
- aucun sélecteur d'événement ;
- aucun carrousel, lightbox, masonry ou animation ;
- aucune ouverture imposée dans un nouvel onglet ;
- un seul document PDF ;
- l'aperçu d'une page ordinaire sans événement reste volontairement vide.

## Apercu dans le module Image natif

Dans une boucle Divi 5, la donnee dynamique `WP Seed Events - Visuel de communication` fournit une URL publique scalaire au module Image natif. Le Visual Builder lit l'item courant du store de boucle et applique cette URL aux attributs runtime de l'image sans modifier le contenu sauvegarde. Le frontend, le lien dynamique vers la fiche evenement et les identifiants historiques restent inchanges.

Un evenement prive, incompatible ou sans visuel reste vide. Aucun fallback vers le post global ou l'item precedent n'est effectue. La recette de reference couvre Divi 5.9.0, deux images distinctes et deux boucles independantes.
