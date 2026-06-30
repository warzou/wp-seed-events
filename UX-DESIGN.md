# UX Design

## Intention

WP Seed Events doit permettre a une personne de creer, publier, partager puis
archiver un evenement sans effort inutile.

L'outil s'adresse a des personnes qui ont besoin de communiquer clairement une
rencontre, un atelier, une conference, une seance collective, une journee
ouverte ou toute autre initiative publique.

Il doit etre utilisable par :

- un therapeute ;
- une association ;
- un formateur ;
- un independant.

L'experience doit rester simple, directe et rassurante. L'utilisateur ne doit pas
avoir l'impression de regler un outil complexe. Il doit sentir qu'il prepare une
page claire pour son public.

## Parcours principal

### 1. Nouvel evenement

L'utilisateur commence par creer un nouvel evenement.

Il renseigne d'abord les informations qui permettent de comprendre l'evenement :

- son nom ;
- une description claire ;
- la ou les dates ;
- le lieu si necessaire ;
- les informations pratiques utiles aux visiteurs.

Le parcours doit encourager une saisie progressive. L'utilisateur peut
enregistrer son travail meme si tout n'est pas encore pret.

### 2. Publication

Quand les informations essentielles sont presentes, l'utilisateur peut verifier
le rendu public de l'evenement.

La publication doit etre un moment simple :

- l'utilisateur voit ce qui sera presente au public ;
- les informations manquantes importantes sont signalees clairement ;
- la decision de publier reste volontaire.

Une fois publie, l'evenement possede une page de reference claire.

### 3. Partage

Apres publication, l'utilisateur doit pouvoir partager l'evenement sans chercher
longtemps.

Les actions principales doivent etre visibles :

- copier le lien public ;
- telecharger le QR Code ;
- telecharger l'invitation calendrier ;
- previsualiser la page publique.

Le partage doit etre pense pour les usages concrets : affiche, flyer, message,
site personnel, reseaux sociaux, programme, email ou support imprime.

### 4. Fin de l'evenement

Quand l'evenement est passe, l'utilisateur ne devrait pas avoir a changer son
statut manuellement pour que l'evenement soit compris comme termine.

L'evenement reste consultable, mais il ne doit plus etre confondu avec les
evenements a venir.

L'utilisateur peut encore :

- consulter la page ;
- retrouver les informations ;
- reutiliser certains elements pour un futur evenement ;
- archiver l'evenement si cela correspond a son organisation.

### 5. Archive

L'archive sert a garder une trace sans encombrer le travail courant.

Archiver un evenement doit etre une action explicite et reversible. Un evenement
archive ne doit plus etre mis en avant dans les listes de travail quotidiennes,
mais il doit rester retrouvable.

## Ecran "Creer un evenement"

L'ecran de creation doit etre organise par grandes sections comprehensibles.

### Informations generales

Cette section repond a la question : de quoi s'agit-il ?

Elle contient :

- le nom de l'evenement ;
- une description ;
- une intention ou phrase courte de presentation si utile.

### Dates

Cette section repond a la question : quand cela a-t-il lieu ?

Elle contient :

- une premiere date ;
- une heure de debut si elle est connue ;
- une heure de fin si elle est connue ;
- la possibilite d'ajouter une autre date.

La saisie doit rester naturelle pour un evenement simple, tout en permettant
plusieurs dates lorsque c'est necessaire.

### Lieu

Cette section repond a la question : ou cela se passe-t-il ?

Elle permet de :

- choisir un lieu deja utilise ;
- creer un nouveau lieu ;
- laisser le lieu vide si l'information n'est pas pertinente.

Le lieu ne doit jamais etre une source de ressaisie inutile.

### Medias

Cette section repond a la question : quels supports accompagnent l'evenement ?

Elle permet d'ajouter une image, une affiche ou un document utile a la
communication.

Les medias doivent soutenir l'evenement, pas compliquer sa creation.

### Informations pratiques

Cette section repond a la question : que doit savoir le visiteur ?

Elle peut contenir :

- les conditions d'entree ;
- les indications d'acces ;
- le public concerne ;
- le materiel a prevoir ;
- une precision utile sur place.

Ces informations doivent etre faciles a lire et faciles a modifier.

### Communication

Cette section repond a la question : comment partager l'evenement ?

Elle regroupe les actions de partage une fois que l'evenement est pret :

- lien public ;
- QR Code ;
- invitation calendrier ;
- previsualisation.

## Champs indispensables en V1

La V1 doit demander peu de choses. Chaque champ doit avoir une utilite claire
pour l'utilisateur ou pour le visiteur.

### Obligatoires

- Nom de l'evenement.
- Au moins une date.

### Optionnels

- Heure de debut.
- Heure de fin.
- Description.
- Phrase courte de presentation.
- Lieu.
- Adresse.
- Ville.
- Indications d'acces.
- Image ou affiche.
- Document associe.
- Contact principal.
- Informations pratiques.

## Actions proposees

Les actions doivent etre peu nombreuses, previsibles et placees au bon moment du
parcours.

### Pendant la preparation

- Enregistrer.
- Previsualiser.
- Publier.

### Apres publication

- Copier le lien.
- Telecharger le QR Code.
- Telecharger l'ICS.
- Previsualiser.

### Apres la fin de l'evenement

- Archiver.
- Restaurer si l'evenement a ete archive par erreur.

## Listes d'evenements

Les listes doivent aider l'utilisateur a retrouver rapidement ce qui compte.

### Tous

Vue globale de tous les evenements accessibles, quel que soit leur etat.

Elle sert surtout a retrouver un evenement precis.

### A venir

Vue prioritaire pour le travail quotidien.

Elle affiche les evenements publies dont la prochaine date n'a pas encore
commence.

### En cours

Vue courte, utile pour les evenements qui se deroulent maintenant.

Elle doit rester simple et ne pas devenir un tableau de supervision complexe.

### Termines

Vue des evenements dont toutes les dates sont passees.

Elle permet de relire, reutiliser ou archiver.

### Brouillons

Vue des evenements en preparation.

Elle aide l'utilisateur a reprendre un travail non termine sans le perdre.

## Lieux

Le choix du lieu doit eviter la ressaisie.

Quand l'utilisateur remplit la section Lieu, il doit pouvoir :

- rechercher un lieu deja utilise ;
- selectionner ce lieu en un geste ;
- verifier rapidement les informations associees ;
- creer un nouveau lieu si aucun lieu existant ne convient.

Quand un nouveau lieu est cree, il doit pouvoir etre reutilise pour de futurs
evenements.

La creation d'un lieu doit rester legere :

- nom du lieu ;
- adresse si utile ;
- ville si utile ;
- indications d'acces si utile.

Un evenement peut aussi etre cree sans lieu physique lorsque le lieu n'est pas
encore connu ou n'est pas pertinent.

## Erreurs UX a eviter

WP Seed Events ne doit jamais provoquer les erreurs suivantes :

- demander trop de champs avant de pouvoir enregistrer ;
- employer un vocabulaire technique ;
- afficher des options avancees partout ;
- melanger creation, partage et archivage dans un meme bloc confus ;
- obliger a ressaisir un lieu deja connu ;
- forcer un lieu quand l'evenement n'en a pas besoin ;
- cacher les actions de partage apres publication ;
- rendre la previsualisation difficile a trouver ;
- demander a l'utilisateur de choisir manuellement un statut temporel ;
- rendre les brouillons difficiles a retrouver ;
- confondre evenement termine et evenement archive ;
- presenter les evenements passes comme s'ils etaient encore a venir ;
- multiplier les choix avant que l'utilisateur ait fini l'essentiel ;
- utiliser des ecrans denses pour des besoins simples ;
- rendre irreversible une action courante sans avertissement clair.

## Principes UX fondateurs

1. Une information n'est saisie qu'une seule fois.
2. L'utilisateur cree un evenement, il ne configure pas un systeme.
3. L'outil doit etre utilisable sans documentation.
4. La simplicite prime toujours sur la puissance.
5. Les champs obligatoires doivent rester rares.
6. Les actions importantes doivent etre visibles au moment ou elles deviennent utiles.
7. Le vocabulaire doit etre celui de l'utilisateur et de son public.
8. Un evenement simple doit rester simple a creer.
9. Un evenement plus riche doit pouvoir etre complete progressivement.
10. Le partage doit etre possible immediatement apres publication.
11. Les statuts lies au temps doivent etre compris naturellement, sans reglage manuel.
12. L'archive doit alleger le travail courant sans effacer la memoire.
13. Les lieux doivent etre reutilisables.
14. Les erreurs doivent expliquer quoi corriger, pas culpabiliser l'utilisateur.
15. Chaque nouvelle option doit justifier sa presence par un besoin utilisateur reel.

## Resume

L'experience ideale de WP Seed Events repose sur un parcours simple :

Nouvel evenement, publication, partage, fin de l'evenement, archive.

La V1 doit rester volontairement sobre. Elle doit permettre de creer un
evenement avec un nom et au moins une date, puis d'ajouter progressivement les
informations utiles au public : description, lieu, medias, contact et
informations pratiques.

La valeur principale de l'outil est de transformer une initiative en reference
publique claire, facile a partager et facile a retrouver.

## Points restant a arbitrer

- La description doit-elle rester optionnelle en V1 ou devenir obligatoire avant publication ?
- Le contact principal doit-il etre gere des la V1 ou reporte apres le socle minimal ?
- Les informations pratiques doivent-elles etre un texte libre unique ou plusieurs blocs courts ?
- Un evenement avec plusieurs dates doit-il toujours partager le meme lieu en V1 ?
- L'archive doit-elle etre proposee automatiquement apres la fin ou rester uniquement une action manuelle ?
- Le libelle public de l'invitation calendrier doit-il afficher "ICS" ou un terme plus comprehensible comme "Ajouter au calendrier" ?
