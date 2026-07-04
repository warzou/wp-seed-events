# Event Display Model

## Quels sont les besoins reels d'un webmaster ?

Un webmaster ne cherche pas d'abord a configurer un outil. Il cherche a placer
les bons evenements au bon endroit du site.

Ses questions sont concretes :

- que dois-je montrer sur la page d'accueil ?
- quels evenements faut-il mettre en avant ?
- comment afficher seulement les prochains evenements ?
- comment afficher les evenements d'un atelier, d'un lieu ou d'une personne ?
- comment eviter d'afficher des evenements termines au mauvais endroit ?
- comment garder une page claire sans refaire la mise en page a chaque nouvel
  evenement ?

Le modele d'affichage de WP Seed Events doit donc partir des usages de
publication, pas des possibilites techniques.

## Intention

WP Seed Events doit permettre d'afficher des evenements de maniere simple,
lisible et coherente sur un site.

La fiche publique repond a la question :

> Que doit voir un visiteur sur un evenement precis ?

Le modele d'affichage repond a une autre question :

> Comment un webmaster choisit-il quels evenements afficher, ou, et avec quel
> niveau de detail ?

La Phase D doit rester progressive. Elle ne doit pas devenir un systeme de
construction de pages, un agenda complexe ou un outil de reservation.

## Principes d'affichage

- Le webmaster choisit un usage, pas une configuration technique.
- Les evenements a venir sont le cas principal.
- Les informations importantes doivent etre lisibles sans ouvrir chaque fiche.
- Les reglages doivent rester peu nombreux.
- Une liste doit pouvoir etre utile avec ses reglages par defaut.
- Les composants doivent etre reutilisables dans plusieurs contextes.
- Les evenements termines ne doivent pas etre melanges aux evenements a venir
  sans intention claire.
- Les evenements epingles doivent pouvoir remonter dans les listes, sans
  devenir une notion de contenu.
- Les sections vides ne doivent pas apparaitre.

## Cas d'usage

### Afficher les prochains evenements

#### Pourquoi ce besoin existe

C'est le besoin le plus courant. La page d'accueil, une page "Agenda" ou une
page "Ateliers" doit montrer ce qui arrive prochainement.

#### Qui l'utilise

- associations ;
- therapeutes ;
- formateurs ;
- independants ;
- webmasters qui maintiennent un site vivant.

#### Informations indispensables

- titre ;
- prochaine date ;
- lieu si disponible ;
- image si disponible ;
- lien vers la fiche publique.

#### Informations secondaires

- types ;
- courte description ;
- personnes ;
- autres dates ;
- flyer.

### Afficher les evenements d'un type

#### Pourquoi ce besoin existe

Un site peut avoir une page dediee a une activite : ateliers, stages, rencontres
ou journees decouverte.

Le visiteur arrive souvent avec une intention precise. Il veut voir les
evenements correspondant a cette categorie.

#### Qui l'utilise

- formateurs avec plusieurs formats ;
- associations avec plusieurs activites ;
- praticiens proposant ateliers et stages ;
- webmasters structurant des pages thematiques.

#### Informations indispensables

- titre ;
- date ;
- type concerne ;
- lien vers la fiche publique.

#### Informations secondaires

- image ;
- lieu ;
- description courte ;
- personnes.

### Afficher les evenements epingles

#### Pourquoi ce besoin existe

Certains evenements doivent etre plus visibles que les autres : lancement,
evenement important, date a remplir en priorite, rencontre exceptionnelle.

L'epinglage sert a guider l'ordre d'affichage, pas a changer le contenu.

#### Qui l'utilise

- webmaster ;
- responsable de communication ;
- organisateur qui veut mettre un evenement en avant temporairement.

#### Informations indispensables

- titre ;
- date ;
- image si disponible ;
- lien vers la fiche publique.

#### Informations secondaires

- type ;
- lieu ;
- courte description.

### Afficher un seul evenement

#### Pourquoi ce besoin existe

Une page peut vouloir mettre en avant un evenement precis : campagne de
communication, page d'accueil temporaire, encart dans une page existante.

#### Qui l'utilise

- webmaster ;
- organisateur ;
- personne qui prepare une page dediee a une action forte.

#### Informations indispensables

- titre ;
- date ;
- image ou flyer si disponible ;
- lien vers la fiche publique.

#### Informations secondaires

- lieu ;
- type ;
- description courte ;
- personnes.

### Afficher les X prochains evenements

#### Pourquoi ce besoin existe

Certains emplacements sont courts : page d'accueil, colonne laterale, bas de
page, page d'une activite.

Le webmaster doit pouvoir limiter l'affichage sans devoir gerer manuellement la
selection.

#### Qui l'utilise

- webmaster ;
- association avec plusieurs evenements ;
- formateur avec calendrier regulier.

#### Informations indispensables

- nombre d'evenements ;
- titre ;
- prochaine date ;
- lien vers la fiche publique.

#### Informations secondaires

- image ;
- type ;
- lieu.

### Afficher les evenements a venir uniquement

#### Pourquoi ce besoin existe

La plupart des pages publiques ne doivent pas montrer des evenements passes.

Le visiteur cherche ce qu'il peut encore rejoindre ou noter.

#### Qui l'utilise

- tous les sites utilisant WP Seed Events.

#### Informations indispensables

- titre ;
- prochaine date future ;
- lien vers la fiche publique.

#### Informations secondaires

- image ;
- lieu ;
- type.

### Afficher les evenements passes

#### Pourquoi ce besoin existe

Les evenements passes peuvent servir de memoire, de preuve d'activite ou
d'archive publique.

Ils ne doivent pas etre confondus avec les prochains evenements.

#### Qui l'utilise

- associations ;
- lieux culturels ;
- organismes de formation ;
- praticiens voulant montrer l'historique des ateliers.

#### Informations indispensables

- titre ;
- date passee ;
- indication claire que l'evenement est termine ;
- lien vers la fiche publique si elle reste publique.

#### Informations secondaires

- image ;
- type ;
- lieu ;
- description courte.

### Afficher les evenements d'un lieu

#### Pourquoi ce besoin existe

Un lieu peut accueillir plusieurs evenements. Un site peut vouloir proposer une
page dediee a un cabinet, une salle, un centre ou une antenne locale.

#### Qui l'utilise

- associations multi-lieux ;
- praticiens intervenant dans plusieurs salles ;
- structures qui animent un meme lieu regulierement.

#### Informations indispensables

- nom du lieu ;
- titre des evenements ;
- dates ;
- lien vers les fiches publiques.

#### Informations secondaires

- adresse ;
- lien du lieu ;
- types ;
- personnes.

### Afficher les evenements d'une personne

#### Pourquoi ce besoin existe

Une personne peut intervenir dans plusieurs evenements. Le visiteur peut vouloir
retrouver les ateliers d'un intervenant ou les evenements organises par une meme
personne.

#### Qui l'utilise

- formateurs ;
- associations avec plusieurs intervenants ;
- collectifs ;
- sites presentant des praticiens.

#### Informations indispensables

- nom de la personne ;
- role dans chaque evenement ;
- titre ;
- dates ;
- lien vers les fiches publiques.

#### Informations secondaires

- type ;
- lieu ;
- image.

### Afficher les evenements mis en avant

#### Pourquoi ce besoin existe

La mise en avant repond a un besoin editorial : montrer ce qui compte maintenant.

Elle peut recouvrir plusieurs intentions : epinglage, selection manuelle future,
page d'accueil, page thematique.

#### Qui l'utilise

- webmaster ;
- responsable communication ;
- organisateur.

#### Informations indispensables

- titre ;
- date ;
- image si disponible ;
- lien vers la fiche publique.

#### Informations secondaires

- description courte ;
- type ;
- lieu.

### Afficher un agenda simple

#### Pourquoi ce besoin existe

Certains visiteurs aiment parcourir les evenements par ordre chronologique.

L'agenda simple doit rester une liste temporelle, pas un calendrier complexe.

#### Qui l'utilise

- associations ;
- lieux accueillant plusieurs evenements ;
- organismes avec programmation reguliere.

#### Informations indispensables

- dates ;
- titres ;
- etat a venir ou termine ;
- lien vers les fiches publiques.

#### Informations secondaires

- image ;
- type ;
- lieu.

### Afficher les evenements annules

#### Pourquoi ce besoin existe

Un evenement annule peut avoir deja ete partage. Le public doit pouvoir
comprendre qu'il ne se tient pas.

Dans la plupart des listes, l'annulation doit etre visible si l'evenement reste
affiche.

#### Qui l'utilise

- organisateurs ;
- associations ;
- tout site ayant communique un evenement avant son annulation.

#### Informations indispensables

- titre ;
- date ;
- mention Annule ;
- lien vers la fiche publique si elle reste accessible.

#### Informations secondaires

- raison de l'annulation si elle existe plus tard ;
- contact.

## Composants d'affichage

### Liste d'evenements

La liste est le composant principal.

Elle affiche plusieurs evenements dans un ordre lisible. Elle sert a la page
d'accueil, aux pages thematiques, aux archives et aux pages de lieu ou de
personne.

Elle doit etre developpee en premier car elle couvre le plus grand nombre
d'usages.

### Carte evenement

La carte evenement est l'unite de lecture d'une liste.

Elle resume un evenement avec les informations essentielles :

- image si disponible ;
- titre ;
- date ;
- lieu si utile ;
- type si utile ;
- lien vers la fiche publique.

La carte ne doit pas devenir une mini fiche complete.

### Fiche evenement

La fiche evenement est la page publique detaillee.

Elle reprend le modele defini dans EVENT-PUBLIC-MODEL.md : image, titre, types,
dates, lieu, personnes, description, supports et actions utiles.

### Bandeau prochain evenement

Le bandeau sert a mettre en avant un seul evenement dans un espace court.

Il peut etre utilise sur une page d'accueil ou une page thematique.

Il doit afficher peu d'informations :

- titre ;
- date ;
- action vers la fiche publique ;
- image si l'espace le permet.

### Selection mise en avant

La selection mise en avant regroupe les evenements que le webmaster veut rendre
plus visibles.

En premiere intention, elle peut s'appuyer sur les evenements epingles.

Elle ne doit pas devenir un second systeme de classement.

### Agenda simple

L'agenda simple affiche les evenements par ordre chronologique.

Il ne doit pas chercher a reproduire une grille de calendrier avancee.

Il sert aux visiteurs qui veulent parcourir une programmation.

### Archive d'evenements

L'archive presente les evenements passes ou termines.

Elle doit etre clairement separee des evenements a venir.

Elle sert a la memoire publique d'une activite.

### Liste contextuelle

La liste contextuelle affiche des evenements lies a un critere :

- meme type ;
- meme lieu ;
- meme personne ;
- meme statut temporel.

Elle sera utile plus tard pour enrichir les fiches publiques sans ajouter de
complexite au debut.

## Informations affichables

Les composants peuvent afficher les informations suivantes, selon leur niveau de
detail :

- titre ;
- image principale ;
- type(s) ;
- date principale ;
- toutes les dates ;
- mention annule ;
- lieu ;
- adresse ;
- lien du lieu ;
- informations complementaires du lieu ;
- personnes ;
- roles des personnes ;
- description courte ;
- description complete ;
- flyer ou document ;
- lien vers la fiche publique.

Toutes ces informations ne doivent pas etre visibles partout. La simplicite
vient du bon niveau de detail pour le bon contexte.

## Ce qui devra etre configurable

### Nombre d'evenements

Le webmaster doit pouvoir limiter le nombre d'evenements affiches.

Reglage simple recommande :

- tous ;
- ou un nombre defini.

### Periode affichee

Le webmaster doit pouvoir choisir si l'affichage concerne :

- les evenements a venir ;
- les evenements passes ;
- tous les evenements ;
- les evenements annules, si cela devient utile.

Le choix par defaut doit etre les evenements a venir.

### Ordre

L'ordre doit rester simple :

- prochains en premier ;
- plus recents en premier pour les archives ;
- epingles en premier si l'option est active.

Le tri avance ne doit pas etre propose trop tot.

### Filtre par type

Le webmaster doit pouvoir afficher les evenements d'un ou plusieurs types.

Ce reglage sert aux pages thematiques.

### Filtre par lieu

Le webmaster doit pouvoir afficher les evenements d'un lieu.

Ce reglage sert aux structures avec plusieurs espaces ou antennes.

### Filtre par personne

Le webmaster doit pouvoir afficher les evenements lies a une personne.

Ce reglage sert aux pages intervenants, formateurs ou organisateurs.

### Afficher ou masquer les images

Les images sont utiles dans les pages visuelles, mais pas toujours dans les
listes compactes.

Reglage simple :

- afficher l'image ;
- masquer l'image.

### Afficher ou masquer la description

La description peut rendre une liste trop longue.

Reglage simple :

- aucune description ;
- extrait court ;
- description complete seulement dans la fiche.

### Afficher ou masquer le lieu

Le lieu est souvent utile, mais peut etre inutile sur une page dediee a un lieu.

### Afficher ou masquer les personnes

Les personnes sont utiles sur certaines pages, mais pas dans toutes les cartes.

### Afficher ou masquer les types

Les types aident a situer l'evenement, mais peuvent etre redondants sur une page
de type.

### Afficher les evenements epingles en premier

L'epinglage doit rester une option d'ordre, pas une information centrale.

### Niveau de detail

Plutot que multiplier les options, WP Seed Events peut proposer quelques niveaux
de detail :

- compact ;
- standard ;
- detaille.

Ce principe est plus simple qu'une longue liste de cases a cocher.

## Reglages a eviter

La Phase D ne doit pas introduire :

- builder interne ;
- styles avances dans tous les sens ;
- reglages techniques ;
- logique de reservation ;
- logique de paiement ;
- cartes obligatoires ;
- filtres trop nombreux des le depart ;
- personnalisation exhaustive de chaque libelle ;
- affichages qui demandent une documentation pour etre compris.

## Roadmap Phase D

### D1 - Modele d'affichage

Objectif : definir les usages, les composants et les priorites.

Livrable : ce document.

### D2 - Fiche publique minimale

Objectif : permettre a un visiteur de consulter un evenement precis.

Pourquoi en premier :

- la fiche publique est la destination naturelle des listes ;
- elle sert de reference partageable ;
- elle valide l'ordre des informations cote visiteur.

### D3 - Carte evenement

Objectif : creer une unite de resume reutilisable.

Pourquoi ensuite :

- toutes les listes auront besoin d'une carte ou d'une ligne d'evenement ;
- elle impose de choisir les informations essentielles ;
- elle evite de recreer plusieurs affichages incoherents.

### D4 - Liste des prochains evenements

Objectif : afficher les evenements a venir de maniere simple.

Pourquoi ensuite :

- c'est le besoin webmaster le plus courant ;
- elle peut reutiliser la carte evenement ;
- elle servira de base aux affichages filtres.

### D5 - Options simples d'affichage

Objectif : permettre quelques choix utiles sans complexite.

Priorites :

- nombre d'evenements ;
- images visibles ou masquees ;
- description visible ou masquee ;
- epingles en premier.

### D6 - Listes filtrees

Objectif : afficher les evenements par type, lieu ou personne.

Pourquoi plus tard :

- ces usages deviennent utiles quand le site contient plusieurs evenements ;
- ils reposent sur la meme liste et la meme carte.

### D7 - Archives et evenements passes

Objectif : afficher proprement la memoire des evenements termines.

Pourquoi apres les listes a venir :

- les evenements futurs sont prioritaires pour la communication ;
- les archives doivent etre separees pour eviter la confusion.

### D8 - Bandeau prochain evenement

Objectif : proposer un affichage court pour la page d'accueil.

Pourquoi plus tard :

- il reutilise les memes informations que la carte ;
- il est utile mais moins fondamental que la fiche et la liste.

### D9 - Agenda simple

Objectif : proposer une lecture chronologique plus complete.

Pourquoi plus tard :

- il peut vite devenir un calendrier avance ;
- il doit rester simple et s'appuyer sur les composants deja valides.

## Reutilisation des composants

Les composants doivent etre concus comme des briques produit reutilisables.

La meme fiche, la meme carte et la meme liste doivent pouvoir servir plus tard a
plusieurs canaux d'affichage :

- insertions simples dans une page ;
- blocs editoriaux ;
- widgets ;
- modeles de pages ;
- archives WordPress ;
- pages gerees par un theme.

Le webmaster ne doit pas percevoir ces canaux comme des produits differents. Il
doit retrouver les memes evenements, les memes informations et la meme logique
d'affichage.

## Priorite recommandee

Le premier composant a developper apres ce document est la fiche publique
minimale.

Raison :

- elle donne une destination stable a chaque evenement ;
- elle valide ce que voit reellement le visiteur ;
- elle permet ensuite aux listes de pointer vers quelque chose de fiable ;
- elle evite de construire des affichages de listes avant de savoir comment un
  evenement complet se presente au public.

Le deuxieme composant doit etre la carte evenement.

Le troisieme composant doit etre la liste des prochains evenements.

Cet ordre respecte la logique produit :

1. comprendre un evenement ;
2. resumer un evenement ;
3. afficher plusieurs evenements.

## Regle de decision

Lorsqu'un affichage hesite entre deux solutions, choisir celle qui :

1. repond au besoin le plus frequent ;
2. montre moins d'options ;
3. affiche mieux les informations essentielles ;
4. peut etre reutilisee ailleurs ;
5. reste comprehensible sans documentation.

La bonne Phase D n'est pas celle qui permet tout. C'est celle qui permet aux
webmasters d'afficher leurs evenements simplement, proprement et durablement.
