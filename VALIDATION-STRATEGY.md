# Validation Strategy

## Intention

Ce document decrit la strategie de validation de WP Seed Events pendant tout son
developpement.

Il est volontairement generique afin de pouvoir servir plus tard de standard de
validation pour les plugins WP Seed.

Chaque lot doit etre valide avant de passer au suivant. La validation ne cherche
pas seulement a confirmer que le developpement fonctionne. Elle verifie aussi que
le produit repond au besoin utilisateur, reste simple et s'integre correctement
dans un vrai site WordPress.

## Philosophie

Un lot n'est pas termine parce qu'il a ete developpe.

Un lot est termine lorsqu'il a ete :

- controle cote developpement ;
- installe et active dans WordPress ;
- valide par rapport au besoin metier ;
- relu du point de vue de l'utilisateur.

Un commit ne remplace jamais une validation.

Une release ne remplace jamais un test reel.

## Niveau 1 - Validation du developpement

### Objectif

S'assurer que le lot est propre, limite et pret a etre teste.

### Points a verifier

- Le workspace est propre avant de commencer.
- Le diff correspond uniquement au lot en cours.
- Aucun fichier parasite n'est present.
- Aucun changement hors sujet n'a ete introduit.
- Le plugin reste generable en archive ZIP.
- Les controles syntaxiques sont faits lorsque l'environnement le permet.
- Aucune dependance inutile n'a ete ajoutee.
- Le lot ne contient pas de refactoring massif non demande.

### Resultat attendu

Le lot peut etre transmis a un environnement WordPress de test sans ambiguite sur
ce qui doit etre valide.

## Niveau 2 - Validation WordPress

### Objectif

Verifier que le plugin s'integre correctement a WordPress.

### Points a verifier

- Le plugin s'installe correctement.
- Le plugin s'active sans erreur fatale.
- Le plugin se desactive sans incident.
- Le plugin peut etre reactive.
- Aucune notice ou warning n'apparait pendant les operations courantes.
- Le plugin apparait correctement dans la liste des extensions.
- Le site reste stable apres activation.
- Les autres extensions essentielles du site continuent de fonctionner.

### Resultat attendu

Le plugin est techniquement acceptable dans un vrai environnement WordPress.

## Niveau 3 - Validation metier

### Objectif

Verifier que la fonctionnalite repond au besoin metier prevu par le lot.

### Questions a poser

- L'utilisateur peut-il reellement accomplir l'action attendue ?
- Le resultat produit correspond-il au besoin ?
- Le lot respecte-t-il le perimetre annonce ?
- Le comportement est-il coherent avec les documents de cadrage ?
- Les cas simples fonctionnent-ils sans detour ?
- Les cas limites prevus sont-ils geres proprement ?

### Exemples

- L'utilisateur peut creer un evenement.
- L'utilisateur peut ajouter une occurrence.
- L'utilisateur peut associer un lieu.
- L'utilisateur peut ajouter un contact.
- L'utilisateur peut telecharger une invitation calendrier.
- L'utilisateur peut recuperer un QR Code.

### Resultat attendu

La fonctionnalite apporte une valeur metier claire et verifiable.

## Niveau 4 - Validation UX

### Objectif

Verifier que l'experience reste simple, comprehensible et utile.

### Questions a poser

- Faut-il lire une documentation pour comprendre quoi faire ?
- L'utilisateur comprend-il immediatement l'action principale ?
- Les libelles sont-ils clairs pour un non-specialiste ?
- Y a-t-il de la ressaisie inutile ?
- Une etape peut-elle etre supprimee ?
- Les choix proposes sont-ils vraiment necessaires ?
- Les erreurs a corriger sont-elles expliquees simplement ?
- Le parcours respecte-t-il le principe de simplicite WP Seed ?

### Resultat attendu

Le lot peut etre utilise naturellement par son public cible, sans sensation
d'outil lourd ou technique.

## Sites de validation

Les validations doivent etre realisees sur plusieurs environnements lorsque le
lot le justifie.

### avecguillaume.fr

Role : validation WordPress standard.

Ce site sert a verifier :

- installation ;
- activation ;
- desactivation ;
- stabilite generale ;
- absence d'erreur visible ;
- comportement standard hors contexte particulier.

### emilieaucoeurdeletre.fr

Role : validation Divi.

Ce site sert a verifier la compatibilite avec un site reel utilisant Divi.

La validation doit confirmer que WP Seed reste autonome, n'impose pas de builder
et ne casse pas l'experience d'un site deja construit avec Divi.

### therapsycorporel.fr

Role : validation fonctionnelle en contexte reel.

Ce site sert a verifier :

- un usage proche du terrain ;
- les parcours utilisateur concrets ;
- la compatibilite eventuelle avec WP Seed Content installe ;
- l'absence de couplage obligatoire avec WP Seed Content.

## Contenus de test

Tous les contenus crees pendant une validation doivent etre prefixes :

SEED TEST -

Ces contenus doivent etre supprimes a la fin de la validation.

La validation ne doit pas polluer les sites utilises.

## Principe fondamental

Un lot n'est termine que lorsque les quatre niveaux de validation sont
satisfaits.

Si un niveau ne peut pas etre valide, le lot doit etre marque comme non valide ou
partiellement valide, avec la raison precise.

Un probleme de validation doit etre traite avant de passer au lot suivant, sauf
decision humaine explicite.

## Check-list avant commit important

- Le lot correspond a une seule responsabilite.
- Le diff est limite au lot.
- Aucun fichier parasite n'est present.
- Le workspace est controle.
- L'archive du plugin est generable si le lot le necessite.
- Les controles syntaxiques disponibles ont ete realises.
- Le plugin s'installe dans un environnement de test.
- Le plugin s'active, se desactive et se reactive sans incident.
- Aucune notice ou warning n'a ete observe.
- La valeur utilisateur du lot est verifiee.
- Le besoin metier est satisfait.
- Le parcours reste simple.
- Les contenus de test sont prefixes correctement.
- Les contenus de test inutiles sont supprimes.
- Les limites ou validations impossibles sont documentees.

## Check-list avant release

- Tous les lots inclus dans la release ont ete valides aux quatre niveaux.
- Les tests WordPress ont ete faits sur les sites de validation pertinents.
- Les tests avec Divi ont ete faits si le rendu public est concerne.
- La cohabitation avec WP Seed Content a ete verifiee si le lot peut l'impacter.
- Aucun contenu de test ne reste sur les sites de validation.
- Les documents de cadrage sont a jour.
- Les notes de release refletent uniquement ce qui est reellement livre.
- La release ne contient pas de fonctionnalite non validee.
- La release ne contient pas de dependance non decidee.
- La release respecte les principes WP Seed : simplicite, autonomie, modularite
  et responsabilite unique.
