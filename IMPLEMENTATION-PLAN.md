# Implementation Plan

## Intention

Ce plan decoupe la V1 de WP Seed Events en petits lots de valeur utilisateur.

Chaque lot doit produire un resultat visible, testable et validable du point de
vue d'une personne qui cree, publie ou partage des evenements.

Le decoupage reste volontairement progressif : un lot ajoute une seule capacite
principale, sans derive vers agenda, billetterie, annuaire complet ou builder.

## Lot 0 - Bootstrap plugin

### Valeur utilisateur

Je peux installer et activer WP Seed Events sans erreur.

### Objectif metier

Poser un socle fiable avant toute fonctionnalite evenementielle.

### Elements techniques probables

- fichier principal du plugin ;
- identification du plugin ;
- version du plugin ;
- protection d'acces direct.

### Validation fonctionnelle

L'utilisateur active le plugin, le desactive, puis le reactive sans erreur
visible.

### Dependances

Aucune.

### Livrable

Un plugin activable, sans fonctionnalite visible et sans logique metier.

## Lot 1 - Evenement public minimal

### Valeur utilisateur

Je peux creer un premier evenement brouillon, le publier, puis ouvrir sa page.

### Objectif metier

Donner a chaque evenement une reference publique stable.

### Elements techniques probables

- contenu editorial dedie aux evenements ;
- liste d'evenements ;
- edition minimale ;
- publication et brouillon ;
- page publique de base.

### Validation fonctionnelle

L'utilisateur cree un evenement brouillon, le retrouve dans la liste, le publie
et consulte sa page publique.

### Dependances

- Lot 0.

### Livrable

Un evenement minimal peut exister, etre publie et etre consulte.

## Lot 2 - Informations essentielles

### Valeur utilisateur

Je peux renseigner les informations de base de mon evenement : titre,
description, premiere date et annulation si besoin.

### Objectif metier

Permettre la creation d'un evenement comprehensible par le public, sans forcer
des informations non indispensables.

### Elements techniques probables

- titre ;
- description ;
- date simple ;
- etat annule ;
- messages d'aide ou de validation simples.

### Validation fonctionnelle

L'utilisateur cree un evenement avec un titre et une date, ajoute une description
si elle est prete, puis verifie qu'un evenement annule est clairement marque
comme annule.

### Dependances

- Lot 1.

### Livrable

Un evenement simple contient les informations minimales utiles et peut etre
marque comme annule.

## Lot 3 - Occurrences

### Valeur utilisateur

Je peux ajouter plusieurs dates simples a un meme evenement.

### Objectif metier

Representer les moments ou l'evenement a lieu, sans gerer de recurrence
complexe.

### Elements techniques probables

- stockage de plusieurs dates ;
- heure de debut optionnelle ;
- heure de fin optionnelle ;
- ordre chronologique ;
- calcul de statut temporel.

### Validation fonctionnelle

L'utilisateur ajoute deux dates a un evenement, les retrouve dans un ordre
comprehensible, puis constate que l'evenement passe automatiquement de "a venir"
a "termine" selon les dates.

### Dependances

- Lot 2.

### Livrable

Un evenement peut porter plusieurs occurrences simples et un statut temporel
calcule.

## Lot 4 - Lieux

### Valeur utilisateur

Je peux choisir un lieu deja utilise ou creer un nouveau lieu pour mon evenement.

### Objectif metier

Eviter la ressaisie des lieux tout en gardant un lieu principal unique par
evenement en V1.

### Elements techniques probables

- lieu reutilisable ;
- nom du lieu ;
- adresse optionnelle ;
- ville optionnelle ;
- indications d'acces optionnelles ;
- association d'un lieu principal a l'evenement.

### Validation fonctionnelle

L'utilisateur cree un lieu une premiere fois, le selectionne ensuite dans un
autre evenement, puis verifie qu'un evenement peut aussi rester sans lieu.

### Dependances

- Lot 2.

### Livrable

Les evenements peuvent utiliser un lieu principal reutilisable et optionnel.

## Lot 5 - Contacts / intervenants

### Valeur utilisateur

Je peux indiquer qui organise, qui intervient ou qui contacter pour un evenement.

### Objectif metier

Clarifier les responsabilites humaines autour de l'evenement sans creer un
annuaire complet.

### Elements techniques probables

- contacts simples lies a l'evenement ;
- role du contact ;
- nom ;
- moyen de contact optionnel ;
- consigne courte optionnelle.

### Validation fonctionnelle

L'utilisateur ajoute plusieurs contacts avec des roles differents, publie
l'evenement, puis verifie que ces contacts sont lisibles sur la page publique.

### Dependances

- Lot 2.

### Livrable

Un evenement peut afficher plusieurs contacts ou intervenants simples, sans
dependance a un annuaire externe.

## Lot 6 - Medias

### Valeur utilisateur

Je peux rendre mon evenement plus partageable avec une image, un flyer, un PDF ou
une galerie.

### Objectif metier

Soutenir la communication de l'evenement sans faire du media la source de verite.

### Elements techniques probables

- image de communication ;
- fallback visuel ;
- flyer recto ;
- flyer verso ;
- PDF associe ;
- galerie d'images.

### Validation fonctionnelle

L'utilisateur ajoute une image de communication et un flyer, consulte la page
publique, puis cree aussi un evenement sans media et verifie qu'il reste
presentable.

### Dependances

- Lot 2.

### Livrable

Un evenement est visuellement partageable avec ou sans media fourni.

## Lot 7 - Rendu public minimal

### Valeur utilisateur

Je peux partager une page evenement claire, lisible et utile pour les visiteurs.

### Objectif metier

Transformer les informations saisies en reference publique stable, sans builder.

### Elements techniques probables

- page publique evenement ;
- ordre d'affichage V1 ;
- affichage de l'annulation ;
- affichage des dates ;
- affichage du lieu ;
- affichage des contacts ;
- affichage des informations pratiques ;
- rendu autonome.

### Validation fonctionnelle

L'utilisateur publie un evenement complet, ouvre sa page publique et verifie que
les informations apparaissent dans un ordre simple : annulation si presente,
image, titre, description, dates, lieu, contacts, informations pratiques et
medias.

### Dependances

- Lot 1 ;
- Lot 2 ;
- Lot 3 ;
- Lot 4 ;
- Lot 5 ;
- Lot 6.

### Livrable

Une page evenement V1 propre, partageable et independante de tout builder.

## Lot 8 - Ajouter a mon calendrier

### Valeur utilisateur

Un visiteur peut ajouter l'evenement a son calendrier depuis la page publique.

### Objectif metier

Faciliter le rappel personnel de l'evenement a partir de ses occurrences.

### Elements techniques probables

- bouton "Ajouter a mon calendrier" ;
- fichier calendrier ;
- reprise des dates et horaires ;
- libelle public non technique.

### Validation fonctionnelle

Le visiteur clique sur "Ajouter a mon calendrier" et obtient une invitation qui
reprend correctement le titre, les dates et les horaires de l'evenement.

### Dependances

- Lot 3 ;
- Lot 7.

### Livrable

Un bouton calendrier utilisable par les visiteurs sur la page evenement.

## Lot 9 - QR Code

### Valeur utilisateur

Je peux recuperer un QR Code qui pointe vers la page publique de mon evenement.

### Objectif metier

Faciliter le partage hors ligne sur affiche, flyer ou support imprime.

### Elements techniques probables

- URL publique de l'evenement ;
- generation ou association du QR Code ;
- acces au QR Code pour l'utilisateur ;
- affichage public seulement si pertinent.

### Validation fonctionnelle

L'utilisateur publie un evenement, recupere son QR Code, le scanne et arrive sur
la bonne page publique.

### Dependances

- Lot 1 ;
- Lot 7.

### Livrable

Un QR Code fiable pour chaque evenement publie.

## Lot 10 - Shortcodes listes

### Valeur utilisateur

Je peux afficher une liste simple des prochains evenements, des evenements en
cours ou des evenements termines dans une page existante.

### Objectif metier

Permettre aux visiteurs de decouvrir les evenements sans transformer le produit
en calendrier avance.

### Elements techniques probables

- point d'affichage pour prochains evenements ;
- point d'affichage pour evenements en cours ;
- point d'affichage pour evenements termines ;
- tri par statut temporel ;
- rendu de liste simple.

### Validation fonctionnelle

L'utilisateur ajoute une liste a une page existante, puis verifie que les
evenements apparaissent dans la bonne categorie selon leurs dates.

### Dependances

- Lot 3 ;
- Lot 7.

### Livrable

Des listes simples d'evenements peuvent etre affichees ailleurs dans le site.

## Lot 11 - Duplication / archivage / annulation

### Valeur utilisateur

Je peux dupliquer un evenement, archiver manuellement un evenement termine et
annuler un evenement sans le supprimer.

### Objectif metier

Simplifier la gestion courante des evenements tout en preservant leur historique
et leur clarte publique.

### Elements techniques probables

- action de duplication ;
- copie en brouillon ;
- archivage manuel ;
- restauration possible si retenue ;
- annulation visible ;
- distinction entre termine, archive, annule et supprime.

### Validation fonctionnelle

L'utilisateur duplique un evenement et obtient un brouillon complet a relire. Il
archive manuellement un evenement termine. Il annule un evenement publie et
verifie que la page reste consultable avec un marquage clair.

### Dependances

- Lot 2 ;
- Lot 3 ;
- Lot 4 ;
- Lot 5 ;
- Lot 6 ;
- Lot 7.

### Livrable

Les actions metier principales sont disponibles sans confusion entre annulation,
archivage, fin d'evenement et suppression.

## Lot 12 - Tests reels / nettoyage / release V1

### Valeur utilisateur

Je peux utiliser WP Seed Events pour un parcours V1 complet, de la creation a la
publication, puis au partage et a l'archivage.

### Objectif metier

Valider que la V1 couvre correctement le coeur de communication evenementielle
avant release.

### Elements techniques probables

- check-list de validation ;
- documentation utilisateur courte ;
- notes de release ;
- corrections ciblees ;
- nettoyage des libelles et des parcours.

### Validation fonctionnelle

Un utilisateur cree plusieurs evenements reels : un brouillon, un evenement a
plusieurs dates, un evenement avec lieu reutilise, un evenement avec contacts, un
evenement avec media, un evenement annule et un evenement archive. Les pages,
listes, QR Codes et boutons calendrier fonctionnent sans dependance obligatoire a
WP Seed Content.

### Dependances

- Lots 0 a 11.

### Livrable

Une V1 stable, testee, documentee et prete pour validation humaine de release.
