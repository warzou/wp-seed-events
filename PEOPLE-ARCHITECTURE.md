# People Architecture

## Intention

Ce document analyse la place des personnes dans l'ecosysteme WP Seed.

La question n'est pas de choisir une solution elegante en theorie. La question
est de choisir une architecture produit simple, autonome, maintenable et capable
d'evoluer naturellement sur 5 a 10 ans.

WP Seed Events doit pouvoir fonctionner seul. Il doit donc pouvoir associer des
personnes simples a un evenement :

- organisateur ;
- intervenant ;
- contact inscription ;
- contact information.

En parallele, WP Seed Content peut porter un annuaire plus riche, avec des fiches
publiques completes : photo, biographie, specialites, coordonnees, liens et
informations de presentation.

La question centrale est donc : ou doit vivre une personne ?

## Besoin reel de WP Seed Events

WP Seed Events n'a pas besoin d'un annuaire complet pour fonctionner.

Son besoin V1 est plus simple :

- nommer une personne ou une equipe liee a un evenement ;
- indiquer son role ;
- afficher un moyen de contact si utile ;
- permettre au visiteur de comprendre qui organise, qui intervient ou qui
  contacter.

Ces informations doivent rester legeres. Elles servent l'evenement. Elles ne
doivent pas transformer WP Seed Events en gestionnaire de profils publics.

## Architecture A - Personnes entierement gerees par WP Seed Events

Dans cette architecture, WP Seed Events devient proprietaire complet des
personnes utilisees par les evenements.

Il gere alors non seulement les contacts simples, mais aussi les fiches plus
riches, reutilisables et potentiellement publiques.

### Avantages

- WP Seed Events reste totalement autonome.
- L'utilisateur n'a qu'un seul outil a utiliser pour ses evenements.
- Les contacts sont directement adaptes au contexte evenementiel.
- Aucun autre module n'est necessaire pour publier un evenement complet.

### Inconvenients

- WP Seed Events risque de depasser son metier principal.
- La gestion des personnes peut devenir un domaine a part entiere.
- Les fiches riches peuvent creer une duplication avec WP Seed Content.
- Les informations de personnes risquent d'etre saisies plusieurs fois dans
  plusieurs plugins.
- L'evenement peut devenir le point d'entree d'une gestion d'annuaire, ce qui
  alourdit le produit.

### Evolutivite

Cette architecture est simple au debut, mais elle vieillit mal si plusieurs
plugins WP Seed ont besoin de personnes.

Elle peut convenir a court terme pour des contacts simples, mais elle devient
moins pertinente des que les personnes doivent exister au-dela des evenements.

### Lecture produit

Architecture A est acceptable seulement si WP Seed Events limite strictement les
personnes a des contacts evenementiels simples.

Elle devient fragile si elle cherche a gerer des fiches completes.

## Architecture B - Personnes entierement gerees par WP Seed Content

Dans cette architecture, WP Seed Content devient proprietaire des personnes.

WP Seed Events doit alors utiliser l'annuaire de WP Seed Content pour associer
des personnes a un evenement.

### Avantages

- Les fiches de personnes sont centralisees.
- Les biographies, photos, specialites et liens ne sont saisis qu'une seule fois.
- La coherence editoriale est meilleure pour les sites qui utilisent deja un
  annuaire.
- Les evenements peuvent beneficier de fiches publiques riches.

### Inconvenients

- WP Seed Events perd son autonomie.
- Un evenement simple dependrait d'un module plus large.
- La creation d'un evenement deviendrait plus lourde si l'utilisateur doit
  d'abord creer une fiche annuaire.
- Le besoin evenementiel simple serait subordonne a un besoin editorial plus
  riche.
- L'installation, la maintenance et les evolutions seraient plus fortement
  couplees.

### Autonomie

Cette architecture est contraire au principe d'autonomie de WP Seed Events.

Elle rend l'annuaire obligatoire pour une fonction pourtant essentielle :
indiquer qui organise, intervient ou repond aux questions.

### Lecture produit

Architecture B est trop rigide pour la V1.

Elle peut etre confortable pour certains sites deja structures autour d'un
annuaire, mais elle impose ce modele a tous les utilisateurs.

## Architecture C - Contacts simples autonomes avec lien optionnel

Dans cette architecture, WP Seed Events reste autonome.

Il possede ses propres contacts simples lies aux evenements.

Si WP Seed Content est installe et qu'une fiche annuaire existe, un contact peut
eventuellement etre relie a cette fiche. Ce lien reste totalement optionnel.

### Simplicite

Cette architecture respecte le besoin immediat :

- creer un evenement rapidement ;
- ajouter une personne ou une equipe sans friction ;
- publier sans dependance externe ;
- enrichir seulement lorsque c'est utile.

L'utilisateur n'est pas oblige de comprendre l'ecosysteme complet pour publier
un evenement.

### Maintenance

La maintenance reste faible.

WP Seed Events conserve un modele simple pour ses besoins propres.

WP Seed Content conserve son role d'annuaire riche lorsqu'il est present.

Le lien entre les deux doit rester une passerelle, pas une obligation.

### Autonomie

L'autonomie est preservee.

WP Seed Events peut fonctionner seul, y compris pour les evenements avec
organisateur, intervenant ou contact d'information.

L'absence de WP Seed Content ne doit jamais bloquer la creation, la publication
ou le partage d'un evenement.

### Experience utilisateur

L'experience reste naturelle :

- pour un besoin simple, l'utilisateur saisit directement le contact ;
- pour un site plus structure, il peut relier ce contact a une fiche existante ;
- l'utilisateur n'est pas force de creer une fiche complete pour un besoin
  ponctuel.

Cette approche evite la ressaisie lorsque l'annuaire existe, sans punir les
utilisateurs qui n'en ont pas besoin.

### Coherence avec l'Option C de l'ecosysteme

Architecture C est coherente avec l'Option C deja retenue pour WP Seed Events :

- domaine evenement autonome ;
- integration optionnelle avec WP Seed Content ;
- aucune dependance obligatoire ;
- separation entre metier evenementiel et enrichissement editorial ;
- evolution progressive de l'ecosysteme.

### Limites

Cette architecture demande de clarifier la responsabilite de chaque donnee :

- le contact simple appartient a l'evenement ;
- la fiche annuaire riche appartient a WP Seed Content ;
- le lien optionnel ne doit pas rendre les deux objets inseparables.

La V1 peut ignorer le lien et conserver seulement les contacts simples. La
passerelle peut venir plus tard.

### Lecture produit

Architecture C est le meilleur compromis actuel.

Elle repond au besoin de la V1 sans fermer l'avenir.

## Possibilite future - WP Seed People

Une quatrieme option serait de creer un futur plugin independant :

WP Seed People.

Ce plugin deviendrait proprietaire des personnes pour tout l'ecosysteme WP Seed.

### Le besoin existe-t-il aujourd'hui ?

Pas encore clairement.

Aujourd'hui, le besoin identifie est double mais limite :

- WP Seed Events a besoin de contacts simples pour les evenements ;
- WP Seed Content peut avoir besoin de fiches annuaire riches.

Cela ne suffit pas encore a justifier un plugin autonome dedie aux personnes.

### Est-ce premature ?

Oui, pour le moment.

Creer WP Seed People maintenant ajouterait une brique centrale avant que
l'ecosysteme ait prouve que plusieurs plugins ont besoin d'un meme socle
personnes.

Ce serait une abstraction trop totive : seduisante, mais potentiellement lourde.

### A partir de quand ce module deviendrait pertinent ?

WP Seed People deviendrait pertinent si plusieurs plugins metier avaient besoin
de personnes partagees de maniere stable.

Exemples possibles :

- evenements ;
- formations ;
- annuaire public ;
- equipe ;
- intervenants ;
- reservations ;
- contenus signes ;
- missions ou accompagnements.

Le seuil raisonnable serait atteint lorsque trois conditions sont reunies :

- au moins trois plugins ou domaines metier utilisent des personnes ;
- les memes personnes doivent etre reutilisees entre ces domaines ;
- la duplication devient un vrai probleme pour les utilisateurs.

### Risques aujourd'hui

Créer WP Seed People maintenant apporterait plusieurs risques :

- dependance centrale prematuree ;
- complexite d'installation ;
- surcharge de maintenance ;
- questions de migration trop totives ;
- experience utilisateur plus abstraite ;
- perte de simplicite pour WP Seed Events ;
- tentation de modeliser trop largement les personnes avant d'avoir les usages.

### Lecture produit

WP Seed People est une piste a garder en memoire, pas une decision V1.

Il pourra devenir pertinent si l'ecosysteme grandit et si la duplication des
personnes devient un probleme concret.

## Recommandation

L'architecture recommandee aujourd'hui est l'Architecture C.

WP Seed Events doit rester autonome et posseder des contacts simples lies aux
evenements.

WP Seed Content peut porter des fiches annuaire riches.

Un lien optionnel entre un contact evenementiel et une fiche annuaire pourra etre
etudie plus tard, mais il ne doit jamais devenir obligatoire.

## Decisions pour la V1

Pour la V1 :

- WP Seed Events gere plusieurs contacts ou intervenants simples par evenement ;
- les roles retenus sont organisateur, intervenant, contact inscription et
  contact information ;
- un contact reste une information de communication liee a l'evenement ;
- aucune fiche annuaire n'est requise ;
- aucune liaison avec WP Seed Content n'est necessaire ;
- aucune dependance a un futur WP Seed People n'est introduite.

## Pistes gardees pour le futur

Les pistes suivantes sont conservees sans engagement V1 :

- relier optionnellement un contact evenementiel a une fiche annuaire existante ;
- eviter la ressaisie lorsqu'une personne existe deja dans un autre module ;
- definir une passerelle simple entre contact evenementiel et fiche riche ;
- observer si plusieurs plugins WP Seed ont besoin d'un socle personnes commun ;
- envisager WP Seed People seulement si le besoin devient transversal, repete et
  suffisamment stable.

## Conclusion

La meilleure architecture produit aujourd'hui est une architecture autonome avec
passerelle optionnelle.

Elle respecte les criteres principaux :

- simplicite ;
- autonomie des plugins ;
- faible maintenance ;
- evolution naturelle de l'ecosysteme.

WP Seed Events ne doit pas devenir un annuaire complet.

WP Seed Content ne doit pas devenir une dependance obligatoire.

WP Seed People ne doit pas etre cree avant que le besoin transversal soit
demontre par plusieurs usages reels.
