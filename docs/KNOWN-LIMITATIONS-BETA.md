# Limites connues pour la bêta

Cette liste distingue les limites acceptées pour beta.2 des travaux nécessaires
avant 1.0 ou reportés après la version stable.

## Acceptées pour beta.2

- Les patterns Gutenberg sont insérés comme compositions non synchronisées par
  défaut. L'utilisateur choisit explicitement une composition synchronisée s'il
  souhaite partager ses modifications.
- Les modules Divi peuvent afficher un aperçu vide ou neutre hors d'un contexte
  événement réel. Le frontend d'une fiche ou d'une boucle reste l'autorité.
- Le contraste final dépend du thème et des styles choisis dans le builder.
- Spectra n'est ni requis ni recetté sur le site de référence.
- Les anciens slugs `brouillon-auto-*` ne sont pas migrés automatiquement.
- L'updater GitHub ne déclenche aucune mise à jour automatique en arrière-plan.
- L'initialisation lifecycle reste une action administrative visible et
  reprenable.
- Certains flyers peuvent avoir un texte alternatif vide uniquement lorsqu'ils
  sont décoratifs.
- Les performances des très gros catalogues doivent rester surveillées dans leur
  environnement réel, malgré l'indexation introduite en beta.2.

## À traiter avant 1.0

- Rejouer les parcours de migration et rollback sur chaque candidate 1.0.
- Vérifier le contraste et l'accessibilité sur les thèmes officiellement
  annoncés.

## Après 1.0

- Recette et éventuelle intégration Spectra lorsqu'un environnement volontaire
  et une API stable sont disponibles.
- Raffinements d'aperçu builder qui n'affectent pas le frontend.
- Expérience de synchronisation plus guidée pour les compositions réutilisées,
  sans créer un moteur de templates concurrent.

## Non-limitations

- WP Seed Content Kit n'est pas requis.
- Spectra n'est pas requis.
- La boîte native **Image mise en avant** n'est pas nécessaire pour les
  événements : le premier visuel reste projeté dans `_thumbnail_id`.
- Les shortcodes restent disponibles comme fallback, mais ne sont pas
  l'expérience builder principale.
