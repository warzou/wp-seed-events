# Métadonnées défensives des renderers Divi

Les modules Divi 5 Dates, Visuels et Personnes acceptent que les métadonnées techniques `orderIndex`, `storeInstance` et `id` soient absentes du bloc transmis au renderer serveur.

Ces valeurs ne portent aucune donnée métier. Lorsqu'elles manquent, les adaptateurs utilisent des valeurs techniques neutres et continuent de résoudre l'événement depuis le contexte public prévu. Les métadonnées complètes conservent leur comportement antérieur.

Le correctif `fec9440` ferme les warnings PHP `Undefined array key` observés sur la route standard WordPress de rendu de bloc. Un appel authentifié incomplet renvoie désormais un JSON valide, sans warning, notice ni chemin serveur, pour les trois modules.

Cette protection reste limitée aux adaptateurs Divi : elle ne modifie ni l'Event Data API, ni les renderers métier partagés, ni le stockage, ni les données événementielles.
