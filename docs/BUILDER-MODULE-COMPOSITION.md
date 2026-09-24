# Composition des modules dans les builders

## Contrat de titre

Les nouvelles instances des modules Dates, Personnes et Visuels n'embarquent pas de titre de presentation. Le titre de section appartient au builder : module Titre ou Texte dans Divi, bloc Titre dans Gutenberg, ou equivalent dans un conteneur Spectra/Astra.

Les attributs historiques `title`, `show_title`, `heading_level` et `titleStyle` restent enregistres pour que les anciennes instances demeurent lisibles, mais ils sont desormais inertes. Aucun renderer Divi, Gutenberg ou public ne produit de titre, de wrapper ou de style a partir de ces valeurs. Aucune migration de contenu n'est necessaire.

## Inventaire

| Module | Titre integre historique | Cible DOM | Nouvelle UI | Decision |
| --- | --- | --- | --- | --- |
| Dates | `title`, `show_title`, `heading_level`, `titleStyle` | aucune | masque | Builder natif pour le titre ; donnees legacy stockees mais inertes |
| Personnes | `title`, `show_title`, `heading_level`, `titleStyle` | aucune | masque | Builder natif pour le titre ; donnees legacy stockees mais inertes |
| Visuels | `title`, `show_title`, `heading_level`, `titleStyle` | aucune | masque | Builder natif pour le titre ; donnees legacy stockees mais inertes |
| Partage | aucun titre de presentation | sans objet | inchange | Les libelles d'action restent des donnees metier |
| Collection d'occurrences | titres d'evenement, annee, theme et promotion | elements de collection | inchange | Ce sont des donnees metier, pas un titre de module |
| Contenu evenement | aucun titre de presentation | sans objet | inchange | Le contenu reste compose avec un titre natif separe |

## Composition recommandee

Dans Divi, placer un module Titre ou Texte et le module WP Seed Events dans le meme Groupe, Ligne ou Colonne. Dans Gutenberg, placer un bloc Titre et le bloc WP Seed Events dans un Groupe. La meme composition fonctionne dans un conteneur Spectra ou Astra sans dependance specifique du plugin.

Les controles propres aux donnees restent dans les modules : liste, date, horaire, personne, coordonnees, image, legende, document et titres metier de collection. Les decorations generiques du conteneur (fond, bordure, ombre, dimensions et espacement externe) appartiennent au conteneur natif du builder.

## Contrat des panneaux Style

Une nouvelle instance n'expose pas un groupe parce qu'un wrapper DOM existe. Les groupes visibles correspondent a des concepts metier ou a un sous-element que le builder ne peut pas cibler separement.

| Module | Groupes Style d'une nouvelle instance | Attributs historiques |
| --- | --- | --- |
| Dates | Module, Liste, Date, Horaire, Separateur Date / Horaire, Etat annule | donnees de titre inertes ; conteneurs et boutons calendrier encore interpretes, mais non exposes |
| Personnes | Module, Liste, Personne, Nom, Coordonnees, Separateur Nom / Coordonnees | donnees de titre inertes ; autres decorations internes encore interpretees, mais non exposees |
| Visuels | Module, Liste / Grille, Element, Image, Legende, Document / Lien document | donnees de titre inertes ; dimensions, espacements, bordures, ombres, filtres et wrappers internes encore interpretes, mais non exposes |

Le panneau Partage doit converger vers Module, Bouton Partager, Copier le lien et Email. Le panneau Collection doit converger vers les concepts Collection, Promotion, Annee, Theme, Occurrence, Titres, Libelles, Valeurs, Etat vide et Pagination. Ces deux simplifications restent hors du lot actuel afin de ne pas modifier leur contrat sans recette dediee.
