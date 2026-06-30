# Functional Model

## Intention

WP Seed Events est un outil de communication evenementielle.

Son role est de transformer une initiative en reference publique claire,
partageable et durable. Le modele fonctionnel decrit ce que le produit doit
permettre de faire, sans definir comment cela sera realise.

Ce document fait le lien entre :

- le modele metier ;
- l'experience utilisateur ;
- les futures decisions de mise en oeuvre.

La regle directrice est simple : l'evenement reste la source de verite. Les
autres objets existent pour preciser, organiser ou diffuser cet evenement.

## Objets metier

Les objets metier identifies pour la V1 sont :

- Evenement ;
- Occurrence ;
- Lieu ;
- Contact / Intervenant ;
- Media.

Aucun autre objet central ne doit etre ajoute sans besoin utilisateur clair.

## Evenement

### Responsabilite

L'evenement est l'objet principal.

Il porte le sens de la communication :

- ce qui est propose ;
- quand cela se passe ;
- ou cela se passe si un lieu est utile ;
- qui contacter ou qui intervient ;
- quels supports accompagnent la communication ;
- comment le public peut consulter, partager ou ajouter l'evenement a son calendrier.

Un evenement doit pouvoir etre compris seul, meme si certains details optionnels
ne sont pas renseignes.

### Ce qu'il peut faire

Un evenement peut :

- etre cree ;
- etre modifie ;
- etre publie ;
- etre depublie ;
- etre duplique ;
- etre annule ;
- etre archive ;
- etre supprime ;
- posseder une ou plusieurs occurrences ;
- etre associe a un lieu ;
- etre associe a plusieurs contacts ou intervenants ;
- etre associe a plusieurs medias ;
- produire une reference publique partageable ;
- produire des elements de communication.

### Ce qu'il ne peut pas faire

Un evenement ne peut pas :

- exister sans occurrence ;
- etre publie sans nom ;
- etre publie sans date ;
- porter lui-meme une logique de paiement ;
- gerer des inscriptions ;
- gerer des places disponibles ;
- envoyer des campagnes de communication ;
- publier automatiquement sur des reseaux sociaux ;
- remplacer une fiche contact complete ;
- remplacer un systeme de gestion documentaire ;
- devenir un calendrier generaliste.

### Regles metier

- Un evenement possede toujours au moins une occurrence.
- Le nom de l'evenement est obligatoire.
- Au moins une date est obligatoire.
- La description est recommandee mais reste optionnelle tant qu'une decision contraire n'est pas prise.
- Le lieu est optionnel.
- Les contacts et intervenants sont optionnels.
- Les medias sont optionnels.
- Le flyer est un support de communication, jamais une obligation.
- Un evenement publie doit disposer d'une reference publique partageable.
- Le QR Code pointe vers la reference publique de l'evenement.
- L'invitation calendrier est produite a partir des occurrences.
- Les etats temporels sont deduits des occurrences.
- Les etats editoriaux sont choisis par l'utilisateur.
- Annuler un evenement ne signifie pas le supprimer.
- Archiver un evenement ne signifie pas le supprimer.

## Actions sur un evenement

### Creer

Creer un evenement consiste a ouvrir une fiche de travail et a renseigner les
informations essentielles.

La creation doit accepter un travail incomplet. L'utilisateur peut preparer un
evenement progressivement.

Regles associees :

- le nom est attendu des que possible ;
- au moins une date est necessaire pour finaliser l'evenement ;
- l'evenement peut rester en brouillon tant qu'il n'est pas pret.

### Modifier

Modifier un evenement consiste a corriger ou enrichir ses informations.

Les modifications peuvent concerner :

- les informations generales ;
- les occurrences ;
- le lieu ;
- les contacts ou intervenants ;
- les medias ;
- les informations pratiques ;
- les elements de communication.

Regles associees :

- modifier un evenement publie doit rester possible ;
- les informations de partage doivent continuer a pointer vers la meme reference publique lorsque c'est possible ;
- modifier les occurrences peut changer automatiquement l'etat temporel.

### Publier

Publier un evenement consiste a le rendre visible et partageable.

La publication signifie que l'utilisateur considere les informations comme
suffisamment fiables pour le public.

Regles associees :

- un evenement publie doit avoir un nom ;
- un evenement publie doit avoir au moins une occurrence datee ;
- la reference publique devient l'adresse a partager ;
- les elements de communication deviennent utiles apres publication.

### Depublier

Depublier un evenement consiste a retirer sa visibilite publique sans effacer son
contenu.

Cette action sert lorsqu'un evenement a ete publie trop tot, contient une erreur
importante ou ne doit plus etre consultable temporairement.

Regles associees :

- depublier ne supprime pas l'evenement ;
- depublier ne supprime pas ses occurrences ;
- depublier suspend l'usage normal du lien public ;
- l'evenement peut etre republie plus tard.

### Dupliquer

Dupliquer un evenement consiste a creer une nouvelle base a partir d'un evenement
existant.

Cette action est utile pour des ateliers similaires, des sessions repetees ou
des evenements recurrentement organises sans recurrence complexe.

Regles associees :

- la copie doit rester independante de l'original ;
- les dates doivent etre revues par l'utilisateur ;
- l'etat de la copie doit rester en brouillon par defaut ;
- les elements de communication definitifs ne doivent pas etre confondus avec ceux de l'original.

### Annuler

Annuler un evenement consiste a indiquer que l'evenement ne se tiendra pas.

L'annulation est une information importante pour le public lorsque l'evenement a
deja ete partage.

Regles associees :

- un evenement annule peut rester visible pour informer les visiteurs ;
- l'annulation doit etre claire ;
- annuler ne supprime pas l'historique de l'evenement ;
- un evenement annule ne doit pas apparaitre comme un evenement actif ordinaire.

### Archiver

Archiver un evenement consiste a le retirer du travail courant tout en conservant
sa trace.

Regles associees :

- l'archive doit etre une action explicite ;
- archiver ne supprime pas l'evenement ;
- un evenement archive doit rester retrouvable ;
- un evenement archive peut etre restaure si besoin ;
- l'archive se distingue de l'etat termine.

### Supprimer

Supprimer un evenement consiste a le retirer definitivement de l'espace de
travail.

Cette action doit etre rare.

Regles associees :

- la suppression doit demander une confirmation claire ;
- supprimer un evenement supprime sa reference de travail ;
- la suppression ne doit pas etre confondue avec l'archivage ;
- l'utilisateur doit etre encourage a archiver lorsqu'il veut seulement ranger.

## Etats editoriaux et temporels

Le cycle de vie d'un evenement repose sur deux familles d'etats.

### Etats editoriaux

Les etats editoriaux expriment une decision de l'utilisateur.

Ils peuvent etre :

- brouillon ;
- publie ;
- depublie ;
- annule ;
- archive.

Ces etats traduisent l'intention de communication.

### Etats temporels

Les etats temporels expriment la position de l'evenement dans le temps.

Ils peuvent etre :

- a venir ;
- en cours ;
- termine.

Ces etats ne doivent pas etre choisis manuellement. Ils sont deduits des
occurrences.

### Regles de combinaison

- Un brouillon peut etre a venir, en cours ou termine selon ses dates, mais ces indications servent surtout a l'organisation interne.
- Un evenement publie et a venir est pret a etre communique.
- Un evenement publie et en cours peut encore etre partage.
- Un evenement publie et termine reste consultable, mais ne doit plus etre presente comme a venir.
- Un evenement annule doit etre signale comme annule, meme si sa date est future.
- Un evenement archive sort des vues de travail courant, quel que soit son etat temporel.

## Occurrence

### Responsabilite

Une occurrence represente un moment reel ou l'evenement a lieu.

Elle porte la dimension temporelle :

- date ;
- heure de debut si connue ;
- heure de fin si connue ;
- periode si l'evenement dure plusieurs jours.

### Ce qu'elle peut faire

Une occurrence peut :

- etre ajoutee a un evenement ;
- etre modifiee ;
- etre supprimee ;
- etre reordonnee avec les autres occurrences ;
- contribuer au calcul de l'etat temporel ;
- alimenter l'invitation calendrier.

### Ce qu'elle ne peut pas faire

Une occurrence ne peut pas :

- exister seule ;
- remplacer l'evenement ;
- porter toute la description de l'evenement ;
- definir un systeme de recurrence complexe en V1 ;
- gerer des inscriptions propres ;
- gerer une capacite ou une jauge.

### Regles metier

- Une occurrence appartient toujours a un seul evenement.
- Un evenement possede toujours au moins une occurrence.
- Supprimer la derniere occurrence doit etre impossible sans en creer une autre.
- Les occurrences doivent pouvoir etre presentees dans l'ordre chronologique.
- L'ordre chronologique doit rester comprehensible, meme si l'utilisateur a saisi les dates dans un autre ordre.
- Une heure de fin ne doit pas etre anterieure a l'heure de debut pour une meme journee.
- Une periode de plusieurs jours doit rester lisible pour le public.
- La V1 ne gere pas les repetitions complexes, les exceptions, les cycles ou les calendriers avances.

### Ajout

Ajouter une occurrence permet de signaler qu'un meme evenement a lieu a un autre
moment.

Exemples :

- deux sessions du meme atelier ;
- une rencontre sur deux jours ;
- plusieurs dates pour une meme proposition.

### Modification

Modifier une occurrence permet de corriger une date ou un horaire.

Une modification peut changer :

- l'etat temporel de l'evenement ;
- le contenu de l'invitation calendrier ;
- les informations affichees au public.

### Suppression

Supprimer une occurrence permet de retirer une date qui n'a plus lieu ou qui a
ete saisie par erreur.

Cette action doit rester prudente lorsque l'evenement est deja publie.

### Reorganisation

Reorganiser les occurrences permet de presenter clairement plusieurs dates.

La presentation finale doit privilegier la comprehension du public, generalement
par ordre chronologique.

## Lieu

### Responsabilite

Le lieu indique ou se deroule l'evenement lorsque cette information est utile.

Il peut representer :

- un cabinet ;
- une salle ;
- un centre associatif ;
- un lieu public ;
- une adresse de formation ;
- un lieu temporaire.

### Ce qu'il peut faire

Un lieu peut :

- etre associe a un evenement ;
- etre reutilise pour plusieurs evenements ;
- etre cree au moment de la saisie d'un evenement ;
- contenir une adresse ;
- contenir une ville ;
- contenir des indications d'acces ;
- etre laisse vide si l'evenement n'a pas de lieu pertinent.

### Ce qu'il ne peut pas faire

Un lieu ne peut pas :

- etre obligatoire pour tous les evenements ;
- remplacer les informations pratiques ;
- devenir un outil de gestion de salles ;
- gerer des disponibilites ;
- gerer une capacite ;
- imposer une adresse complete lorsque le nom du lieu suffit.

### Regles metier

- Un evenement peut avoir un lieu ou ne pas en avoir.
- Un lieu peut etre partage par plusieurs evenements.
- La reutilisation d'un lieu doit eviter la ressaisie.
- La creation d'un lieu doit rester legere.
- En V1, le lieu est considere au niveau de l'evenement.
- La possibilite d'avoir un lieu different par occurrence reste un arbitrage futur.

## Contact / Intervenant

### Responsabilite

Un contact ou intervenant represente une personne, une equipe ou un canal utile a
la comprehension ou a l'organisation de l'evenement.

Il peut servir a presenter :

- un organisateur ;
- un intervenant ;
- un contact d'inscription ;
- un contact d'information.

Le modele doit rester autonome : un evenement peut contenir ses contacts sans
dependre d'un annuaire externe.

### Ce qu'il peut faire

Un contact ou intervenant peut :

- etre ajoute a un evenement ;
- etre modifie ;
- etre retire d'un evenement ;
- avoir un role lisible ;
- fournir un moyen de contact ;
- fournir une courte consigne de prise de contact ;
- etre reutilise si cela evite une ressaisie ;
- etre eventuellement relie plus tard a une fiche de l'annuaire WP Seed Content lorsque celui-ci est disponible.

### Ce qu'il ne peut pas faire

Un contact ou intervenant ne peut pas :

- etre obligatoire pour tous les evenements ;
- transformer l'evenement en outil de relation client ;
- porter un historique de relation ;
- gerer des campagnes ;
- gerer des inscriptions completes ;
- rendre WP Seed Content obligatoire.

### Regles metier

- Un evenement peut comporter zero, un ou plusieurs contacts.
- Chaque contact associe a un evenement doit avoir un role comprehensible.
- Les roles utiles en V1 sont : organisateur, intervenant, contact d'inscription, contact d'information.
- Un meme contact peut remplir plusieurs roles si c'est plus simple pour l'utilisateur.
- Le moyen de contact doit etre affiche seulement s'il est utile au public.
- La relation future avec un annuaire doit rester optionnelle.
- L'evenement doit rester complet et utilisable meme sans annuaire externe.

## Media

### Responsabilite

Un media est un support visuel ou documentaire qui aide a communiquer
l'evenement.

L'evenement reste la source de verite. Le media illustre, complete ou facilite le
partage, mais ne remplace pas les informations de l'evenement.

### Ce qu'il peut faire

Un media peut :

- illustrer l'evenement ;
- servir d'image principale ;
- servir d'image de communication ;
- representer le recto d'un flyer ;
- representer le verso d'un flyer ;
- composer une galerie ;
- fournir un flyer PDF ;
- enrichir la page publique ;
- aider au partage sur messagerie ou reseaux sociaux.

### Ce qu'il ne peut pas faire

Un media ne peut pas :

- etre obligatoire pour publier en V1 ;
- remplacer le nom de l'evenement ;
- remplacer la date ;
- remplacer le lieu ;
- contenir la seule information fiable sur l'evenement ;
- devenir la source principale des donnees ;
- rendre l'evenement impossible a partager s'il manque.

### Regles metier

- Un evenement peut etre publie sans media.
- Un evenement peut avoir une image principale.
- Un evenement peut avoir deux images de flyer, recto et verso.
- Un evenement peut avoir une galerie.
- Un evenement peut avoir un flyer PDF.
- Ces supports peuvent etre combines.
- Le flyer est un support de communication, jamais une obligation.
- Les informations importantes doivent rester saisies dans l'evenement, meme si elles apparaissent aussi sur un flyer.
- Une image de communication est fortement recommandee pour un partage qualitatif.

## Image de communication

L'image de communication est l'image representative utilisee lorsque l'evenement
est partage.

Elle doit aider le destinataire a identifier rapidement l'evenement.

### Cas image fournie

Si l'utilisateur fournit une image representative, cette image doit etre utilisee
comme image de communication.

Elle peut etre une photo, une creation visuelle, une affiche simplifiee ou un
visuel dedie au partage.

### Cas flyer utilise comme image

Si aucun visuel dedie n'est fourni mais qu'un flyer existe, le flyer peut servir
d'image de communication.

Cette solution est pratique, mais elle peut etre moins lisible dans un petit
apercu. Le produit doit encourager une image simple lorsque c'est possible.

### Cas simple photo utilisee comme image

Une simple photo peut suffire si elle represente clairement l'evenement, le lieu,
l'ambiance ou l'intervenant.

Cette option doit rester accessible aux utilisateurs qui n'ont pas de flyer.

### Cas aucune image fournie

Si aucune image n'est fournie, l'evenement doit rester partageable de maniere
propre.

Une image par defaut peut etre utilisee pour eviter un partage vide ou peu
qualitatif.

Cette image par defaut ne doit pas donner l'impression qu'un visuel specifique a
ete cree pour l'evenement. Elle doit rester sobre et generique.

## Communication

WP Seed Events genere ou rend disponibles les elements suivants :

- URL publique ;
- QR Code ;
- bouton "Ajouter a mon calendrier" ;
- fichier ICS.

### URL publique

L'URL publique est la reference principale de l'evenement.

Elle sert au partage numerique, aux supports imprimes et aux rappels.

### QR Code

Le QR Code pointe vers l'URL publique.

Il sert principalement aux affiches, flyers, programmes, documents imprimes et
supports affiches sur place.

### Bouton "Ajouter a mon calendrier"

Le bouton "Ajouter a mon calendrier" permet au visiteur de conserver l'evenement
dans son propre outil de calendrier.

Le libelle public doit etre comprehensible pour un visiteur non specialiste.

### Fichier ICS

Le fichier ICS contient les informations calendrier de l'evenement.

Il doit refleter les occurrences de l'evenement.

### Hors V1 pour la communication

La publication automatique sur les reseaux sociaux est explicitement hors
perimetre de la V1.

Le produit peut aider a partager, mais il ne publie pas a la place de
l'utilisateur.

## Hors perimetre V1

Les elements suivants ne seront pas developpes en V1 :

- paiement ;
- billetterie ;
- inscription en ligne complete ;
- reservation de places ;
- gestion de jauge ;
- liste d'attente ;
- paiement d'acompte ;
- facturation ;
- newsletter ;
- emailing ;
- campagnes automatiques ;
- publication automatique sur les reseaux sociaux ;
- suivi de performance des partages ;
- statistiques avancees ;
- relation client ;
- historique de relation avec les participants ;
- programme multi-salles complexe ;
- planning d'equipe ;
- gestion de ressources internes ;
- disponibilites de salles ;
- synchronisation avec des services externes ;
- recurrence complexe ;
- exceptions de recurrence ;
- parcours participant complet ;
- evaluation apres evenement ;
- certificats ou attestations ;
- espace participant.

Ces sujets peuvent devenir des extensions futures, mais ils ne definissent pas le
coeur fonctionnel de la V1.

## Resume

Le modele fonctionnel de WP Seed Events repose sur un principe central :
l'evenement est la source de verite.

Les occurrences definissent le temps. Le lieu precise l'endroit. Les contacts et
intervenants clarifient les responsabilites humaines. Les medias soutiennent la
communication. Les elements generes, comme l'URL publique, le QR Code et
l'invitation calendrier, permettent de diffuser l'evenement.

La V1 doit rester autonome, simple et utile. Elle doit couvrir la creation, la
publication, le partage, la fin de vie et l'archivage d'un evenement sans
prendre en charge les fonctions lourdes d'inscription, de paiement, de campagne
ou de gestion relationnelle.

## Decisions fonctionnelles restant a arbitrer

- La description doit-elle etre obligatoire avant publication ou seulement recommandee ?
- L'annulation doit-elle etre un etat editorial distinct ou une information affichee sur un evenement publie ?
- La duplication doit-elle copier les medias ou seulement les informations textuelles et le lieu ?
- Les contacts doivent-ils etre reutilisables des la V1 ou seulement saisis dans chaque evenement ?
- Les roles de contact doivent-ils etre limites a une liste courte ou rester libres ?
- Un evenement multi-occurrences peut-il avoir un lieu different par occurrence apres la V1 ?
- L'image de communication doit-elle etre obligatoire pour certains types de partage ou seulement recommandee ?
- Quelle image par defaut utiliser lorsqu'aucun media n'est fourni ?
- Le libelle visible doit-il utiliser "Ajouter a mon calendrier" partout, avec "ICS" seulement comme format de telechargement ?
- L'archive doit-elle etre proposee automatiquement apres la fin de l'evenement ou rester une action manuelle ?
