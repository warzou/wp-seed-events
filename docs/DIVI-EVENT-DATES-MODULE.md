# Module Divi Dates de l'événement

## Rôle

Le module Divi 5 `WP Seed — Dates de l'événement` est un adaptateur builder pour la collection structurée des occurrences. Il ne contient aucune logique métier de date : il résout le contexte, lit l'Event Data API une fois et délègue le HTML à `wp_seed_events_render_public_event_dates_section()`.

Identifiants persistants :

- module : `wp-seed-events/event-dates` ;
- dossier Divi : `wp-seed-events` (`WP Seed Events`) ;
- classe module : `wp_seed_events_divi_event_dates`.

Une valeur Dynamic Content convient à une donnée simple, par exemple `next_date`. Les dates constituent une collection ordonnée avec des états, des horaires et des actions : elles utilisent donc un composant dédié. Cette exception reste limitée aux collections métier Dates et Visuels. Les personnes détaillées sont un besoin futur non commencé ; leurs coordonnées publiques ne devront être exposées que lorsqu'elles sont explicitement activées.

## Architecture universelle

La chaîne de référence est :

`Event Occurrences API → Event Data API → renderer partagé Dates → consommateurs`.

Les consommateurs actuels sont le template natif, le shortcode universel et l'adaptateur Divi. Un futur bloc Gutenberg/Spectra devra appeler le même renderer. Astra pourra consommer ce bloc ou le shortcode de fallback. Aucun HTML métier n'est reconstruit dans React et aucun couplage métier à Divi n'est autorisé.

La partie PHP est chargée depuis `includes/integrations/divi/bootstrap.php` lorsque l'API Divi 5 est disponible. `class-event-dates-module.php` enregistre les métadonnées, le callback frontend et l'endpoint de prévisualisation. Le bundle Visual Builder transmet uniquement le contexte et les attributs, puis affiche le HTML renvoyé par le serveur.

Le module ne lit aucune meta privée, n'exécute aucune requête SQL, ne persiste aucun ID d'événement et n'appelle aucun shortcode.

## Attributs Contenu

Les valeurs sont stockées dans `content.innerContent.desktop.value` :

- `title` : texte, `Dates` par défaut, vide autorisé ;
- `heading_level` : `h2`, `h3`, `h4`, `h5` ou `h6` ;
- `scope` : `all`, `upcoming` ou `past` ;
- `show_cancelled` : `on` ou `off` ;
- `show_times` : `on` ou `off` ;
- `show_calendar_links` : `on` ou `off`.

Les valeurs invalides reviennent aux valeurs sûres : `h2`, `all` et options activées. Le niveau de titre ne modifie que le heading facultatif ; la structure métier reste `section`, heading éventuel, `ul`, `li`, `time`, `span` et `a`.

## Réglages Design

Les attributs Design utilisent les mécanismes natifs Divi et ciblent strictement le module courant :

- `titleStyle` : `.wp-seed-event-dates__title` ;
- `dateStyle` : `.wp-seed-event-date__date` ;
- `timeStyle` : `.wp-seed-event-date__time` ;
- `statusStyle` : `.wp-seed-event-date__status` ;
- `calendarLinkStyle` : `.wp-seed-event-calendar-link` ;
- `occurrenceStyle` : `.wp-seed-event-date` ;
- `module` : sélecteur racine Divi.

Les groupes exposent la typographie, la taille, la graisse, la couleur et les variantes responsive des textes concernés. Le titre peut aussi être aligné et espacé. Les occurrences disposent de leur espacement. Le module global fournit fond, bordure, rayon, ombre, marges, padding, dimensions et réglages responsive. Les liens calendrier bénéficient des états pris en charge par le contrôle de police Divi, notamment le hover.

## Résolution du contexte

L'ordre est : élément de boucle compatible, `post_id` compatible, contexte public WP Seed Events, puis post WordPress courant compatible. Un élément de boucle réel mais incompatible produit une sortie vide et ne retombe jamais sur la page porteuse.

La fiche événement, la page modèle et un template Theme Builder assigné au CPT utilisent le contexte de l'événement au frontend. Une page ordinaire reste vide. Dans le Loop Builder, chaque `loop_id` est résolu indépendamment, sans ID fixe ni reprise du contexte de la page porteuse.

La page modèle éditée seule peut afficher un état vide dans le Visual Builder, car elle n'est pas elle-même un événement. Le rendu public depuis une fiche événement reste contextuel.

## Endpoint de prévisualisation

Route : `GET /wp-json/wp-seed-events/v1/divi-event-dates-preview`.

La route exige la capacité `edit_posts`, n'accepte qu'une lecture, n'écrit aucune donnée et renvoie uniquement le HTML du renderer partagé. Les attributs sont normalisés côté serveur. Le composant React annule les requêtes obsolètes avec `AbortController`, affiche un état de chargement temporaire, un état vide propre et un message d'erreur lisible.

## Accessibilité

Le renderer conserve :

- une liste `ul`/`li` dans l'ordre canonique ;
- les balises `time` et leurs attributs `datetime` ;
- le libellé visible `Annulée` ;
- des liens calendrier explicites et navigables au clavier ;
- un heading limité à `h2`–`h6` ;
- un `aria-label` sur la section lorsque le titre est masqué ;
- aucun conteneur vide lorsqu'aucune occurrence n'est retenue.

Le responsive visuel est délégué aux réglages standard de Divi et à la structure HTML partagée.

## Shortcode et multi-builders

`[wp_seed_event_dates]` reste le fallback universel et utilise le même renderer. Il n'est pas l'expérience builder principale. Le provider Divi `next_date` reste réservé à une valeur scalaire ; le module Dates traite la collection complète.

Le futur bloc Gutenberg/Spectra reprendra le même contrat Contenu et le même renderer. Aucun bloc Gutenberg, module Visuels ou module Personnes n'est inclus dans ce lot.

## Développement et build

Prérequis : Node.js 18 ou plus récent et npm 10 ou plus récent. La recette V1 a été exécutée avec Node.js 22.22.3 et npm 10.9.8.

Depuis `includes/integrations/divi/event-dates-module/visual-builder` :

```powershell
npm ci
npm test
npm run lint
npm run build
```

Les sources JSX, le test de contrat, `package.json`, `package-lock.json` et la configuration Webpack sont suivis dans Git. `node_modules` reste ignoré. Le bundle compilé est suivi et doit être reproductible.

## Packaging

`build-dev-zip.ps1` échoue si `src/module.json` ou le bundle compilé manque. Le ZIP inclut uniquement ces artefacts runtime avec le PHP du plugin. Il exclut `node_modules`, les tests, la configuration Webpack, les manifests Node, la source JSX, les journaux et les extensions temporaires `.patch`, `.next`, `.tmp` et `.bak`.

Le ZIP doit conserver une racine unique `wp-seed-events/` et la version du header principal.

## Pages de recette

- page ordinaire du module : ID `1295` ;
- page modèle : ID `976` ;
- Loop Builder natif : ID `1205` ;
- fiche événement de référence : ID `914` ;
- Gutenberg : ID `998` ;
- collections : ID `1008` ;
- anciens shortcodes : ID `1137`.

La recette couvre insertion, sauvegarde, réouverture, contenu brut natif, options Contenu, sélecteurs Design, aperçu REST, frontend événement, page modèle, Loop Builder, états vide/futur/passé/annulé, horaires, calendrier et non-régressions.

## Limites V1

- aucun sélecteur arbitraire de balise n'est proposé pour la structure des occurrences ;
- aucun ID d'événement ne peut être forcé dans le module ;
- l'aperçu d'une page ordinaire ou d'une page modèle hors contexte événement est volontairement vide ;
- le module ne remplace pas les valeurs Dynamic Content scalaires ;
- les futurs composants Visuels et Personnes ne sont pas commencés.
