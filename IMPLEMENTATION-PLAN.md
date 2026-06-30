# Implementation Plan

## Intention

Ce plan decoupe la V1 de WP Seed Events en petits lots operationnels.

Chaque lot doit etre livre, teste et valide avant le suivant. Les noms de fichiers
ci-dessous sont indicatifs et devront etre confirmes par l'architecture
WordPress.

## Lot 0 - Bootstrap plugin

Objectif : creer un plugin WordPress activable, sans fonctionnalite metier.

Fichiers probables :

- fichier d'entree du plugin ;
- dossier de base du plugin ;
- fichier de desinstallation ou note de cycle de vie si retenu ;
- documentation courte de lancement local.

Validations attendues :

- le plugin s'active ;
- le plugin se desactive sans erreur ;
- aucune fonctionnalite metier n'est encore exposee ;
- aucune dependance externe n'est requise.

Risques :

- introduire trop tot une structure lourde ;
- ajouter une dependance inutile ;
- commencer le metier avant le socle.

Critere de sortie :

- plugin activable, vide fonctionnellement, pret pour le lot 1.

## Lot 1 - CPT Evenement

Objectif : creer le contenu evenement public minimal.

Fichiers probables :

- module de declaration du contenu evenement ;
- module d'activation du plugin ;
- documentation interne du type de contenu.

Validations attendues :

- un evenement peut exister comme contenu public dedie ;
- brouillon et publication fonctionnent ;
- la page publique de base existe ;
- aucun autre objet metier n'est cree.

Risques :

- ajouter trop d'options editoriales ;
- melanger evenement et page classique ;
- ouvrir deja les occurrences, lieux ou contacts.

Critere de sortie :

- evenement minimal cree, consultable et publiable.

## Lot 2 - Champs evenement essentiels

Objectif : ajouter titre, description, dates simples et etat annule.

Fichiers probables :

- module des champs evenement ;
- module de validation metier ;
- module d'administration minimale.

Validations attendues :

- titre obligatoire ;
- description recommandee mais non bloquante ;
- une date simple peut etre saisie ;
- l'etat annule peut etre choisi ;
- un evenement annule reste consultable et marque clairement.

Risques :

- bloquer inutilement les brouillons ;
- confondre annulation et suppression ;
- rendre les dates trop complexes.

Critere de sortie :

- evenement simple publiable avec date et annulation visible.

## Lot 3 - Occurrences

Objectif : gerer plusieurs dates simples sans recurrence complexe.

Fichiers probables :

- module occurrences ;
- module de calcul temporel ;
- ajustement des champs evenement ;
- tests de cas dates.

Validations attendues :

- plusieurs occurrences peuvent etre ajoutees ;
- elles sont affichees dans un ordre comprehensible ;
- la derniere occurrence ne peut pas etre supprimee sans remplacement ;
- les statuts a venir, en cours et termine sont calcules.

Risques :

- creer un calendrier avance ;
- gerer des exceptions de recurrence ;
- disperser la logique temporelle.

Critere de sortie :

- plusieurs dates simples fonctionnent et pilotent le statut temporel.

## Lot 4 - Lieux

Objectif : ajouter un lieu principal reutilisable.

Fichiers probables :

- module lieu ;
- module d'association evenement-lieu ;
- interface de choix ou creation rapide ;
- ajustement du rendu public.

Validations attendues :

- un lieu peut etre cree ;
- un lieu peut etre reutilise ;
- un evenement peut rester sans lieu ;
- un evenement conserve un seul lieu principal en V1.

Risques :

- transformer les lieux en gestion de salles ;
- permettre les multi-lieux trop tot ;
- rendre le lieu obligatoire.

Critere de sortie :

- lieu principal reutilisable operationnel et optionnel.

## Lot 5 - Contacts / intervenants

Objectif : ajouter plusieurs contacts simples lies a l'evenement.

Fichiers probables :

- module contacts evenementiels ;
- module de roles de contact ;
- ajustement de l'edition evenement ;
- ajustement du rendu public.

Validations attendues :

- plusieurs contacts peuvent etre ajoutes ;
- les roles sont organisateur, intervenant, contact inscription et contact information ;
- aucun annuaire externe n'est requis ;
- l'absence de WP Seed Content ne change rien au fonctionnement.

Risques :

- creer un annuaire complet ;
- rendre les contacts obligatoires ;
- introduire un couplage implicite avec WP Seed Content.

Critere de sortie :

- contacts simples visibles et autonomes.

## Lot 6 - Medias

Objectif : ajouter image de communication, flyer image/PDF et galerie.

Fichiers probables :

- module medias evenementiels ;
- module image de communication ;
- ajustement de l'edition evenement ;
- ajustement du rendu public.

Validations attendues :

- une image de communication peut etre choisie ;
- un flyer recto ou verso peut etre associe ;
- un PDF peut etre associe ;
- une galerie peut etre associee ;
- un fallback existe si aucune image n'est fournie.

Risques :

- rendre le flyer obligatoire ;
- faire du media la source de verite ;
- creer un systeme media parallele.

Critere de sortie :

- evenement partageable visuellement avec ou sans media fourni.

## Lot 7 - Rendu public minimal

Objectif : produire une page evenement propre et partageable.

Fichiers probables :

- module de rendu public ;
- gabarit minimal evenement ;
- styles publics minimaux ;
- ajustement des donnees exposees.

Validations attendues :

- ordre d'affichage respecte ;
- annulation visible en premier si presente ;
- image, titre, description, dates, lieu, contacts et informations pratiques sont lisibles ;
- aucun builder n'est necessaire ;
- WP Seed Content n'est pas requis.

Risques :

- transformer le rendu en moteur de templates ;
- surcharger la page ;
- rendre la presentation dependante d'un autre plugin.

Critere de sortie :

- page publique V1 claire, stable et autonome.

## Lot 8 - Ajouter a mon calendrier

Objectif : fournir le bouton calendrier avec libelle utilisateur clair.

Fichiers probables :

- module calendrier ;
- module de generation ICS ;
- ajustement du rendu public ;
- tests de telechargement.

Validations attendues :

- le libelle public utilise "Ajouter a mon calendrier" ou equivalent ;
- les occurrences sont reprises correctement ;
- le telechargement fonctionne ;
- le mot ICS n'est pas le libelle principal cote visiteur.

Risques :

- exposer un vocabulaire trop technique ;
- oublier les evenements multi-occurrences ;
- produire une invitation incoherente apres modification des dates.

Critere de sortie :

- bouton calendrier utilisable sur un evenement publie.

## Lot 9 - QR Code

Objectif : produire un QR Code de l'URL publique.

Fichiers probables :

- module QR Code ;
- module de partage ;
- ajustement de l'administration evenement ;
- ajustement optionnel du rendu public.

Validations attendues :

- le QR Code pointe vers l'URL publique ;
- il n'est pas genere depuis une URL temporaire ;
- il reste disponible apres publication ;
- son affichage public ou prive respecte la decision retenue.

Risques :

- pointer vers une mauvaise URL ;
- generer trop tot des fichiers permanents inutiles ;
- rendre le QR Code central alors qu'il reste un support de partage.

Critere de sortie :

- QR Code fiable pour l'evenement publie.

## Lot 10 - Shortcodes listes

Objectif : afficher prochains evenements, evenements en cours et evenements termines.

Fichiers probables :

- module shortcodes ;
- module de requetes evenementielles ;
- module de rendu de liste ;
- tests de tri temporel.

Validations attendues :

- liste des prochains evenements ;
- liste des evenements en cours ;
- liste des evenements termines ;
- exclusion ou traitement clair des archives ;
- pas de builder interne.

Risques :

- multiplier les options ;
- disperser les regles temporelles ;
- transformer les listes en calendrier avance.

Critere de sortie :

- listes simples utilisables dans un contenu existant.

## Lot 11 - Duplication / archivage / annulation

Objectif : ajouter les actions metier simples.

Fichiers probables :

- module actions evenement ;
- module duplication ;
- module archivage ;
- ajustement des listes d'administration.

Validations attendues :

- la duplication cree un brouillon ;
- elle copie titre, description, occurrences, lieu, contacts, informations pratiques et medias ;
- l'archivage est manuel ;
- un evenement termine n'est pas archive automatiquement ;
- l'annulation reste distincte de la suppression.

Risques :

- copier des donnees qui devraient etre revues ;
- confondre archive et termine ;
- rendre une action destructive trop facile.

Critere de sortie :

- actions metier fiables, explicites et reversibles quand necessaire.

## Lot 12 - Tests reels / nettoyage / release V1

Objectif : valider la V1 en conditions proches d'un usage reel.

Fichiers probables :

- check-list de validation ;
- documentation utilisateur courte ;
- notes de release ;
- ajustements mineurs issus des tests.

Validations attendues :

- creation d'un evenement simple ;
- evenement multi-occurrences ;
- evenement avec lieu reutilise ;
- evenement avec plusieurs contacts ;
- evenement avec image et flyer ;
- evenement annule ;
- evenement termine puis archive manuellement ;
- calendrier, QR Code et listes fonctionnels ;
- absence de dependance obligatoire a WP Seed Content.

Risques :

- ajouter de nouvelles fonctions pendant le nettoyage ;
- repousser les tests reels trop tard ;
- melanger correctifs et nouvelles idees V2.

Critere de sortie :

- V1 stable, documentee, testee et prete pour validation humaine de release.
