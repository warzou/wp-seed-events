# Mises a jour officielles depuis GitHub

## Portee

WP Seed Events integre un updater borne au depot officiel `warzou/wp-seed-events`. Il alimente l'ecran Extensions, le dialogue natif « Afficher les details », le lien « Verifier les mises a jour » et l'action WordPress « Mettre a jour maintenant » lorsqu'une release admissible existe. Le plugin n'active aucune mise a jour automatique en arriere-plan.

## Canal

- Les releases stables sont admissibles par defaut.
- Une installation alpha, beta ou RC suit automatiquement les prereleases officielles plus recentes afin de pouvoir progresser dans le meme canal.
- Une installation stable ignore les prereleases, sauf accord explicite dans **WP Seed Events > Mises a jour** ; en multisite, ce reglage appartient a l'administration reseau.
- Les brouillons GitHub sont toujours ignores.
- Les versions sont normalisees puis comparees avec `version_compare` ; l'ordre chronologique GitHub n'est jamais utilise comme ordre de version.
- Seule une version strictement superieure a la version installee est proposee. Aucun downgrade n'est selectionne.
- Changer le canal purge uniquement le cache Events et son entree dans l'etat WordPress des mises a jour.

## Interface native WordPress

La ligne **WP Seed Events** dans Extensions expose :

- **Afficher les details**, qui ouvre la modale WordPress avec nom, version, auteur, compatibilite, description, changelog, date et lien de release ;
- **Verifier les mises a jour**, visible uniquement avec la capacite `update_plugins` et protege par nonce ;
- **Mettre a jour maintenant**, fourni par WordPress lorsqu'une version superieure admissible est injectee dans le transient standard.

La verification manuelle invalide seulement le cache `wp_seed_events_github_releases` et les entrees `response`/`no_update` de `wp-seed-events/wp-seed-events.php`. Elle conserve l'etat des autres extensions et affiche un resultat distinct : a jour, mise a jour disponible, release incomplete ou verification impossible.

La fiche de details reste disponible avec les metadonnees locales si GitHub est temporairement indisponible. Aucun paquet n'est alors propose.

## Contrat d'une release installable

Une release future doit etre publiee dans le depot officiel avec exactement :

```text
wp-seed-events-<version>.zip
wp-seed-events-<version>.zip.sha256
```

Le tag doit etre `v<version>` ou `<version>` avec une version SemVer valide. La release ne doit pas etre un brouillon. Le ZIP et son checksum doivent etre des assets uniques de cette release, servis par HTTPS depuis le chemin officiel GitHub du depot. Les archives source automatiques de GitHub ne sont jamais utilisees.

Le fichier `.sha256` contient les 64 caracteres hexadecimaux, seuls ou suivis du nom exact du ZIP. Cette source de checksum est obligatoire : une release sans checksum, avec assets ambigus ou avec un nom different n'est pas proposee.

## Verifications avant installation

Avant de transmettre le paquet a l'upgrader WordPress, l'integration verifie :

- reponse HTTP et type de contenu acceptable ;
- taille non nulle et, si fournie, taille identique aux metadonnees GitHub ;
- SHA-256 identique a l'asset checksum ;
- archive ZIP lisible ;
- chemins sans antislash, chemin absolu ni traversal ;
- racine unique `wp-seed-events/` ;
- fichier principal present ;
- version interne strictement identique au tag.

L'extension PHP `ZipArchive` est requise pour cette validation. Si elle manque, l'installation echoue proprement avant remplacement du plugin.

## Cache et reseau

Les metadonnees GitHub sont demandees avec un timeout de 5 secondes, trois redirections maximum, un `User-Agent` identifiant le plugin sans URL du site, puis mises en cache six heures. Les telechargements utilisent un timeout de 30 secondes et un fichier temporaire supprime en cas d'echec.

Les erreurs reseau, JSON invalide, 403, 404, 429, 500, version invalide, asset absent, checksum absent ou divergent et archive invalide n'affectent ni le site ni les autres plugins. Aucune erreur n'est mise en cache durablement. Une verification manuelle ou l'expiration du cache permet un nouvel essai.

## Hooks WordPress

L'integration utilise uniquement les hooks publics suivants :

- `pre_set_site_transient_update_plugins` ;
- `plugins_api` ;
- `plugin_row_meta` ;
- `admin_init` et `all_admin_notices` pour la verification manuelle ;
- `upgrader_pre_download` ;
- `upgrader_source_selection` ;
- `upgrader_process_complete` ;
- `admin_menu`, `network_admin_menu` et `admin_post_*` pour le canal.

Le header `Update URI` du plugin evite toute collision avec une extension homonyme du repertoire WordPress.org.

## Multisite et permissions

L'option de canal est une option de site reseau. Sur une installation simple, elle exige `manage_options`. En multisite, sa modification exige `manage_network_plugins` et se fait dans l'administration reseau. La verification manuelle exige `update_plugins`. L'updater ne modifie pas les politiques WordPress d'activation par site ou reseau et ne touche jamais aux entrees de mise a jour d'un autre plugin.

## Sauvegarde et rollback

Avant toute mise a jour manuelle :

1. sauvegarder la base WordPress ;
2. archiver exactement le dossier plugin actif et verifier son SHA-256 ;
3. relever la version et l'etat de l'index lifecycle ;
4. lancer la mise a jour depuis WordPress ;
5. recetter une fiche, une collection, un ICS et les builders utilises.

L'updater ne promet pas de rollback proprietaire. En cas d'echec, restaurer atomiquement le ZIP ou le dossier exact de la version precedente, puis verifier l'index lifecycle. Les donnees metier restent dans la base et ne sont pas contenues dans le ZIP.

## Disponibilite

L'updater securise est livre a partir de `0.2.0-beta.2`. Une installation en prerelease suit les prereleases officielles plus recentes ; une installation stable reste sur le canal stable sans opt-in. La transition native beta.2 vers beta.3 constitue la recette officielle de bout en bout apres publication des assets beta.3.

## Validation officielle beta.3

0.2.0-beta.3 est la premiere release utilisee pour valider de bout en bout la transition native depuis 0.2.0-beta.2. Les assets officiels restent le ZIP runtime-only et son fichier .sha256 ; les archives source GitHub ne sont jamais installees. Le controle couvre la proposition dans Extensions, la fiche de details, le checksum, la racine finale wp-seed-events/, l'activation et la correspondance stricte du runtime.
## Validation corrective beta.5

La transition native de `0.2.0-beta.4` vers `0.2.0-beta.5` valide le meme contrat avec une release corrective : prerelease officielle, ZIP runtime-only, checksum obligatoire, details natifs, installation dans `wp-seed-events/`, plugin actif et runtime strictement identique a l'asset GitHub.

## Validation corrective beta.6

La release `0.2.0-beta.6` conserve le contrat de mise a jour natif : prerelease officielle, ZIP runtime-only, checksum obligatoire, details natifs et installation finale dans `wp-seed-events/`. Elle doit etre proposee aux installations beta.5 ayant active le canal des prereleases, puis le runtime installe doit correspondre strictement a l'asset GitHub.

## Release 0.2.0-beta.7

La release `0.2.0-beta.7` conserve le contrat de mise à jour natif : prerelease
officielle, ZIP runtime-only et fichier `.sha256` obligatoire. Elle rend les
taxonomies Events détectables par les constructeurs sans créer d'archive
publique ni modifier le stockage métier.
