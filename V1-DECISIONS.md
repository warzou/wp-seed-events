# V1 Decisions

## Intention

Ce document consolide les arbitrages V1 de WP Seed Events avant le passage a
l'architecture de mise en oeuvre.

La V1 doit rester simple, autonome et centree sur la communication d'un
evenement.

## 1. Contacts et intervenants

La V1 accepte plusieurs contacts ou intervenants par evenement.

Ils restent simples et lisibles. Ils peuvent representer :

- un organisateur ;
- un intervenant ;
- un contact inscription ;
- un contact information.

La liaison future avec un annuaire WP Seed Content reste optionnelle et hors V1.

## 2. Description

La description n'est pas obligatoire pour enregistrer un brouillon.

Elle est fortement recommandee avant publication.

L'outil doit guider l'utilisateur sans le bloquer inutilement.

## 3. Informations pratiques

En V1, les informations pratiques sont saisies en texte libre.

Il n'y a pas de blocs structures complexes.

## 4. Lieu

En V1, un evenement possede un lieu principal unique.

Ce choix reste valable meme si l'evenement possede plusieurs occurrences.

Les evenements multi-lieux sont hors V1.

## 5. Image de communication

Un evenement doit rester partageable visuellement.

La V1 doit prevoir les cas suivants :

- image fournie par l'utilisateur ;
- flyer image utilise comme image de communication ;
- photo simple utilisee comme image de communication ;
- fallback ou image par defaut si rien n'est fourni.

Le flyer n'est jamais obligatoire.

## 6. Annulation

Annuler est un etat metier visible publiquement.

Un evenement annule reste consultable.

Il doit etre clairement marque comme annule.

L'annulation n'est pas une suppression.

## 7. Archivage

L'archivage est manuel.

Un evenement termine n'est pas automatiquement archive.

L'outil peut plus tard proposer une action d'archivage, mais il ne doit pas la
forcer.

## 8. Libelle calendrier

Cote visiteur, le mot ICS ne doit pas etre utilise comme libelle principal.

Le libelle recommande est :

Ajouter a mon calendrier

Une formulation equivalente reste acceptable si elle est plus claire dans le
contexte.

## 9. Rendu public minimal

La page publique V1 doit afficher les informations dans un ordre simple :

1. etat si l'evenement est annule ;
2. image de communication ;
3. titre ;
4. resume ou description ;
5. dates et horaires ;
6. lieu ;
7. contacts ;
8. informations pratiques ;
9. galerie ou flyer si presents ;
10. bouton Ajouter a mon calendrier ;
11. QR Code ou lien de partage si pertinent.

Ce rendu doit rester minimal, clair et centre sur le visiteur.

## 10. Duplication

La duplication copie par defaut :

- titre ;
- description ;
- occurrences ;
- lieu ;
- contacts ;
- informations pratiques ;
- medias associes.

Le nouvel evenement reste toujours en brouillon.

## Resume

La V1 de WP Seed Events est maintenant cadree autour d'un evenement simple,
partageable et durable.

Elle accepte plusieurs contacts, un lieu principal unique, des informations
pratiques libres, une image de communication avec fallback, une annulation
visible, un archivage manuel et un rendu public minimal ordonne.

Ces decisions limitent volontairement la V1 tout en couvrant les besoins
essentiels de communication.

## Passage a l'architecture

Le projet peut passer au document WORDPRESS-ARCHITECTURE.md.

Les decisions produit necessaires au cadrage V1 sont suffisamment explicites
pour guider la suite.
