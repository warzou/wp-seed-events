# Collections d'evenements avec Spectra

## Etat du support

WP Seed Events ne charge actuellement aucun adaptateur Spectra. Spectra et Spectra Pro sont absents de l'environnement de recette, et aucune version minimale n'est donc declaree compatible.

Le contrat canonique de collection reste independant des builders dans `includes/public/collections.php`. Il fournit une selection ordonnee, un total et une pagination pour les parametres `type`, `status`, `pinned` et `order`. La fonction `wp_seed_events_apply_collection_to_query_args()` permet a un adaptateur borne de reutiliser cette selection sans recopier les regles metier.

## Pourquoi aucun filtre runtime n'est branche

Le filtre public documente `spectra_loop_builder_main_query_args` agit sur les arguments de requete du Loop Builder. Sans marqueur persistant et officiellement documente permettant d'identifier une boucle WP Seed Events particuliere, le brancher globalement pourrait modifier des boucles Spectra sans rapport avec les evenements.

Le Loop Builder est en outre une fonctionnalite Spectra Pro. Spectra Free seul ne fournit pas de boucle exploitable pour cette integration.

WP Seed Events reste donc silencieux lorsque Spectra est absent, lorsque seule l'edition gratuite est presente, ou lorsqu'aucun bloc de boucle supporte n'est enregistre.

## Conditions d'une integration future

Une recette volontaire avec Spectra Pro devra confirmer, sur une version precise :

- le nom reel du bloc de boucle et sa disponibilite dans l'editeur ;
- un marqueur stable par instance pour isoler plusieurs boucles ;
- le hook officiel utilise en frontend ;
- le chemin officiel de l'apercu editeur ;
- la transmission de `type`, `status`, `pinned`, `order` et du nombre d'elements par page ;
- le maintien de l'ordre canonique des IDs et de la pagination ;
- la coexistence avec les boucles Spectra ordinaires.

Le protocole de recette devra couvrir au minimum : Spectra absent, Spectra Free seul, Spectra Pro actif, deux boucles WP Seed Events aux reglages differents, une boucle Spectra ordinaire, les deux pages d'une pagination 2 x 2 et la parite entre apercu editeur et frontend. Les controles devront verifier les IDs, leur ordre, les contextes des blocs enfants et l'absence de doublon ou de fuite entre boucles.

Si ces garanties sont obtenues, l'adaptateur devra etre charge conditionnellement apres detection des classes ou blocs officiels. Il appellera uniquement le contrat canonique puis rendra la main a Spectra pour la composition visuelle. Aucun acces aux metas privees et aucune duplication des regles de dates ne seront acceptes.

## Statut produit

- Gutenberg Core Query Loop : integration disponible et recettable.
- Spectra Free : aucun Loop Builder compatible a adapter dans l'environnement audite.
- Spectra Pro : contrat central, documentation et harness prets ; installation et recette explicites requises apres une activation volontaire, avant toute annonce de compatibilite et toute integration runtime conditionnelle.

## Composition avec Spectra gratuit

Spectra gratuit peut servir d'outil de mise en page autour des blocs Core et
WP Seed Events lorsqu'il est deja installe. Cette composition reste celle de
Gutenberg : WP Seed Events ne charge aucun adaptateur Spectra et n'en depend
pas.

Les patterns fournis par WP Seed Events utilisent exclusivement des blocs Core
et WP Seed Events. Ils restent donc disponibles lorsque Spectra est absent.
Aucun plugin Spectra n'est installe ou active automatiquement.

Un pattern qui reference un bloc fourni par une extension absente peut
apparaitre comme bloc manquant dans l'editeur WordPress. Pour cette raison,
aucun bloc Spectra n'est inclus dans les presentations par defaut de WP Seed
Events. L'utilisateur peut remplacer ou entourer les blocs de la carte avec
Spectra apres insertion, sous sa propre responsabilite editoriale.

## Compatibilite alpha.2

Spectra est facultatif et non requis par WP Seed Events. Il n'est pas installe sur le site de reference. Les patterns officiels n'incluent aucun bloc Spectra et cette alpha ne revendique aucune compatibilite runtime avancee avec une boucle Spectra.

Gutenberg Core, les blocs WP Seed Events et le contrat canonique restent utilisables sans Spectra. Toute integration runtime future devra faire l'objet d'une installation volontaire, d'un audit des hooks officiels et d'une recette distincte.
