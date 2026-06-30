# WordPress Concept

## Intention

Ce document traduit le modele metier de WP Seed Events vers les concepts
WordPress possibles.

Il ne decrit pas encore une architecture detaillee. Son objectif est de verifier
comment WordPress peut soutenir le produit sans le deformer.

Les principes de reference restent :

- autonomie de WP Seed Events ;
- coeur metier evenementiel simple ;
- rendu public minimal ;
- aucune dependance obligatoire a WP Seed Content ;
- aucune ambition de builder ;
- aucune transformation en agenda generaliste.

## 1. Evenement

### Possibilite 1 - Contenu WordPress public

L'evenement peut naturellement devenir un contenu public WordPress.

Cette option correspond bien au produit :

- un evenement a un titre ;
- il a une page publique de reference ;
- il peut etre publie, depublie, previsualise ou archive ;
- il doit etre partage par URL ;
- il doit rester consultable apres sa date ;
- il porte un contenu editorial visible par le public.

Cette representation donne une place claire a l'evenement dans le site. Elle
respecte aussi l'idee que chaque evenement devient une reference stable.

### Possibilite 2 - Objet editorial non public avec page generee

L'evenement pourrait etre conserve comme objet editorial interne, puis produire
une page publique generee.

Cette option separe fortement la saisie et le rendu public. Elle peut sembler
propre, mais elle ajoute une distance inutile pour la V1.

Inconvenients :

- l'utilisateur risque de moins bien comprendre ou vit la page publique ;
- la previsualisation peut devenir moins naturelle ;
- la publication devient plus abstraite ;
- le lien public depend davantage d'une logique de generation.

### Possibilite 3 - Page classique enrichie

L'evenement pourrait etre une page classique enrichie avec des informations
evenementielles.

Cette option est simple en apparence, mais elle risque de melanger le metier
evenement avec la gestion generale des pages du site.

Inconvenients :

- les listes d'evenements deviennent moins naturelles ;
- les statuts temporels sont plus difficiles a isoler ;
- la duplication et l'archivage metier sont moins clairs ;
- le produit perd son espace metier dedie.

### Recommandation

L'evenement doit etre represente comme un contenu public editorial dedie.

Il doit avoir sa propre identite, sa propre page publique et ses propres regles
metier.

## 2. Occurrences

### Possibilite 1 - Objet independant

Chaque occurrence pourrait devenir un objet independant.

Avantages :

- bonne extensibilite pour des cas futurs complexes ;
- chaque date peut porter ses propres informations ;
- utile si chaque occurrence devient presque un evenement autonome.

Inconvenients pour la V1 :

- complexite plus forte ;
- experience de saisie plus lourde ;
- risque de transformer le produit en calendrier avance ;
- maintenance plus importante ;
- modele disproportionne pour des dates simples.

### Possibilite 2 - Composante interne de l'evenement

Les occurrences peuvent etre des composantes internes de l'evenement.

Avantages :

- l'utilisateur reste concentre sur un seul evenement ;
- la saisie de plusieurs dates reste legere ;
- la V1 evite les recurrences complexes ;
- les statuts temporels restent calculables depuis l'evenement ;
- la maintenance reste raisonnable.

Limites :

- les occurrences portent peu d'informations propres ;
- les cas multi-lieux ou tres complexes devront attendre une version future.

### Recommandation

En V1, les occurrences doivent etre des composantes internes de l'evenement.

Elles servent a representer les dates et horaires, pas a creer un calendrier
avance.

## 3. Lieux

### Possibilite 1 - Donnee simple de l'evenement

Le lieu pourrait etre saisi directement dans chaque evenement.

Avantages :

- tres simple pour un premier evenement ;
- peu de concepts a expliquer ;
- aucune gestion separee.

Inconvenients :

- ressaisie frequente ;
- incoherences possibles entre evenements ;
- correction difficile si un lieu est utilise plusieurs fois ;
- contradiction avec l'objectif de reutilisation.

### Possibilite 2 - Objet reutilisable

Le lieu peut etre un objet reutilisable par plusieurs evenements.

Avantages :

- evite la ressaisie ;
- facilite la coherence ;
- correspond aux usages des associations, formateurs et independants ;
- permet de garder les indications d'acces au meme endroit ;
- reste evolutif pour des besoins futurs.

Inconvenients :

- ajoute un objet metier de plus ;
- doit rester leger pour ne pas devenir un outil de gestion de salles.

### Possibilite 3 - Taxonomie

Le lieu pourrait etre une taxonomie.

Cette option est adaptee au classement, mais moins adaptee a un lieu qui porte
une adresse, une ville et des indications d'acces.

Elle risque de reduire le lieu a une etiquette alors que le produit a besoin
d'informations utiles aux visiteurs.

### Recommandation

Le lieu doit etre represente comme un objet reutilisable simple.

Il ne doit pas devenir un systeme de gestion de salles. En V1, un evenement a un
lieu principal unique, meme avec plusieurs occurrences.

## 4. Contacts et intervenants

### Besoin V1

WP Seed Events doit rester autonome.

Il doit pouvoir associer plusieurs contacts ou intervenants simples a un
evenement :

- organisateur ;
- intervenant ;
- contact inscription ;
- contact information.

Ces contacts servent l'evenement. Ils ne sont pas des fiches annuaire completes.

### Possibilite 1 - Contacts internes a l'evenement

Les contacts peuvent etre conserves comme informations simples dans l'evenement.

Avantages :

- autonomie totale ;
- saisie rapide ;
- parfait pour les besoins ponctuels ;
- aucune dependance a un annuaire ;
- experience simple en V1.

Inconvenients :

- reutilisation limitee ;
- risque de ressaisie si les memes personnes reviennent souvent.

### Possibilite 2 - Contacts comme objets reutilisables de WP Seed Events

Les contacts pourraient devenir des objets reutilisables propres a WP Seed
Events.

Avantages :

- moins de ressaisie ;
- meilleure coherence pour les intervenants recurrents ;
- evolutif sans dependance externe.

Inconvenients :

- risque de creer un mini-annuaire dans WP Seed Events ;
- peut depasser le besoin V1 ;
- maintenance plus elevee.

### Possibilite 3 - Liaison optionnelle avec WP Seed Content

Un contact simple peut, plus tard, etre relie a une fiche Annuaire WP Seed
Content si elle existe.

Avantages :

- coherence avec l'ecosysteme ;
- enrichissement possible pour les sites qui ont un annuaire ;
- pas de ressaisie quand une fiche complete existe deja ;
- respect de l'autonomie si le lien reste optionnel.

Inconvenients :

- passerelle a cadrer avec soin ;
- risque de couplage si elle devient trop presente dans l'experience.

### Recommandation

En V1, les contacts doivent rester simples et autonomes dans WP Seed Events.

La liaison optionnelle avec une fiche Annuaire WP Seed Content doit rester une
piste future, sans dependance obligatoire.

## 5. Medias

WordPress possede deja des mecanismes natifs solides pour gerer des medias.

WP Seed Events doit les utiliser sans inventer un systeme media parallele.

### Image de communication

L'image de communication est fortement recommandee.

Elle peut venir de plusieurs sources :

- image fournie par l'utilisateur ;
- flyer image utilise comme image de communication ;
- photo simple ;
- image par defaut si rien n'est fourni.

Elle sert au partage et a la lisibilite publique de l'evenement.

### Flyer recto et flyer verso

Le flyer recto et le flyer verso sont des medias associes a l'evenement.

Ils ne remplacent jamais les informations saisies dans l'evenement.

Le flyer reste facultatif.

### Galerie

La galerie peut enrichir la page publique lorsqu'il existe plusieurs images.

Elle doit rester secondaire par rapport aux informations essentielles :
titre, dates, lieu, contacts et informations pratiques.

### PDF

Un flyer PDF peut etre associe a l'evenement comme support de communication.

Il ne doit pas devenir la seule source d'information fiable.

### Recommandation

Les medias doivent s'appuyer sur les mecanismes natifs WordPress.

WP Seed Events doit seulement definir leur role evenementiel :

- image de communication ;
- flyer recto ;
- flyer verso ;
- galerie ;
- PDF.

## 6. Etats

Le produit distingue trois familles d'etats.

### Etats editoriaux

Les etats editoriaux sont choisis par l'utilisateur ou lies au cycle de
publication.

Ils sont :

- brouillon ;
- publie ;
- depublie ;
- archive ;
- supprime.

Ils indiquent ce que l'utilisateur veut faire de l'evenement dans son espace de
travail et dans sa communication.

### Etats temporels

Les etats temporels sont calcules depuis les occurrences.

Ils sont :

- a venir ;
- en cours ;
- termine.

L'utilisateur ne doit pas les choisir manuellement.

### Etat metier

L'etat metier annule est choisi par l'utilisateur.

Un evenement annule reste consultable s'il a deja ete publie ou partage. Il doit
etre marque clairement comme annule.

Annule n'est ni une suppression, ni un archivage, ni une depubllication.

### Ce qui est saisi, choisi ou calcule

Saisi :

- titre ;
- description ;
- occurrences ;
- lieu ;
- contacts ;
- informations pratiques ;
- medias.

Choisi :

- brouillon ;
- publie ;
- depublie ;
- archive ;
- annule ;
- supprime.

Calcule :

- a venir ;
- en cours ;
- termine ;
- prochaine occurrence ;
- ordre chronologique ;
- informations calendrier ;
- lien utilise par le QR Code.

## 7. Rendu public minimal

WordPress peut produire une page publique V1 simple si WP Seed Events garde un
rendu minimal integre.

Ce rendu doit exister sans builder et sans dependance obligatoire.

Ordre recommande :

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

### Compatibilite future avec WP Seed Content

WP Seed Content pourra plus tard enrichir la presentation ou relier certains
contenus, mais la page publique V1 ne doit pas en dependre.

La compatibilite future doit etre pensee comme une passerelle, pas comme une
condition de fonctionnement.

## 8. Shortcodes et blocs

La V1 peut avoir besoin de moyens simples pour afficher des elements
evenementiels dans d'autres zones du site.

Les besoins identifies sont :

- liste des prochains evenements ;
- liste des evenements en cours ;
- liste des evenements termines ;
- bouton Ajouter a mon calendrier ;
- QR Code eventuel.

### Shortcodes

Les shortcodes sont utiles pour une compatibilite large et une integration simple
dans des contenus existants.

Ils conviennent a des sorties encadrees et limitees.

### Blocs

Les blocs peuvent offrir une experience plus moderne et plus visuelle.

Ils doivent rester des points d'affichage simples, pas devenir un constructeur
de mise en page.

### Recommandation

La V1 peut prevoir des points d'affichage limites.

Ils doivent afficher des informations metier deja structurees, sans creer un
builder interne.

## 9. Pieges WordPress a eviter

WP Seed Events doit eviter :

- trop de CPT ;
- trop de taxonomies ;
- options globales prematurees ;
- builder interne ;
- dependance forte a WP Seed Content ;
- stockage trop complexe ;
- logique metier dispersee ;
- transformation des occurrences en calendrier avance ;
- transformation des contacts en annuaire complet ;
- transformation des lieux en gestion de salles ;
- rendu public impossible sans outil externe ;
- melange entre statut editorial et statut temporel ;
- usage du mot ICS comme libelle principal cote visiteur.

## Conclusion

### Representation WordPress la plus naturelle

Evenement :
contenu public editorial dedie, avec une page de reference stable.

Occurrences :
composantes internes de l'evenement en V1.

Lieu :
objet reutilisable simple, associe comme lieu principal unique a l'evenement.

Contacts et intervenants :
contacts simples autonomes lies a l'evenement, avec passerelle future optionnelle
vers WP Seed Content.

Medias :
medias WordPress natifs qualifies par leur role evenementiel.

Etats :
etats editoriaux choisis, etats temporels calcules, annulation comme etat metier
visible.

Rendu public :
page publique minimale integree, sans builder et sans dependance obligatoire.

Shortcodes et blocs :
points d'affichage limites pour listes, calendrier, QR Code et elements de
partage.

### Alternatives rejetees

- Evenement comme simple page classique.
- Evenement comme objet interne sans vraie page publique.
- Occurrences comme objets independants en V1.
- Lieu comme simple texte saisi dans chaque evenement.
- Lieu comme simple taxonomie.
- Contacts obligatoirement fournis par WP Seed Content.
- Contacts transformes en annuaire complet dans WP Seed Events.
- Medias geres par un systeme parallele.
- Rendu public confie a un builder.

### Raisons

Ces alternatives sont rejetees car elles introduisent au moins un des risques
suivants :

- perte d'autonomie ;
- complexite prematuree ;
- maintenance plus lourde ;
- experience utilisateur moins directe ;
- couplage implicite avec un autre plugin ;
- glissement vers agenda, annuaire, builder ou calendrier avance.

### Points a arbitrer avant WORDPRESS-ARCHITECTURE.md

- Le lieu reutilisable doit-il etre public, interne ou les deux selon le besoin ?
- Les contacts simples doivent-ils etre uniquement saisis dans l'evenement en V1
  ou deja reutilisables entre evenements ?
- Quels points d'affichage V1 sont indispensables : shortcodes, blocs, ou les deux ?
- Le QR Code doit-il etre visible sur la page publique ou seulement disponible
  pour l'utilisateur qui prepare sa communication ?
- Quelle image par defaut utiliser pour l'image de communication ?
- Comment presenter les evenements archives dans l'espace de travail sans les
  confondre avec les evenements termines ?
