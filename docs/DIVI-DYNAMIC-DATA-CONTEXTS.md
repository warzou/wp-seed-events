# Divi Dynamic Data Contexts

WP Seed Events exposes one canonical Event Data registry through two Divi source IDs per field. The IDs are intentionally retained because saved Divi bindings refer to them directly.

| Current UI label | Provider | Key / slug | Resolution context | Expected usage |
| --- | --- | --- | --- | --- |
| WPSEvents — Page — Titre | Text / `wp_seed_events_title` | `title` | BOTH (legacy) | Event page; historical loops remain compatible |
| WPSEvents — Titre | Text / `loop_wp_seed_events_title` | `title` | LOOP ITEM | New event loops |
| WPSEvents — Page — Types | Text / `wp_seed_events_types` | `types` | BOTH (legacy) | Event page; historical loops remain compatible |
| WPSEvents — Types | Text / `loop_wp_seed_events_types` | `types` | LOOP ITEM | New event loops |
| WPSEvents — Page — Statut | Text / `wp_seed_events_status` | `status` | BOTH (legacy) | Event page; historical loops remain compatible |
| WPSEvents — Statut | Text / `loop_wp_seed_events_status` | `status` | LOOP ITEM | New event loops |
| WPSEvents — Page — Prochaine date | Text / `wp_seed_events_next_date` | `next_date` | BOTH (legacy) | Event page; historical loops remain compatible |
| WPSEvents — Prochaine date | Text / `loop_wp_seed_events_next_date` | `next_date` | LOOP ITEM | New event loops |
| WPSEvents — Page — Prochaine heure | Text / `wp_seed_events_next_time` | `next_time` | BOTH (legacy) | Event page; historical loops remain compatible |
| WPSEvents — Prochaine heure | Text / `loop_wp_seed_events_next_time` | `next_time` | LOOP ITEM | New event loops |
| WPSEvents — Page — Date affichée | Text / `wp_seed_events_display_date` | `display_date` | BOTH (legacy) | Event page; historical loops remain compatible |
| WPSEvents — Date affichée | Text / `loop_wp_seed_events_display_date` | `display_date` | LOOP ITEM | New event loops |
| WPSEvents — Page — Heure affichée | Text / `wp_seed_events_display_time` | `display_time` | BOTH (legacy) | Event page; historical loops remain compatible |
| WPSEvents — Heure affichée | Text / `loop_wp_seed_events_display_time` | `display_time` | LOOP ITEM | New event loops |
| WPSEvents — Page — Lieu | Text / `wp_seed_events_place` | `place` | BOTH (legacy) | Event page; historical loops remain compatible |
| WPSEvents — Lieu | Text / `loop_wp_seed_events_place` | `place` | LOOP ITEM | New event loops |
| WPSEvents — Page — Adresse du lieu | Text / `wp_seed_events_place_address` | `place_address` | BOTH (legacy) | Event page; historical loops remain compatible |
| WPSEvents — Adresse du lieu | Text / `loop_wp_seed_events_place_address` | `place_address` | LOOP ITEM | New event loops |
| WPSEvents — Page — Contact | Text / `wp_seed_events_contact` | `contact` | BOTH (legacy) | Event page; historical loops remain compatible |
| WPSEvents — Contact | Text / `loop_wp_seed_events_contact` | `contact` | LOOP ITEM | New event loops |
| WPSEvents — Page — Description complète | Text / `wp_seed_events_description` | `description` | BOTH (legacy) | Event page; historical loops remain compatible |
| WPSEvents — Description complète | Text / `loop_wp_seed_events_description` | `description` | LOOP ITEM | New event loops |
| WPSEvents — Page — Description courte effective | Text / `wp_seed_events_excerpt` | `excerpt` | BOTH (legacy) | Event page; historical loops remain compatible |
| WPSEvents — Description courte effective | Text / `loop_wp_seed_events_excerpt` | `excerpt` | LOOP ITEM | New event loops |
| WPSEvents — Page — Informations pratiques | Text / `wp_seed_events_practical_info` | `practical_info` | BOTH (legacy) | Event page; historical loops remain compatible |
| WPSEvents — Informations pratiques | Text / `loop_wp_seed_events_practical_info` | `practical_info` | LOOP ITEM | New event loops |
| WPSEvents — Page — Nom du document | Text / `wp_seed_events_event_document_filename` | `event_document_filename` | BOTH (legacy) | Event page; historical loops remain compatible |
| WPSEvents — Nom du document | Text / `loop_wp_seed_events_event_document_filename` | `event_document_filename` | LOOP ITEM | New event loops |
| WPSEvents — Page — URL de l’événement | URL / `wp_seed_events_url` | `url` | BOTH (legacy) | Event page; historical loops remain compatible |
| WPSEvents — URL de l’événement | URL / `loop_wp_seed_events_url` | `url` | LOOP ITEM | New event loops |
| WPSEvents — Page — URL du lieu | URL / `wp_seed_events_place_url` | `place_url` | BOTH (legacy) | Event page; historical loops remain compatible |
| WPSEvents — URL du lieu | URL / `loop_wp_seed_events_place_url` | `place_url` | LOOP ITEM | New event loops |
| WPSEvents — Page — URL du document | URL / `wp_seed_events_event_document_url` | `event_document_url` | BOTH (legacy) | Event page; historical loops remain compatible |
| WPSEvents — URL du document | URL / `loop_wp_seed_events_event_document_url` | `event_document_url` | LOOP ITEM | New event loops |
| WPSEvents — Page — Ajouter toutes les dates au calendrier | URL / `wp_seed_events_calendar_all_occurrences_url` | `calendar_all_occurrences_url` | BOTH (legacy) | Native button on an event page; historical loops remain compatible |
| WPSEvents — Ajouter toutes les dates au calendrier | URL / `loop_wp_seed_events_calendar_all_occurrences_url` | `calendar_all_occurrences_url` | LOOP ITEM | Native button in a new event loop |
| WPSEvents — Page — Visuel de communication | Image / `wp_seed_events_communication_visual` | `communication_visual` | BOTH (legacy) | Event page; historical loops remain compatible |
| WPSEvents — Visuel de communication | Image / `loop_wp_seed_events_communication_visual` | `communication_visual` | LOOP ITEM | New event loops |

## Resolution contract

- `loop_wp_seed_events_*` accepts only an explicit public event `loop_id`. It never falls back to the queried event, holder page, global post, or prior loop item.
- `wp_seed_events_*` resolves the current event page and keeps explicit `loop_id` support solely for saved historical loops.
- An ordinary page, incompatible loop item, draft event, or missing context returns an empty value.
- Divi source IDs, registry keys, stored variable expressions, and Gutenberg binding IDs are unchanged.
- Gutenberg keeps one context-aware `wp-seed-events/event-field` source. WordPress supplies the current `postId` for both a single event and a Query Loop item, so it is not a duplicate Divi series.

The DEV homepage uses both generations: historical text bindings use `wp_seed_events_title`, `wp_seed_events_excerpt`, and `wp_seed_events_place`; its image and event URL already use the `loop_` aliases. This mixed storage is supported without rewriting page content.
