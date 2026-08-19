# Guide utilisateur bêta

Ce guide présente le parcours courant de WP Seed Events, de la création d'un
événement à son affichage dans Gutenberg, Divi ou un shortcode. Le plugin reste
autonome : Spectra n'est pas requis et WP Seed Content Kit n'est pas une
dépendance.

Ce guide accompagne `0.2.0-beta.9`. Les contrats Collections, Dates et
Personnes sont figés pour cette phase de stabilisation. L'updater officiel
détecte les releases GitHub autorisées, mais aucune mise à jour automatique en
arrière-plan n'est activée.

## Installer et activer

1. Sauvegardez les fichiers WordPress et la base de données.
2. Dans **Extensions > Ajouter une extension**, téléversez le ZIP officiel.
3. Activez **WP Seed Events**.
4. Ouvrez **Événements > Réglages** et vérifiez l'état de l'index lifecycle.
5. Si WordPress propose une initialisation, lancez-la jusqu'à l'état prêt avant
   d'utiliser les filtres de cycle de vie.

Une installation ou une mise à jour ne republie aucune coordonnée et ne
modifie pas automatiquement les anciens slugs.

## Créer un événement

Ouvrez **Événements > Ajouter un événement**. Renseignez d'abord le titre : il
sert de base au slug et à l'URL publique. Ajoutez ensuite la description, les
dates, le lieu, les personnes et les médias. La fiche n'est publique qu'après
avoir utilisé l'action WordPress **Publier**.

### Titre, contenu et slug

- Le titre est obligatoire pour publier.
- Le contenu décrit l'événement et peut servir d'extrait.
- Le slug est créé à la première sauvegarde à partir du titre.
- Un slug corrigé manuellement est conservé.
- Les anciens slugs de type `brouillon-auto-*` ne sont pas migrés
  automatiquement.

## Types et événement épinglé

Sélectionnez un ou plusieurs types, par exemple **Atelier**. Le type principal
participe à l'organisation et peut, selon les réglages du site, apparaître dans
l'URL.

L'option **Épingler cet événement** place l'événement avant les autres lorsque
la collection utilise la priorité des épinglés. Le tri par date reste appliqué
à l'intérieur de chaque groupe.

## Dates et occurrences

Dans **Quand a lieu mon événement ?**, ajoutez chaque occurrence avec sa date,
son horaire éventuel et sa date de fin. Les occurrences sont ordonnées par le
plugin. Après une modification, utilisez aussi **Publier** ou **Mettre à jour**
pour enregistrer la fiche WordPress.

Une occurrence peut être annulée sans être supprimée. Elle reste identifiable
comme annulée, mais les tris actifs et la prochaine date ignorent les
occurrences annulées.

Les composants Dates proposent notamment :

- **Prochaine date** ;
- **Première date** ou **Dernière date** ;
- **Toutes les prochaines dates** ;
- **Toutes les dates passées** ;
- **Toutes les dates** ;
- horaires, format court ou long et liens calendrier.

## Lieu

Choisissez un lieu existant ou créez-en un depuis la fiche. Vous pouvez retirer
le lieu de l'événement sans supprimer le lieu global. N'utilisez l'action de
suppression globale que si le lieu ne doit plus servir à aucun événement.

Le nom, l'adresse, le lien et les informations pratiques sont rendus uniquement
selon les composants choisis.

## Personnes et confidentialité

Ajoutez les personnes associées puis attribuez un ou plusieurs rôles :
organisateur, intervenant, contact d'inscription ou contact d'information.

Le nom et les rôles sont publics. Le téléphone, l'e-mail et le lien restent
privés par défaut. Chaque association personne-événement possède trois
autorisations indépendantes :

- publier le téléphone ;
- publier l'e-mail ;
- publier le lien.

Un bloc, un module ou un shortcode peut masquer une coordonnée déjà publique,
mais ne peut jamais contourner ces autorisations.

## Visuels et image principale

Dans **Visuels de communication**, ajoutez une ou plusieurs images :

1. le premier visuel devient le **Flyer recto** ;
2. il est aussi utilisé comme image principale WordPress de l'événement ;
3. les autres visuels conservent l'ordre affiché ;
4. **Définir comme flyer recto** place une autre image en première position ;
5. retirer le recto promeut automatiquement le visuel suivant ;
6. retirer le dernier visuel remet l'événement à zéro visuel.

La boîte WordPress native **Image mise en avant** est volontairement masquée
sur les événements pour éviter deux interfaces concurrentes. Le support
technique de l'image principale reste actif et la synchronisation est assurée
par le panneau Visuels. Retirer un visuel de l'événement ne supprime pas le
fichier de la médiathèque.

Les textes alternatifs et légendes viennent de la médiathèque. Un texte
alternatif vide convient uniquement à une image réellement décorative.

## Document PDF

Le panneau **Document complémentaire** accepte un seul PDF. Choisissez,
remplacez ou retirez le document, puis mettez à jour l'événement. Le retrait
de la fiche ne supprime pas le fichier de la médiathèque.

## Description courte facultative

Dans la boîte **Description**, le contenu principal reste la description complète de la fiche. Le champ **Description courte (facultative)** alimente les listes et cartes. S'il est vide, WP Seed Events utilise d'abord une coupure **Lire la suite** placée dans la description complète, puis génère un texte limité à 40 mots.

Les retours à la ligne saisis sont conservés. Vider le champ réactive immédiatement le fallback calculé ; aucun extrait automatique n'est stocké. L'ancien champ WordPress `post_excerpt` n'est pas affiché, migré ou modifié.

## Publier et vérifier la fiche

Avant publication :

1. vérifiez le titre et l'URL ;
2. contrôlez les dates, notamment les annulations ;
3. relisez les autorisations de coordonnées ;
4. vérifiez le flyer recto et le PDF ;
5. cliquez sur **Publier** ou **Mettre à jour** ;
6. ouvrez la fiche publique et, si nécessaire, son export ICS.

Un événement sans date reste publiable. Selon la collection choisie, il peut
être placé en fin de liste ou absent d'une sélection « à venir ».

## Collections Gutenberg

Ajoutez **WP Seed Events — Collection d'événements**. Le bouton
**Modifier le design** permet de partir d'une **Carte compacte** ou d'une
**Carte détaillée**.

Les réglages métier du bloc parent permettent de choisir :

- le type d'événement ;
- le statut : à venir, passé ou tous ;
- le traitement des événements épinglés ;
- le tri par **1re date de l'événement** ;
- l'ordre croissant ou décroissant ;
- le nombre d'éléments par page.

La carte reste une composition Gutenberg : sélectionnez ses blocs enfants pour
modifier leur ordre, leurs styles ou leur présence. Enregistrez une composition
non synchronisée pour obtenir une copie indépendante. Une composition
synchronisée répercute ses modifications partout où elle est utilisée ; elle
convient seulement si structure et réglages doivent rester identiques.

## Divi

Dans le Loop Builder Divi :

1. utilisez les événements comme source ;
2. filtrez le type et le statut ;
3. choisissez **1re date de l'événement** pour le tri métier ;
4. composez la carte avec les modules natifs et les modules
   **WP Seed — Dates**, **Visuels** et **Personnes**.

Les modules utilisent le contexte de l'item courant, sans identifiant
d'événement fixe. En dehors d'un contexte événement, leur aperçu peut afficher
un état vide neutre. Pour réutiliser un design Divi, employez la Library, les
presets ou les éléments globaux selon le niveau de synchronisation souhaité.

## Shortcodes de secours

Les shortcodes restent un fallback universel, notamment :

```text
[wp_seed_events type="atelier" status="upcoming"]
[wp_seed_event_dates]
[wp_seed_event_visuals]
[wp_seed_event_people]
```

Un identifiant explicite peut être utilisé sur une page ordinaire lorsque le
shortcode le prévoit. Dans une fiche, un modèle ou une boucle compatible,
préférez le contexte courant.

## ICS et partage

Chaque occurrence active peut proposer un lien calendrier. L'événement peut
aussi produire un export ICS global. Les modes Dates qui n'affichent qu'une
occurrence ne rendent que le lien de cette occurrence.

Le partage utilise l'URL canonique, le titre et le visuel principal normalisé.
Sa position finale dépend du thème et du mode de rendu du modèle.

## Cas particuliers

- **Sans date** : la fiche fonctionne, mais une collection « à venir » peut
  l'exclure.
- **Passé** : utilisez le statut passé ou toutes les dates.
- **Annulé** : une occurrence annulée peut être affichée explicitement, mais
  elle ne devient pas la prochaine occurrence active.
- **Sans visuel** : les composants Visuels restent vides sans wrapper parasite.
- **Sans coordonnée autorisée** : aucun téléphone, e-mail ou lien privé ne fuit.

## Dépannage

- Un changement d'administration n'apparaît pas : cliquez sur **Mettre à jour**
  puis rechargez.
- Une collection est vide : vérifiez type, statut, dates et pagination.
- Un module est vide dans l'éditeur : testez le frontend dans un vrai contexte
  événement.
- Une URL ancienne reste en `brouillon-auto-*` : corrigez le slug manuellement.
- Les filtres lifecycle sont indisponibles : terminez l'initialisation depuis
  les réglages.
- Avant toute restauration, consultez
  [MIGRATION-AND-ROLLBACK.md](MIGRATION-AND-ROLLBACK.md).

Les limites classées pour la bêta sont décrites dans
[KNOWN-LIMITATIONS-BETA.md](KNOWN-LIMITATIONS-BETA.md).
