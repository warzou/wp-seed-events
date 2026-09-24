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
- lieu principal éventuel ;
- médias ;
- contacts ou intervenants éventuels ;
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

### Promotion et annee du parcours

Une promotion represente une cohorte nommee, ordonnee et eventuellement
archivee. Elle possede un nom, un slug unique, une annee de debut, un statut,
un ordre et une description. Elle ne possede pas de page publique.

Une occurrence peut etre rattachee a une promotion et a une annee du parcours
de `1` a `4`. Les deux valeurs sont facultatives mais indissociables :

- une promotion exige une annee du parcours ;
- une annee du parcours exige une promotion ;
- aucune annee n'est deduite automatiquement ;
- une promotion archivee reste lisible dans l'historique ;
- une nouvelle occurrence ne peut cibler qu'une promotion active.

Le theme du seminaire reste l'evenement lui-meme, identifie par son ID, son
titre et son slug. Il n'existe pas de taxonomie Theme parallele.

Le lifecycle v3 projette chaque occurrence de facon reconstruisible pour les
lectures indexees par promotion et annee du parcours. Cette projection reste
technique et n'est jamais la source de verite.

Le chemin canonique du parcours est :

```text
Promotion
  -> annee du parcours
    -> theme/evenement
      -> occurrence
```

### Lieu

Un lieu représente l'endroit associé à un évènement.

Il peut décrire :

- un nom de lieu ;
- une adresse ;
- une ville ;
- des indications d'accès ;
- une précision utile pour les visiteurs.

Un lieu peut être utilisé par plusieurs évènements.

En V1, un évènement possède au maximum un lieu principal, même s'il possède
plusieurs occurrences. Les évènements multi-lieux sont hors V1.

### Média

Un média représente un support visuel ou documentaire lié à l'évènement.

Il peut servir à :

- illustrer l'évènement ;
- enrichir la communication ;
- fournir une affiche ;
- accompagner le partage public ;
- fournir une image de communication.

Un évènement peut posséder plusieurs médias.

Le média n'est pas le contenu principal de l'évènement. Il soutient sa
communication.

### Contact / Intervenant

Un contact ou intervenant représente la personne, l'équipe ou le canal utile à la
compréhension ou à l'organisation de l'évènement.

Il peut contenir :

- un nom ;
- un rôle ;
- un moyen de contact ;
- une consigne de prise de contact.

En V1, un évènement peut comporter plusieurs contacts ou intervenants simples.
Ils peuvent représenter un organisateur, un intervenant, un contact inscription
ou un contact information.

La liaison future avec un annuaire WP Seed Content reste optionnelle et hors V1.

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

En V1, les informations pratiques sont saisies en texte libre. Il n'y a pas de
blocs structurés complexes.

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

Le libellé public retenu est Ajouter à mon calendrier.

Elle est produite à partir des informations de l'évènement et de ses occurrences.

## Relations entre les objets

Un évènement possède toujours une ou plusieurs occurrences.

Une occurrence appartient toujours à un seul évènement.

Un évènement peut posséder un lieu principal.

Un lieu peut être partagé par plusieurs évènements.

Un évènement peut posséder plusieurs médias.

Un média appartient à la communication d'un évènement.

Un évènement peut posséder plusieurs contacts ou intervenants.

Un contact ou intervenant reste une information simple liée à l'évènement en V1.
Une liaison future avec un annuaire WP Seed Content pourra exister, mais elle
restera optionnelle.

Un évènement peut posséder plusieurs informations pratiques.

Un évènement possède une page publique de référence.

Le QR Code pointe vers la page publique de référence.

L'invitation calendrier reprend les informations temporelles de l'évènement.

## Sources de verite et collections publiques

Les donnees metier de l'evenement et de ses occurrences restent dans le stockage
WordPress etabli. Les contrats publics canoniques sont :

- `wp_seed_events_get_event_occurrences()` pour normaliser les occurrences ;
- `wp_seed_events_get_promotion()` pour normaliser une Promotion ;
- Event Data pour l'objet evenement public ;
- `wp_seed_events_query_event_collection()` pour une selection par evenement ;
- `wp_seed_events_query_occurrence_collection()` pour une selection plate, une
  entree par occurrence ;
- `wp_seed_events_query_grouped_occurrence_collection()` pour le chemin
  Promotion -> annee -> theme/evenement -> occurrences.

La projection Lifecycle V3 est reconstruisible. Les collections sont des
selecteurs publics et ne constituent ni un second stockage ni une seconde
representation canonique des occurrences.

Une occurrence sans Promotion reste valide hors parcours, mais elle est exclue
des collections groupees par parcours. Une Promotion archivee reste lisible
pour l'historique. Les occurrences annulees sont exclues des collections par
defaut. Le meme theme/evenement peut apparaitre dans plusieurs Promotions ou
annees sans deduplication globale.

Les builders restent responsables de la composition visuelle. Ils doivent
consommer les contrats publics plutot que les metas, la table de projection ou
les options du lifecycle.

## Cycle de vie d'un évènement

Le cycle de vie combine trois dimensions :

- l'état éditorial, décidé par l'équipe qui prépare la communication ;
- le statut temporel, déduit des occurrences ;
- l'état métier annulé, choisi explicitement si l'évènement ne se tient pas.

### Brouillon

L'évènement est en préparation.

Il peut être incomplet, relu, corrigé ou enrichi. Il n'est pas encore destiné au
public.

### Publié

L'évènement est validé pour la communication.

Sa page publique peut devenir la référence vers laquelle pointent les supports de
communication.

### Dépublié

L'évènement n'est plus visible publiquement, sans être supprimé.

### Annulé

L'évènement ne se tiendra pas.

Il reste consultable lorsqu'il a déjà été publié ou partagé, et il doit être
clairement marqué comme annulé.

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

L'archivage est manuel. Un évènement terminé n'est pas automatiquement archivé.

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

Un évènement possède au maximum un lieu principal en V1.

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
- parcours participant complet ;
- évènements multi-lieux ;
- récurrence complexe.

Ces sujets peuvent exister autour d'un évènement, mais ils ne définissent pas le
coeur métier de WP Seed Events en V1.

## Décisions V1 validées

### Lieu principal unique

En V1, un évènement possède au maximum un lieu principal.

Ce choix reste valable même si l'évènement possède plusieurs occurrences.

Les évènements multi-lieux sont hors V1.

### Contacts et intervenants multiples

En V1, un évènement peut comporter plusieurs contacts ou intervenants simples.

Ils peuvent représenter :

- un organisateur ;
- un intervenant ;
- un contact inscription ;
- un contact information.

### Informations pratiques libres

En V1, les informations pratiques sont saisies en texte libre.

Il n'y a pas de blocs structurés complexes.

### Description recommandée

La description n'est pas obligatoire pour enregistrer un brouillon.

Elle est fortement recommandée avant publication.

### Archivage manuel

L'archivage est manuel.

Un évènement terminé n'est pas automatiquement archivé.

## Résumé

WP Seed Events repose sur un objet central : l'évènement.

Un évènement existe pour communiquer clairement une initiative au public. Il
possède au moins une occurrence, peut être associé à un lieu principal, à des
médias, à plusieurs contacts ou intervenants et à des informations pratiques.

La page publique de l'évènement est la référence unique. Le QR Code et
l'invitation calendrier sont des sorties produites à partir de cette référence et
des informations temporelles.

Le domaine V1 doit rester volontairement simple. Il couvre le coeur évènementiel
utile à la communication, sans prendre en charge la billetterie, les inscriptions,
le paiement, les campagnes ou les outils relationnels.
## Contexte builder d'une occurrence

Les builders ne donnent pas une identité de post aux occurrences. Le bloc
Gutenberg dédié et le module Divi Collection d’occurrences consomment les
collections publiques puis fournissent temporairement un contexte composite
`collection_instance_id + event_id + occurrence_uid + current_item_index`. La
Query Loop native et le Loop Builder restent réservés aux événements. Aucun post
technique d’occurrence n’est créé.

Le mode groupé conserve la hiérarchie Promotion → année du parcours →
événement/thème → occurrence. Le modèle éditable porte sur l’occurrence ; les
niveaux de groupe sont une structure serveur minimale issue du contrat groupé.
