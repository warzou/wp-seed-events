# Domain Model

## Intention

WP Seed Events est un gestionnaire d'évènements orienté communication.

Il ne cherche pas à devenir un agenda complet ni un calendrier généraliste. Son
rôle est de créer une référence claire pour chaque évènement, puis de permettre à
toute la communication de pointer vers cette référence.

Un évènement est créé une seule fois. Sa page publique devient le point de
référence pour les visiteurs, les supports imprimés, les partages numériques et
les rappels de calendrier.

## Objets métier

### Évènement

Un évènement représente une action, une rencontre, une session, une journée, une
conférence, un atelier ou toute autre initiative à communiquer au public.

Sa responsabilité est de regrouper les informations qui donnent du sens à la
communication :

- nom ;
- description ;
- promesse ou intention ;
- informations utiles ;
- occurrences ;
- lieu éventuel ;
- médias ;
- contact éventuel ;
- lien public de référence.

L'évènement est l'objet central du domaine.

### Occurrence

Une occurrence représente un moment réel où l'évènement a lieu.

Elle porte la dimension temporelle :

- date ;
- heure de début éventuelle ;
- heure de fin éventuelle ;
- période si l'évènement dure plusieurs jours.

Un évènement peut avoir une seule occurrence ou plusieurs occurrences.

L'occurrence ne remplace pas l'évènement. Elle précise quand l'évènement existe
dans le temps.

### Lieu

Un lieu représente l'endroit associé à un évènement.

Il peut décrire :

- un nom de lieu ;
- une adresse ;
- une ville ;
- des indications d'accès ;
- une précision utile pour les visiteurs.

Un lieu peut être utilisé par plusieurs évènements.

Un évènement peut aussi ne pas avoir de lieu physique si cette information n'est
pas pertinente.

### Média

Un média représente un support visuel ou documentaire lié à l'évènement.

Il peut servir à :

- illustrer l'évènement ;
- enrichir la communication ;
- fournir une affiche ;
- accompagner le partage public.

Un évènement peut posséder plusieurs médias.

Le média n'est pas le contenu principal de l'évènement. Il soutient sa
communication.

### Contact

Un contact représente la personne, l'équipe ou le canal à joindre pour obtenir
des informations sur l'évènement.

Il peut contenir :

- un nom ;
- un rôle ;
- un moyen de contact ;
- une consigne de prise de contact.

Le contact est facultatif.

### Information pratique

Une information pratique représente une précision utile aux visiteurs.

Exemples :

- accès ;
- horaires particuliers ;
- conditions d'entrée ;
- consignes sur place ;
- niveau requis ;
- public concerné ;
- matériel à prévoir.

Ces informations doivent rester orientées visiteur et communication.

### QR Code

Un QR Code représente un raccourci visuel vers la page publique de l'évènement.

Il sert surtout aux supports imprimés, affiches, flyers, programmes ou documents
partagés hors ligne.

Il est produit à partir du lien public de référence.

### Invitation calendrier

Une invitation calendrier représente une version transportable des dates de
l'évènement.

Elle permet à une personne d'ajouter l'évènement à son propre outil de rappel ou
de calendrier.

Elle est produite à partir des informations de l'évènement et de ses occurrences.

## Relations entre les objets

Un évènement possède toujours une ou plusieurs occurrences.

Une occurrence appartient toujours à un seul évènement.

Un évènement peut posséder un lieu.

Un lieu peut être partagé par plusieurs évènements.

Un évènement peut posséder plusieurs médias.

Un média appartient à la communication d'un évènement.

Un évènement peut posséder un contact.

Un contact peut être commun à plusieurs évènements si la même personne ou équipe
est responsable de plusieurs communications.

Un évènement peut posséder plusieurs informations pratiques.

Un évènement possède une page publique de référence.

Le QR Code pointe vers la page publique de référence.

L'invitation calendrier reprend les informations temporelles de l'évènement.

## Cycle de vie d'un évènement

Le cycle de vie combine deux dimensions :

- l'état éditorial, décidé par l'équipe qui prépare la communication ;
- le statut temporel, déduit des occurrences.

### Brouillon

L'évènement est en préparation.

Il peut être incomplet, relu, corrigé ou enrichi. Il n'est pas encore destiné au
public.

### Publié

L'évènement est validé pour la communication.

Sa page publique peut devenir la référence vers laquelle pointent les supports de
communication.

### À venir

L'évènement est publié et sa prochaine occurrence n'a pas encore commencé.

Ce statut est déduit des dates.

### En cours

L'évènement est publié et une occurrence est en train de se dérouler.

Ce statut est déduit des dates et des horaires connus.

### Terminé

Toutes les occurrences de l'évènement sont passées.

Ce statut est déduit des dates.

### Archivé

L'évènement n'est plus mis en avant dans la communication courante.

L'archivage peut être utile pour conserver une trace sans continuer à présenter
l'évènement comme actif.

## Ce qui est calculé

Les éléments suivants ne doivent pas être saisis comme des informations fixes.
Ils sont déduits des occurrences et du moment de consultation :

- à venir ;
- en cours ;
- terminé ;
- aujourd'hui ;
- cette semaine ;
- prochaine occurrence ;
- dernière occurrence passée ;
- durée affichable si début et fin sont connus ;
- ordre chronologique des occurrences ;
- lien utilisé par le QR Code ;
- contenu de l'invitation calendrier.

Ces éléments peuvent changer sans que l'évènement lui-même soit modifié.

## Ce qui est immuable

Un évènement possède toujours au moins une occurrence.

Une occurrence appartient toujours à un évènement.

Une occurrence ne peut pas exister seule.

Un évènement créé pour la communication doit posséder une page publique de
référence lorsqu'il est publié.

Le QR Code d'un évènement publié doit pointer vers la page publique de référence,
pas vers une ressource temporaire.

Une invitation calendrier doit refléter les occurrences de l'évènement.

Un lieu peut être partagé par plusieurs évènements.

Un média soutient la communication d'un évènement, mais ne définit pas
l'évènement à lui seul.

Le statut temporel est déduit des occurrences. Il ne doit pas être choisi
manuellement.

## Hors périmètre V1

Les éléments suivants ne font pas partie du domaine V1 :

- paiement ;
- billetterie ;
- inscriptions ;
- réservation de places ;
- gestion de jauge ;
- liste d'attente ;
- newsletter ;
- emailing ;
- campagnes automatiques ;
- publication automatique sur les réseaux sociaux ;
- CRM ;
- suivi commercial ;
- programme multi-salles complexe ;
- planning d'équipe ;
- gestion de ressources internes ;
- synchronisation avec des services externes ;
- statistiques avancées ;
- parcours participant complet.

Ces sujets peuvent exister autour d'un évènement, mais ils ne définissent pas le
coeur métier de WP Seed Events en V1.

## Zones d'ambiguïté à clarifier

### Lieu par évènement ou par occurrence

Le modèle actuel considère qu'un évènement possède éventuellement un lieu.

Une question reste ouverte : un évènement multi-occurrences peut-il avoir des
lieux différents selon les occurrences ?

Pour garder la V1 simple, le choix recommandé est de commencer avec un lieu au
niveau de l'évènement, puis de réévaluer ce besoin plus tard.

### Contact unique ou contacts multiples

Le modèle décrit un contact facultatif.

Une question reste ouverte : certains évènements auront-ils besoin de plusieurs
contacts, par exemple un contact presse et un contact organisation ?

Pour la V1, un contact principal semble suffisant.

### Informations pratiques structurées ou libres

Les informations pratiques peuvent être très variées.

Une question reste ouverte : faut-il les organiser par type ou les garder sous
forme de blocs simples ?

Pour la V1, une approche simple et lisible côté visiteur est préférable.

### Archivage

L'archivage est utile, mais son rôle exact reste à cadrer.

Une question reste ouverte : l'archivage doit-il être une décision explicite ou
une conséquence naturelle du temps passé ?

Pour la V1, il vaut mieux distinguer clairement "terminé" et "archivé".

## Résumé

WP Seed Events repose sur un objet central : l'évènement.

Un évènement existe pour communiquer clairement une initiative au public. Il
possède au moins une occurrence, peut être associé à un lieu, à des médias, à un
contact et à des informations pratiques.

La page publique de l'évènement est la référence unique. Le QR Code et
l'invitation calendrier sont des sorties produites à partir de cette référence et
des informations temporelles.

Le domaine V1 doit rester volontairement simple. Il couvre le coeur évènementiel
utile à la communication, sans prendre en charge la billetterie, les inscriptions,
le paiement, les campagnes ou les outils relationnels.
