# Composant public Personnes

## Role

Le composant public Personnes affiche les personnes associees a un evenement, leurs roles et uniquement les coordonnees dont la publication a ete explicitement autorisee pour cette association.

La chaine de reference est :

`association Personne-evenement -> Event Data publique filtree -> renderer partage Personnes -> shortcode, template natif, Divi et Gutenberg`.

Les consommateurs ne lisent ni les metas privees, ni le registre global des personnes. Ils recoivent un resultat Event Data deja filtre.

## P1 : autorisations de publication

Chaque association Personne-evenement dispose de trois autorisations independantes :

- `publish_email` ;
- `publish_phone` ;
- `publish_link`.

Ces autorisations sont privees par defaut. Seules les valeurs stockees `true`, `1` et `"1"` autorisent une publication. Une coordonnee absente ou invalide reste privee, meme si une autorisation incoherente est presente.

Les autorisations sont gerees dans la metabox Personnes de chaque evenement. Elles ne sont pas stockees dans le registre global `wp_seed_events_people` et ne se propagent pas d'un evenement a un autre.

La sauvegarde respecte les garanties suivantes :

- une sauvegarde sans interaction avec la metabox Personnes ne modifie aucune association ;
- un POST partiel ne modifie aucune association ;
- le retrait volontaire du dernier contact est pris en charge ;
- toute modification d'une coordonnee revoque uniquement l'autorisation associee ;
- aucune coordonnee historique n'est republiee automatiquement ;
- aucune migration ou activation globale n'est executee.

## Contrat Event Data public

Chaque element public de `people` contient uniquement les projections necessaires au rendu :

```text
{
  name: string,
  role_keys: string[],
  roles: string[],
  public_email: string,
  public_phone: string,
  public_url: string,
  email: string,
  phone: string,
  link: string
}
```

`email`, `phone` et `link` sont des alias historiques temporaires. Ils reprennent les valeurs publiques filtrees ; ils ne constituent jamais un acces aux coordonnees privees.

Le contrat public n'expose pas :

- `publish_email`, `publish_phone` ou `publish_link` ;
- `person_key` ;
- les coordonnees non autorisees ;
- les metas brutes de l'association.

Le nom et les roles restent publics. Les coordonnees sont normalisees avec les API WordPress et les regles du plugin : email valide, telephone au format accepte, URL absolue HTTP/HTTPS.

## P2 : renderer partage

Signature :

```php
wp_seed_events_render_public_event_people_section( $event, $options = array() )
```

Le premier argument est un resultat Event Data public deja charge. Le renderer ne recoit aucun ID d'evenement et n'accede ni au contexte WordPress, ni aux metas, ni a SQL, ni au stockage.

Options :

| Option | Defaut | Valeurs |
| --- | --- | --- |
| `title` | `Personnes` | texte libre, vide autorise |
| `heading_level` | `h2` | `h2` a `h6` |
| `role` | tous les roles | `organizer`, `speaker`, `registration_contact`, `information_contact` |
| `show_roles` | `true` | booleen |
| `show_email` | `true` | booleen |
| `show_phone` | `true` | booleen |
| `show_link` | `true` | booleen |
| `layout` | `list` | `list` ou `grid` |

Un role inconnu revient au filtre sans role. Les personnes conservent l'ordre metier fourni par Event Data. Les coordonnees sont affichees independamment les unes des autres.

L'option historique `details` reste acceptee comme alias de compatibilite : lorsqu'aucune option fine correspondante n'est fournie, elle pilote les roles et les trois coordonnees. Les nouvelles integrations doivent privilegier les options `show_*`.

Si aucune personne valide ne reste apres filtrage, le renderer retourne une chaine vide et ne produit aucun conteneur.

## HTML public

Le HTML metier est neutre et accessible :

- `section.wp-seed-event-section--people` ;
- titre facultatif `.wp-seed-event-people__title` ;
- liste `.wp-seed-event-people__list` ;
- element `.wp-seed-event-people__item` ;
- nom `.wp-seed-event-people__name` ;
- liste des roles `.wp-seed-event-people__roles` ;
- liste des coordonnees publiques `.wp-seed-event-people__contacts` ;
- liens email, telephone et URL avec libelles accessibles.

Lorsque le titre est vide, la section recoit un `aria-label`. Les valeurs sont echappees cote serveur. Aucun HTML prive, token brut ou wrapper vide n'est emis.

## P3-A : shortcode universel

```text
[wp_seed_event_people]
```

Exemple :

```text
[wp_seed_event_people role="speaker" details="no" title="Intervenants" heading_level="h3" layout="grid"]
```

Attributs :

| Attribut | Defaut |
| --- | --- |
| `id` | contexte courant |
| `role` | `all` |
| `details` | `yes` |
| `title` | `Contacts et intervenants` |
| `heading_level` | `h2` |
| `show_roles` | herite de `details` |
| `show_email` | herite de `details` |
| `show_phone` | herite de `details` |
| `show_link` | herite de `details` |
| `layout` | `list` |

Un `id` explicite invalide produit une sortie vide sans fallback. Sans `id`, le shortcode utilise le contexte public WP Seed Events puis un post courant compatible. Il charge Event Data une fois et delegue integralement le HTML au renderer partage.

## P3-B : template natif

Le template natif `templates/event-single.php` ne reconstruit plus la section Personnes. Il appelle le renderer partage avec le resultat Event Data deja disponible et le titre `Contacts et intervenants`.

Le template n'ajoute aucun fallback vers les donnees privees et ne produit aucune section lorsque l'evenement n'a pas de personne publique valide.

## P4 : module Divi 5

Le module Divi est enregistre sous l'identifiant :

```text
wp-seed-events/event-people
```

Il apparait dans le dossier `WP Seed Events` sous le libelle `WP Seed - Personnes de l'evenement`. Il reste un adaptateur fin : la resolution du contexte charge Event Data une fois, puis le rendu frontend est entierement delegue au renderer partage Personnes.

Le module propose huit reglages de contenu :

| Reglage | Defaut |
| --- | --- |
| Titre | `Contacts et intervenants` |
| Niveau du titre | `h2` |
| Role affiche | tous les roles |
| Afficher les roles | oui |
| Afficher les emails autorises | oui |
| Afficher les telephones autorises | oui |
| Afficher les liens autorises | oui |
| Mise en page | liste |

Les quatre filtres de role canoniques et les dispositions `list` et `grid` utilisent les memes options que le renderer. Le module ne reconstruit aucun HTML metier et n'utilise ni shortcode, ni ID d'evenement fixe, ni meta privee.

La resolution P4 active `strict_post` uniquement pour ce module :

- un contexte evenement explicite valide est utilise ;
- un ID explicite invalide ou un type de post incompatible retourne une sortie vide ;
- aucun fallback vers l'evenement public global n'est autorise lorsqu'un contexte post explicite a ete fourni ;
- le contexte public implicite reste disponible pour la fiche evenement, la page modele et le Loop Builder.

Le Visual Builder affiche un etat neutre sans coordonnee : `Apercu disponible sur le frontend dans un contexte evenement.` Le module est selectionnable, enregistrable et persistant dans Divi 5. Il n'ajoute aucune route REST Personnes ; son chargement et son enregistrement suivent le mecanisme de modules Divi deja utilise par Dates et Visuels.

En frontend, un contexte sans personne valide ne produit aucun wrapper. Les coordonnees peuvent etre masquees par les options du module, mais celui-ci ne peut jamais rendre une coordonnee absente de l'Event Data publique filtree.

## P5 : bloc Gutenberg

Le bloc Gutenberg est enregistre sous l'identifiant :

```text
wp-seed-events/event-people-block
```

Il utilise Block API v3, la categorie native `widgets` deja employee par les blocs Dates et Visuels, un rendu dynamique et `save: null`. Il reste un adaptateur fin : le callback PHP resout strictement le contexte, charge Event Data une seule fois et delegue tout le HTML au renderer partage Personnes.

Les huit attributs correspondent directement au contrat du renderer :

| Attribut | Defaut |
| --- | --- |
| `title` | `Contacts et intervenants` |
| `heading_level` | `h2` |
| `role` | `all` |
| `show_roles` | `true` |
| `show_email` | `true` |
| `show_phone` | `true` |
| `show_link` | `true` |
| `layout` | `list` |

L'inspecteur expose ces huit reglages en francais et rappelle que seules les coordonnees deja autorisees sur l'evenement peuvent etre affichees. Masquer une coordonnee ne modifie jamais son autorisation de publication.

L'apercu editeur utilise le composant WordPress standard `ServerSideRender` et la route native de rendu des blocs. Aucune route REST Personnes specifique n'est ajoutee. Le JavaScript ne contient aucun HTML metier et ne lit ni meta, ni association privee.

La resolution de contexte suit les memes garanties que les autres blocs WP Seed Events :

- un `postId` et un `postType` explicites compatibles utilisent l'evenement cible ;
- un contexte explicite invalide ou incompatible retourne une sortie vide sans fallback ;
- le fallback public est reserve a l'absence de contexte post explicite ;
- Query Loop transmet un contexte propre a chaque carte sans cache croise ;
- une page ordinaire sans contexte evenement affiche un etat editeur neutre et ne produit aucun wrapper en frontend.

Le bloc a ete valide dans l'editeur Gutenberg : insertion, huit reglages, aide de confidentialite, apercu neutre, enregistrement et persistance apres rechargement. La Query Loop native a ete validee avec plusieurs evenements distincts, dont un evenement sans personne qui reste strictement vide.
## Confidentialite

Le controle de confidentialite est applique avant le renderer, dans la projection Event Data publique. Masquer une coordonnee dans les options de rendu ne remplace jamais cette protection ; une coordonnee non autorisee n'entre pas dans le contrat public.

Le deploiement P3 n'active aucune autorisation. Les associations historiques conservent leurs donnees et restent privees tant qu'un editeur ne coche pas explicitement la case correspondante sur l'evenement.

## Etat deploye

P1, P2, P3-A, P3-B, P4 et P5 sont deployes sur le site de reference en version `0.2.0-alpha.1`.

La recette couvre :

- les quatre filtres de roles et le fallback d'un role invalide ;
- les options de details, titre, niveau de titre et disposition ;
- les contextes avec ID valide, ID invalide et sans contexte ;
- les evenements avec plusieurs personnes, une personne et aucune personne ;
- l'absence de coordonnee privee, `person_key`, meta brute ou shortcode non interprete ;
- l'absence de duplication entre shortcode et template natif ;
- les contextes fiche evenement, page modele, page ordinaire et Loop Builder du module Divi ;
- l'enregistrement, la persistance, les huit reglages et l'etat neutre du Visual Builder ;
- l'insertion, la persistance, les huit reglages et l'etat neutre du bloc Gutenberg ;
- le rendu serveur standard et la Query Loop Gutenberg sans contamination de contexte ;
- l'absence de route REST Personnes, d'ID fixe et de fallback pour un contexte explicite incompatible ;
- la neutralite des associations et autorisations de publication.

## Limites V1

- aucun champ Dynamic Data detaille supplementaire pour les coordonnees ;
- aucune personne principale ;
- aucune republication automatique des donnees historiques ;
- aucune migration ou nouvelle meta globale ;
- aucun changement de capacite : les permissions historiques d'edition restent conservees ;
- aucun apercu de coordonnees dans le Visual Builder ; l'apercu reste volontairement neutre ;
