# Implementation Guidelines

## Intention

Ces regles guident la realisation de WP Seed Events V1.

Le but est de construire par petits lots, sans perdre les principes du projet :
autonomie, simplicite, maintenance faible et usage prioritaire de WordPress
natif.

## Regles de developpement

- Un lot = une responsabilite.
- Chaque lot doit produire un resultat verifiable.
- Le diff doit rester minimal.
- Aucun refactoring massif pendant un lot fonctionnel.
- Aucune dependance inutile.
- Aucune dependance obligatoire a WP Seed Content.
- Aucun builder interne.
- WordPress natif en priorite.
- Le plugin doit rester activable apres chaque lot.
- Les donnees metier doivent rester separees du rendu avance.
- Les occurrences ne doivent pas devenir un calendrier complexe.
- Les contacts ne doivent pas devenir un annuaire complet.
- Les lieux ne doivent pas devenir une gestion de salles.
- Le rendu public V1 doit rester minimal et stable.
- Les libelles publics doivent rester comprehensibles pour un visiteur non specialiste.

## Validation

- Tester apres chaque lot.
- Verifier l'activation du plugin apres chaque lot.
- Verifier l'absence de dependance obligatoire a WP Seed Content.
- Verifier qu'aucune derive builder, agenda ou billetterie n'est introduite.
- Corriger avant de passer au lot suivant.

## Commits

- Un commit doit etre atomique.
- Un commit correspond a un lot valide ou a une correction ciblee.
- Aucun commit sans validation humaine explicite.
- Ne pas melanger documentation, refactoring et fonctionnalite metier dans le meme commit.

## Priorite

Quand un choix hesite entre puissance et simplicite, choisir la simplicite.

Quand un choix hesite entre autonomie et integration, choisir l'autonomie.

Quand un choix hesite entre WordPress natif et mecanisme specifique, choisir
WordPress natif sauf justification explicite.
