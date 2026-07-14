# Event Card Model

## Intention

L'Event Card est le composant de base qui permet a un visiteur de reconnaitre un
evenement sans ouvrir sa fiche complete.

Elle doit repondre a une question simple :

> En trois secondes, que dois-je comprendre ?

La carte ne doit pas tout dire. Elle doit donner assez d'informations pour que
le visiteur sache :

- de quoi il s'agit ;
- quand cela a lieu ;
- ou cela se passe, si le lieu est connu ;
- pourquoi cela peut l'interesser ;
- comment acceder au detail.

L'Event Card est donc un resume oriente decision. Elle sert a parcourir,
comparer et choisir.

## Role dans WP Seed Events

L'Event Card deviendra l'unite de lecture commune des affichages publics de WP
Seed Events.

Elle doit pouvoir servir dans plusieurs contextes :

- listes d'evenements ;
- pages d'accueil ;
- zones de mise en avant ;
- archives ;
- affichages compacts ;
- affichages thematiques ;
- futurs composants de presentation.

Le visiteur ne doit pas avoir l'impression de voir des formats differents selon
l'endroit du site. La carte doit rester reconnaissable, meme lorsqu'elle est
adaptee a un espace plus large ou plus etroit.

## Ce que le visiteur doit comprendre en trois secondes

Dans l'ordre, le visiteur doit comprendre :

1. l'evenement est-il encore pertinent ?
2. quel est son titre ?
3. quand a-t-il lieu ?
4. ou a-t-il lieu ?
5. de quel type d'evenement s'agit-il ?
6. puis-je en savoir plus ?

La carte doit donc privilegier les informations qui aident a decider rapidement.

Les actions, les details secondaires et les supports complementaires viennent
apres.

## Informations obligatoires

Une Event Card V1 doit toujours afficher :

- le titre ;
- un lien ou bouton vers la fiche de l'evenement.

Lorsqu'une occurrence active existe, elle affiche aussi une date de reference.
Cette date est la prochaine occurrence active si elle existe, sinon la derniere
occurrence active. L'absence de date active ne doit pas bloquer l'affichage
d'une carte lorsque la collection autorise cet evenement.

## Informations fortement recommandees

Les informations suivantes ameliorent fortement la carte, mais ne doivent pas
bloquer son affichage :

- illustration principale ;
- type(s) d'evenement ;
- lieu ;
- court resume.

Ces elements aident le visiteur a reconnaitre et comparer les evenements.

## Informations optionnelles

Les informations suivantes apparaissent seulement si elles existent et si le
format de carte les autorise :

- horaires ;
- mention Toute la journee ;
- mention Annule ;
- plusieurs dates ;
- adresse courte ;
- personnes principales ;
- roles ;
- badge d'evenement epingle ;
- flyer disponible ;
- courte information pratique.

Aucune zone vide ne doit etre affichee.

Si une information n'existe pas, elle disparait naturellement.

## Informations a ne pas afficher dans la carte V1

La carte V1 ne doit pas afficher :

- description complete ;
- galerie complete ;
- flyer PDF comme contenu principal ;
- details complets des personnes ;
- telephone ;
- email ;
- lien de personne ;
- informations complementaires longues du lieu ;
- cartes ;
- reservation ;
- paiement ;
- liste d'attente ;
- statistiques ;
- informations techniques.

Ces informations appartiennent a la fiche complete ou a des phases futures.

## Priorites visuelles

La carte doit donner plus de poids aux informations qu'aux actions.

Priorite recommandee :

1. illustration ou zone visuelle ;
2. titre ;
3. date ;
4. lieu ;
5. type(s) ;
6. resume court ;
7. action.

Le bouton ne doit pas prendre le dessus sur le contenu. Il doit etre clair, mais
pas dominant au point de transformer la carte en publicite agressive.

## Ordre de lecture

L'ordre de lecture V1 recommande est :

1. illustration ;
2. type(s) ;
3. titre ;
4. date principale ;
5. lieu ;
6. resume court ;
7. action.

Cet ordre fonctionne parce qu'il combine reconnaissance visuelle, comprehension
du sujet et decision.

L'illustration attire l'oeil.
Le type situe l'evenement.
Le titre donne le sens.
La date et le lieu permettent de savoir si l'evenement est possible.
Le resume donne envie.
L'action ouvre la suite.

## Illustration

L'illustration est recommandee, mais elle ne doit pas etre obligatoire.

Sources possibles :

- image principale de l'evenement ;
- premiere illustration pertinente ;
- image issue d'un flyer ;
- image par defaut future, si le produit en decide une.

La carte ne doit pas afficher un espace vide si aucune image n'existe.

Si aucune illustration n'est disponible, la carte reste lisible avec une mise en
page plus textuelle.

L'image doit servir l'evenement. Elle ne doit pas etre decorative au point de
masquer les informations essentielles.

## Titre

Le titre est l'information centrale.

Il doit etre :

- lisible rapidement ;
- plus visible que les types ;
- plus visible que les actions ;
- limite a une longueur raisonnable dans la carte.

Si le titre est long, il peut etre coupe visuellement, mais la carte doit rester
comprehensible.

## Type(s)

Les types aident a situer l'evenement.

Ils doivent rester discrets.

Exemples :

- Atelier ;
- Stage ;
- Rencontre ;
- Non classe.

Si plusieurs types existent, ils peuvent etre affiches en ligne courte.

La carte ne doit pas devenir une liste de badges. Si les types prennent trop de
place, seuls les plus utiles doivent etre visibles dans la V1.

## Date(s)

La date est une information prioritaire.

La carte V1 doit afficher une date de reference : la prochaine occurrence active
si elle existe, sinon la derniere occurrence active. Si aucune occurrence active
n'existe, elle n'affiche pas de date.

Format recommande :

- Samedi 12 septembre 2026 ;
- ou 12/09/2026 dans les formats compacts.

Si l'heure existe, elle peut etre affichee juste sous la date :

- 19h30 -> 22h00 ;
- Toute la journee.

Si l'evenement possede plusieurs dates, la carte ne doit pas toutes les afficher
par defaut. Elle peut indiquer :

- date de reference visible ;
- mention courte : + 2 autres dates.

La liste complete des dates appartient a la fiche evenement.

Si une date est annulee, elle ne doit pas etre presentee comme une date normale.

Si tout l'evenement est annule, la carte doit afficher l'annulation avant le
reste.

## Lieu

Le lieu aide le visiteur a evaluer rapidement si l'evenement est accessible.

La carte V1 affiche de preference :

- nom du lieu ;
- ville ou adresse courte si disponible et utile.

Elle ne doit pas afficher :

- toutes les informations complementaires ;
- un long texte d'acces ;
- un plan ;
- des details pratiques longs.

Si le lieu n'est pas renseigne, la ligne disparait.

## Resume court

Le resume court est optionnel.

Il sert a donner le ton de l'evenement sans transformer la carte en fiche
complete.

Il doit rester :

- court ;
- lisible ;
- secondaire par rapport au titre, a la date et au lieu.

Si aucune description courte n'existe, la carte peut simplement ne pas afficher
de resume.

Le resume ne doit pas etre genere artificiellement si cela produit un texte peu
utile.

## Action

L'action principale doit etre simple.

Libelle recommande :

- Voir l'evenement ;

ou, selon le ton du site :

- En savoir plus.

La carte ne doit pas multiplier les actions en V1.

Les actions avancees appartiennent a la fiche complete ou a des phases futures.

## Informations secondaires

Les informations secondaires ne doivent pas concurrencer la lecture principale.

Elles peuvent etre utiles dans certains formats :

- personnes principales ;
- role d'un intervenant ;
- mention flyer disponible ;
- mention evenement epingle ;
- indication d'annulation partielle.

Elles doivent rester rares et contextuelles.

Si une information secondaire rend la carte plus lourde, elle doit etre
supprimee de la carte V1.

## Variantes comparees

### Carte verticale

La carte verticale place l'image au-dessus, puis le texte en dessous.

Avantages :

- tres lisible sur mobile ;
- adaptee aux grilles ;
- met bien en valeur les illustrations ;
- facile a parcourir visuellement ;
- bonne pour les pages d'accueil et listes visuelles.

Inconvenients :

- peut prendre plus de hauteur ;
- moins efficace dans les listes tres denses ;
- depend davantage de la qualite des images.

### Carte horizontale

La carte horizontale place l'image a gauche et les informations a droite.

Avantages :

- bonne lisibilite sur desktop ;
- efficace pour les listes chronologiques ;
- permet de comparer plusieurs evenements ;
- laisse la date et le lieu visibles rapidement.

Inconvenients :

- moins naturelle sur mobile ;
- peut devenir serree si le titre est long ;
- demande une adaptation responsive plus nette.

### Carte compacte

La carte compacte reduit fortement l'affichage.

Elle peut contenir uniquement :

- date ;
- titre ;
- lieu ;
- action discrete.

Avantages :

- utile dans les espaces etroits ;
- efficace pour une colonne laterale ;
- faible encombrement ;
- bonne pour afficher plusieurs evenements.

Inconvenients :

- moins engageante ;
- peu de place pour l'image ;
- moins adaptee aux pages qui doivent donner envie.

### Carte riche

La carte riche affiche davantage d'informations :

- grande image ;
- types ;
- titre ;
- date ;
- lieu ;
- resume ;
- personnes ;
- action ;
- informations complementaires.

Avantages :

- donne beaucoup de contexte ;
- utile pour un evenement important ;
- proche d'une mini-fiche.

Inconvenients :

- risque de surcharge ;
- concurrence la fiche complete ;
- rend les listes longues ;
- complique la reutilisation.

## Recommandation V1

La V1 doit adopter une carte standard unique, avec deux adaptations visuelles :

- verticale dans les grilles et sur mobile ;
- horizontale lorsque l'espace de liste le justifie.

Il ne s'agit pas de deux cartes differentes.

Il s'agit de la meme carte, avec le meme ordre d'information et le meme niveau
de detail, adaptee a l'espace disponible.

### Structure recommandee V1

1. Illustration, si disponible ;
2. Type(s), discretement ;
3. Titre ;
4. Prochaine date ;
5. Horaire, si disponible ;
6. Lieu, si disponible ;
7. Resume court, si disponible ;
8. Action : Voir l'evenement.

### Pourquoi cette carte

Cette carte est le meilleur compromis pour la V1 parce qu'elle :

- repond au besoin de lecture en trois secondes ;
- reste utile sans image ;
- ne duplique pas la fiche complete ;
- peut etre reutilisee dans plusieurs contextes ;
- respecte la simplicite WP Seed ;
- evite d'ouvrir trop tot des options d'affichage.

## Comportement responsive

### Mobile

Sur mobile, la carte doit etre principalement verticale.

Ordre recommande :

1. image ;
2. types ;
3. titre ;
4. date ;
5. lieu ;
6. resume ;
7. action.

Les informations doivent rester empilees, lisibles et sans colonnes serrees.

L'action doit etre facile a toucher, mais ne pas masquer le contenu.

### Tablette

Sur tablette, la carte peut rester verticale dans une grille ou devenir
horizontale dans une liste.

Le choix depend de l'espace disponible, pas d'un nouveau modele de carte.

### Desktop

Sur desktop, deux usages sont possibles :

- grille visuelle : carte verticale ;
- liste chronologique : carte horizontale.

Le contenu affiche reste le meme.

Le visiteur doit retrouver les memes informations, simplement disposees
differemment.

## Etats particuliers

### Evenement annule

Si l'evenement est annule, la mention Annule doit apparaitre avant le titre ou
tres pres du titre.

Le visiteur ne doit pas decouvrir l'annulation apres avoir clique.

### Date annulee

Si seule une date est annulee et qu'il existe d'autres dates valides, la carte
doit privilegier la prochaine date valide.

La mention des dates annulees peut rester dans la fiche complete.

Une occurrence annulee ne doit jamais devenir la date de reference. Si aucune
occurrence active n'existe, la carte n'affiche pas de date active.

### Evenement termine

Dans une archive ou une liste d'evenements passes, la carte peut afficher une
mention discrete :

- Termine ;
- Passe.

Cette mention ne doit pas apparaitre dans les listes de prochains evenements.

### Evenement epingle

L'epinglage peut influencer l'ordre d'affichage.

Dans la carte V1, l'epinglage ne doit pas devenir une information centrale.

Si une indication visuelle existe, elle doit rester discrete.

## Disparition automatique des informations

La carte doit disparaitre par blocs simples.

Si aucune image n'existe :

- l'espace image disparait ;
- le texte devient prioritaire.

Si aucun type n'existe :

- la ligne des types disparait.

Si aucun lieu n'existe :

- la ligne du lieu disparait.

Si aucun resume n'existe :

- le resume disparait.

Si aucune heure n'existe :

- seule la date est affichee.

La carte ne doit jamais afficher :

- Aucun lieu ;
- Pas de description ;
- Aucun type ;
- Image manquante.

Ces formulations appartiennent a l'administration, pas au public.

## Erreurs a eviter

L'Event Card V1 doit eviter :

- trop d'informations ;
- plusieurs boutons ;
- une image qui prend toute l'attention ;
- un titre noye dans les badges ;
- une date affichee dans un format technique ;
- un lieu trop long ;
- des sections vides ;
- un style different selon chaque page ;
- une carte qui ressemble a une fiche complete ;
- une carte qui depend d'un flyer PDF pour etre comprise ;
- des actions avancees avant que le visiteur ait compris l'evenement ;
- une option de personnalisation pour chaque detail.

## Regle de decision

Lorsqu'une information hesite a entrer dans la carte, poser ces questions :

1. aide-t-elle le visiteur a comprendre en trois secondes ?
2. aide-t-elle a choisir entre plusieurs evenements ?
3. reste-t-elle lisible sur mobile ?
4. disparait-elle proprement si elle manque ?
5. appartient-elle vraiment a la carte, ou plutot a la fiche complete ?

Si la reponse est incertaine, l'information reste hors de la carte V1.

## Resume

L'Event Card V1 de WP Seed Events doit etre une carte standard, sobre et
reutilisable.

Elle affiche en priorite :

1. illustration si disponible ;
2. type(s) ;
3. titre ;
4. date de reference si disponible ;
5. horaire si disponible ;
6. lieu si disponible ;
7. resume court si disponible ;
8. action Voir l'evenement.

Elle doit permettre a un visiteur de comprendre rapidement l'evenement sans
remplacer la fiche publique.

La carte V1 recommandee est une carte standard adaptable : verticale par defaut,
horizontale lorsque le contexte de liste le justifie.

Le principe fondamental reste :

> Montrer juste assez pour comprendre et donner envie d'ouvrir la fiche.
