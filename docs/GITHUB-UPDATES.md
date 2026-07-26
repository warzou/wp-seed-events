# Mises a jour officielles depuis GitHub

## Portee

WP Seed Events integre un updater borne au depot officiel `warzou/wp-seed-events`. Il alimente l'ecran Extensions et le dialogue « Voir les details ». L'installation reste une action manuelle WordPress : le plugin n'active aucune mise a jour automatique en arriere-plan.

## Canal

- Les releases stables sont admissibles par defaut.
- Les prereleases sont ignorees par defaut.
- Un administrateur autorise peut activer le canal de prerelease dans **WP Seed Events > Mises a jour** ; en multisite, ce reglage appartient a l'administration reseau.
- Seule une version strictement superieure a la version installee est proposee. Aucun downgrade n'est selectionne.
- Changer le canal purge le cache des releases et le transient WordPress des mises a jour.

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

Les erreurs reseau, JSON invalide, 403, 404, 429, 500, asset absent ou invalide n'affectent ni le site ni les autres plugins : aucune mise a jour WP Seed Events n'est alors injectee. Un nouvel essai reste possible apres purge ou expiration du cache.

## Hooks WordPress

L'integration utilise uniquement les hooks publics suivants :

- `pre_set_site_transient_update_plugins` ;
- `plugins_api` ;
- `upgrader_pre_download` ;
- `upgrader_source_selection` ;
- `upgrader_process_complete` ;
- `admin_menu`, `network_admin_menu` et `admin_post_*` pour le canal.

Le header `Update URI` du plugin evite toute collision avec une extension homonyme du repertoire WordPress.org.

## Multisite et permissions

L'option de canal est une option de site reseau. Sur une installation simple, elle exige `manage_options`. En multisite, sa modification exige `manage_network_plugins` et se fait dans l'administration reseau. L'updater ne modifie pas les politiques WordPress d'activation par site ou reseau et ne touche jamais au transient d'un autre plugin.

## Sauvegarde et rollback

Avant toute mise a jour manuelle :

1. sauvegarder la base WordPress ;
2. archiver exactement le dossier plugin actif et verifier son SHA-256 ;
3. relever la version et l'etat de l'index lifecycle ;
4. lancer la mise a jour depuis WordPress ;
5. recetter une fiche, une collection, un ICS et les builders utilises.

L'updater ne promet pas de rollback proprietaire. En cas d'echec, restaurer atomiquement le ZIP ou le dossier exact de la version precedente, puis verifier l'index lifecycle. Les donnees metier restent dans la base et ne sont pas contenues dans le ZIP.

## Disponibilite

L'updater est livre a partir de `0.2.0-beta.2`. Cette prerelease n'est proposee qu'aux installations ayant explicitement active le canal de prerelease. Les releases stables restent le canal par defaut.