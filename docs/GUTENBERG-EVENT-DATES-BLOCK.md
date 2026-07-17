# Bloc Gutenberg Dates de l'événement

## Rôle

Le bloc dynamique `WP Seed — Dates de l'événement` expose dans Gutenberg la collection structurée des occurrences d'un événement.

Identifiant du bloc :

```text
wp-seed-events/event-dates-block
```

Le bloc est un adaptateur builder. Il ne définit ni le métier des occurrences, ni leur ordre, ni leur HTML public. Une valeur Block Binding convient à une donnée simple, par exemple `next_date`. Les dates forment une collection ordonnée avec des états, des horaires et des actions : elles utilisent donc un composant dédié.

Cette exception reste limitée aux collections métier Dates et Visuels de communication. Elle n'autorise pas un bloc par champ.

## Architecture

La chaîne de référence est :

`Event Occurrences API → Event Data API → renderer partagé Dates → consommateurs`.

Les consommateurs du même renderer sont :

- le template public natif ;
- le shortcode universel `[wp_seed_event_dates]` ;
- le module Divi 5 Dates ;
- le bloc Gutenberg/Spectra Dates.

Le chemin Gutenberg appelle une fois `wp_seed_events_get_event_data()`, puis une fois `wp_seed_events_render_public_event_dates_section()`. Il ne lit aucune meta privée, n'exécute aucune requête SQL, n'appelle aucun shortcode et ne reconstruit aucun HTML métier en JavaScript.

## Métadonnées du bloc

Le fichier source est `includes/integrations/gutenberg/event-dates-block/src/block.json`. Sa copie compilée est livrée dans `build/block.json`.

Contrat :

- Block API v3 ;
- catégorie `widgets` ;
- rendu dynamique PHP ;
- `save: () => null` dans le code éditeur ;
- aucun HTML sauvegardé dans `post_content` ;
- aucun identifiant d'événement sérialisé ;
- contextes consommés : `postId`, `postType` et `queryId` ;
- script frontend absent ;
- alignement non déclaré, afin de ne pas proposer un support fictif.

L'enregistrement PHP utilise `register_block_type_from_metadata()` une seule fois et fournit le callback serveur `wp_seed_events_render_gutenberg_event_dates_block()`.

## Attributs

Le bloc expose exactement six attributs :

| Attribut | Type | Défaut | Valeurs |
| --- | --- | --- | --- |
| `title` | chaîne | `Dates` | chaîne libre, vide autorisé |
| `heading_level` | chaîne | `h2` | `h2` à `h6` |
| `scope` | chaîne | `all` | `all`, `upcoming`, `past` |
| `show_cancelled` | booléen | `true` | afficher ou masquer les occurrences annulées |
| `show_times` | booléen | `true` | afficher ou masquer les horaires |
| `show_calendar_links` | booléen | `true` | afficher ou masquer les actions calendrier |

Les valeurs invalides reviennent aux valeurs sûres du contrat : `h2`, `all` et options booléennes activées. Un titre vide supprime le heading sans produire de wrapper vide.

Le filtrage `upcoming` et `past` consomme exclusivement les projections neutres `is_date_future` et `is_date_past` fournies par l'Event Occurrences API. Le bloc ne recalcule aucune date.

## Rendu serveur

Le callback du bloc :

1. résout l'événement depuis le contexte Gutenberg ;
2. charge son contrat Event Data ;
3. normalise les six options ;
4. délègue au renderer partagé ;
5. ajoute le wrapper natif Gutenberg avec `get_block_wrapper_attributes()` uniquement lorsque le renderer retourne du HTML.

Une sortie métier vide produit une chaîne vide. Aucun conteneur Gutenberg vide n'est envoyé au frontend.

## Résolution du contexte

La règle est impérative :

- contexte `postId`/`postType` explicite et compatible : utiliser le `wp_seed_event` ciblé ;
- contexte explicite incompatible ou événement invalide : retourner vide ;
- absence de contexte post explicite : autoriser le contexte public WP Seed Events, puis le post WordPress courant s'il s'agit d'un événement.

Le fallback public ne s'exécute jamais après un contexte explicite incompatible. Cette garde empêche une boucle Pages imbriquée dans une fiche événement d'hériter accidentellement de l'événement porteur.

Conséquences :

- fiche événement : contexte de la fiche ;
- page modèle rendue depuis une fiche : contexte public de l'événement ;
- page ordinaire : sortie vide ;
- Query Loop d'événements : contexte propre à chaque carte ;
- Query Loop d'un autre type : sortie vide ;
- plusieurs instances : résolution indépendante, sans ID fixe.

## Page modèle

Une page modèle WordPress n'est pas elle-même un événement. Éditée seule, elle peut donc afficher l'état vide. Lorsqu'elle est utilisée par WP Seed Events pour rendre une fiche publique, le plugin fournit le contexte public de l'événement et le même bloc devient contextuel.

Aucun identifiant d'événement n'est ajouté aux attributs pour simuler ce comportement.

## Inspecteur et aperçu éditeur

L'inspecteur propose six contrôles en français :

- Titre ;
- Niveau du titre ;
- Portée ;
- Afficher les occurrences annulées ;
- Afficher les horaires ;
- Afficher les liens calendrier.

L'aperçu éditeur utilise le HTML réel du renderer serveur. Il présente des états distincts :

- chargement avec `Spinner` ;
- contenu prêt ;
- contexte vide avec un message explicite ;
- erreur avec une `Notice` non masquable.

Les changements sont regroupés par un délai de 250 ms. `AbortController` annule la requête précédente lorsque possible, un compteur de séquence ignore les réponses obsolètes et le nettoyage React annule le timer. Aucun chargement infini n'a été retenu dans les recettes G3 à G5.

## Route d'aperçu

Route :

```text
POST /wp-json/wp-seed-events/v1/gutenberg-event-dates-preview
```

La route :

- est réservée aux utilisateurs authentifiés capables d'éditer des contenus ;
- vérifie `edit_post` lorsqu'un `postId` est transmis ;
- accepte uniquement `attributes` et un contexte réduit à `postId`, `postType` et `queryId` ;
- renvoie `html`, `empty` et `message` ;
- n'écrit aucune donnée ;
- ne lit aucune meta privée directement ;
- refuse un appel anonyme.

La route d'aperçu est distincte de l'API REST standard du CPT.

## REST du CPT événement

`wp_seed_event` est déclaré avec `show_in_rest => true` afin d'être disponible dans le Query Loop natif. Le contrôleur REST WordPress standard est conservé : aucun contrôleur, champ REST ou base REST spécifique n'est ajouté.

Les supports, capacités et l'éditeur classique du CPT restent inchangés. Aucune meta métier privée n'est enregistrée pour l'exposition REST. Les lectures de collection, d'item, de recherche et de pagination suivent donc les permissions WordPress standard.

`wp_seed_place` reste déclaré avec `show_in_rest => false`.

## Query Loop et collections publiques

Le Query Loop Core et la boucle `spectra/post` peuvent composer des cartes génériques en fournissant un contexte `postId`/`postType` distinct à chaque bloc Dates. Plusieurs blocs Dates peuvent être placés dans une même carte sans contamination entre événements.

Le Query Loop n'est pas une nouvelle Collection publique WP Seed. Il ne reçoit aucune règle métier supplémentaire du plugin.

Les règles suivantes restent portées par les Collections publiques WP Seed :

- `upcoming` ;
- `past` ;
- `pinned` ;
- filtre par type ;
- lifecycle.

Le Query Loop reste responsable de sa requête WordPress générique ; le bloc Dates reste responsable uniquement du rendu contextuel de la collection d'occurrences déjà normalisée.

## Astra, Spectra et Gutenberg natif

La recette G5 a validé le bloc sans adaptateur spécifique avec :

- Astra 4.13.6 ;
- Spectra Blocks 1.0.0 ;
- Spectra Legacy 2.20.0 ;
- Gutenberg natif ;
- Query Loop Core ;
- boucle `spectra/post` ;
- conteneurs Spectra.

Le bloc ne dépend ni d'Astra, ni de Spectra, ni de Divi. Spectra compose le bloc comme n'importe quel bloc dynamique Gutenberg. Astra fournit le thème et les styles globaux sans logique métier dédiée.

## Supports et design natifs

Le bloc déclare uniquement les supports Gutenberg validés :

- couleur de texte ;
- couleur de fond ;
- couleur des liens ;
- marge ;
- padding ;
- taille de police ;
- hauteur de ligne ;
- ancre HTML ;
- classe CSS personnalisée.

Le wrapper issu de `useBlockProps()` dans l'éditeur et de `get_block_wrapper_attributes()` au frontend applique les styles natifs, les classes et les valeurs `theme.json`.

Le bloc n'ajoute aucun CSS métier lourd, aucune valeur Divi et aucune dépendance au thème. Le responsive repose sur la structure partagée, les styles Gutenberg et le thème actif.

## Accessibilité

Le renderer partagé conserve :

- une section sémantique ;
- un heading facultatif limité à `h2`–`h6` ;
- un `aria-label` lorsque le titre est vide ;
- une liste `ul`/`li` dans l'ordre canonique ;
- une balise `time` avec `datetime` par occurrence ;
- le statut visible `Annulée` ;
- des liens calendrier explicites et accessibles au clavier ;
- des icônes décoratives avec `aria-hidden="true"` ;
- aucune sortie partielle pour une date invalide ;
- aucun wrapper vide lorsqu'aucune occurrence n'est retenue.

Les recettes Gutenberg, Astra et Spectra ont validé le responsive sans débordement et des contrastes raisonnables dans les environnements testés. Le thème reste responsable de son contraste final.

## Sécurité

Le bloc :

- ne sérialise aucun ID d'événement ;
- n'accepte aucun accès arbitraire à une meta ;
- n'exécute ni SQL, ni shortcode ;
- échappe les attributs et textes via le renderer partagé ;
- filtre le HTML des liens calendrier ;
- protège sa route d'aperçu par les capacités WordPress ;
- n'ajoute aucune écriture, migration, table, option ou meta.

L'ouverture REST de `wp_seed_event` expose uniquement les champs standard du contrôleur WordPress. Elle ne rend pas publiques les metas métier privées.

## Performance

Un rendu du bloc effectue un appel Event Data et un appel au renderer partagé. La logique des occurrences n'est pas recalculée dans l'adaptateur.

Dans l'éditeur :

- délai de 250 ms avant aperçu ;
- annulation des requêtes précédentes ;
- réponses obsolètes ignorées ;
- aucune boucle de requêtes observée avec plusieurs instances.

Le frontend ne charge aucun JavaScript propre au bloc : seul le HTML serveur est livré. Le bloc n'ajoute aucun asset Divi.

## Développement et build

Le workspace Node est partagé à la racine du plugin. Prérequis : Node.js 18.12 ou plus récent et npm 10 ou plus récent.

```powershell
npm ci
npm test
npm run lint
npm run build
```

Les scripts produisent séparément :

- le bundle Visual Builder Divi ;
- `build/index.js`, `build/index.asset.php` et `build/block.json` pour Gutenberg.

React et ReactDOM 18.3.1 sont verrouillés dans le workspace de développement. Les bundles compilés sont suivis et doivent rester reproductibles. `node_modules` reste ignoré et non suivi.

Les alertes `npm audit` concernent l'arbre d'outillage de développement ; cet arbre n'est pas livré dans le plugin runtime.

## Packaging

`build-dev-zip.ps1` vérifie la présence des trois assets Gutenberg et du bundle Divi.

Le ZIP de développement inclut les fichiers PHP runtime, le bundle Divi et :

- `build/block.json` ;
- `build/index.js` ;
- `build/index.asset.php`.

Il exclut :

- `node_modules` ;
- `package.json` et `package-lock.json` ;
- les sources JavaScript et JSX ;
- les tests et configurations de build ;
- les fixtures et profils de recette ;
- les secrets ;
- les extensions temporaires `.patch`, `.next`, `.tmp` et `.bak`.

## Shortcode et module Divi

`[wp_seed_event_dates]` reste le fallback universel. Il utilise le même renderer, mais n'est pas l'expérience builder principale.

Le module Divi `wp-seed-events/event-dates` est l'adaptateur équivalent pour Divi 5. Il expose le même contenu métier et ajoute les contrôles Design natifs de Divi. Aucun des deux adaptateurs ne doit devenir la source du métier ou du HTML Dates.

## Limites V1

- aucun sélecteur manuel d'événement ;
- aucun ID d'événement persistant ;
- aucune pagination propre au bloc ;
- aucun tri configurable ;
- aucune requête métier de collection dans le bloc ;
- aucune mise en page galerie, grille ou carrousel ;
- aucune intégration Astra ou Spectra spécifique ;
- aucune exposition REST des metas métier privées ;
- aucun composant Visuels ou Personnes inclus dans ce lot.

Les composants Visuels de communication, les Dynamic Data simples complémentaires et l'évaluation des Personnes détaillées constituent des lots ultérieurs séparés.
