# Descriptions des événements

## Contrat V1

La description complète et la description courte sont deux données distinctes :

- `description` provient de `post_content` et reste la source de vérité de la fiche et de l’ICS ;
- `short_description` contient uniquement la saisie manuelle facultative stockée dans `_wp_seed_event_short_description` ;
- `short_description_effective` est calculée dynamiquement et n’est jamais stockée ;
- `excerpt` est un alias strict, byte pour byte, de `short_description_effective`.

Le champ WordPress historique `post_excerpt` n’entre jamais dans ce contrat. Il
n’est ni lu, ni migré, ni nettoyé, ni copié, ni supprimé par WP Seed Events.

## Résolution canonique

`wp_seed_events_resolve_short_description()` applique une seule règle métier :

1. description courte manuelle non vide après `trim()` ; la valeur `"0"` est valide ;
2. texte avant une coupure WordPress `<!--more-->` ou `<!--more texte-->` ;
3. texte automatique limité à 40 mots.

Les branches manuelle et `more` ne sont jamais tronquées. L’ellipse `…` est
ajoutée uniquement lorsque le fallback automatique dépasse réellement 40 mots.

Le nettoyage automatique convertit les frontières HTML utiles en sauts de ligne,
retire scripts, styles, shortcodes et balises, normalise CRLF/CR vers LF, compacte
les espaces horizontaux et conserve au maximum une ligne vide entre paragraphes.
Event Data et REST transportent du texte avec `\n`, jamais des `<br>`.

## Administration et REST

La boîte **Description** conserve l’éditeur complet et ajoute le textarea
**Description courte (facultative)**. Une valeur vide supprime seulement la meta
dédiée. La sauvegarde ne modifie ni `post_content`, ni `post_excerpt`.

En contexte REST `edit`, pour un utilisateur autorisé :

- `short_description` est lisible, modifiable et supprimable ;
- `short_description_effective` est en lecture seule ;
- `excerpt` est en lecture seule et reproduit exactement la valeur effective.

Ces champs ne rendent pas la meta publique en contexte `view` et ne changent pas
le réglage `show_in_rest` du type de publication.

## Consommateurs

Les cartes et shortcodes rendent les sauts de ligne après échappement HTML.
Gutenberg et Divi appliquent le style borné
`.wp-seed-events-multiline-text { white-space: pre-line; }`. Les collections
continuent à utiliser `excerpt`. La fiche détaillée et l’ICS continuent à utiliser
exclusivement `description`.

Le cache Event Data par requête est invalidé lors de la sauvegarde du contenu,
de l’ajout, de la modification ou de la suppression de la meta dédiée. Aucun
fallback calculé n’est persisté.

## Compatibilité et rollback

Le changement volontaire est le remplacement de l’ancien fallback automatique
28 mots par le contrat `manuel → more → 40 mots`. Les clés publiques
`description` et `excerpt` restent disponibles. Un rollback du runtime ne
demande aucune migration : la meta dédiée peut rester présente et les
`post_excerpt` historiques restent strictement intacts.
