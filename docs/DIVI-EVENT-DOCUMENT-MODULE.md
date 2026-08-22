# WPSEvents - Document

Le modele documentaire reste volontairement unitaire : un evenement reference au plus un PDF par `_wp_seed_event_flyer_pdf_id`.
Le nom technique du fichier reste porte par la piece jointe. Le nom editorial facultatif est stocke separement dans
`_wp_seed_event_document_display_name`.

L'Event Data API expose :

- `event_document_url` ;
- `event_document_filename` (nom technique) ;
- `event_document_display_name` (nom editorial, avec repli sur le nom de fichier nettoye).

Le module Divi `wp-seed-events/event-document` et le bloc Gutenberg
`wp-seed-events/event-document-block` consomment exclusivement ce document canonique. Ils n'accedent jamais directement
aux metas et ne rendent aucune image de galerie.

Les modes de contenu sont : texte uniquement, nom uniquement, texte et nom a la suite, texte et nom sur deux lignes.
Le builder reste proprietaire du titre et du conteneur externe.

Depuis la separation, le module Visuels ne rend plus de PDF. Les anciens attributs `show_document` et styles document
peuvent rester dans des contenus historiques, mais ils sont ignores sans migration destructive.
