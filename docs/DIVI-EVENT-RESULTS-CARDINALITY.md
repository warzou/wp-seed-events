# Cardinalite des collections dans les conditions Divi

La condition publique Divi `wpSeedEventsHasResults` evalue une collection WP Seed Events avec le meme pipeline canonique que les boucles publiques.

## Attributs

- `resultCountOperator` accepte `equals`, `at_least` ou `greater_than`.
- `resultCount` est un entier superieur ou egal a zero.

Lorsque ces attributs sont absents, la condition conserve son comportement historique : elle est vraie a partir d'un resultat (`at_least`, `1`). Les conditions deja enregistrees ne necessitent donc aucune migration.

## Exemples

- Etat vide : `resultCountOperator = equals`, `resultCount = 0`.
- Un seul resultat : `resultCountOperator = equals`, `resultCount = 1`.
- Plusieurs resultats : `resultCountOperator = at_least`, `resultCount = 2`.

Le contrat est identique dans le rendu serveur et le Visual Builder. Le plugin limite la requete au nombre minimal de resultats necessaire pour decider la comparaison.

## Integration Stages

La page Stages du DEV consomme ce contrat sans JavaScript ni logique de comptage locale :

- zero stage a venir affiche l'etat vide editorial ;
- un stage a venir affiche `Prochain stage` ;
- deux stages ou plus affichent `Prochains stages`.

Le type d'evenement et l'identite des contenus restent des donnees de configuration Divi, pas des constantes du contrat public.
