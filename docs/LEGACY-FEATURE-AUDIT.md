# Legacy Feature Audit

This inventory distinguishes compatibility storage from behavior that still belongs in the current runtime. It is intentionally conservative: only the integrated Dates calendar presentation is removed in this milestone.

| Feature | Old contract | New contract | Storage necessary? | Render necessary? | UI necessary? | Decision |
| --- | --- | --- | --- | --- | --- | --- |
| Dates integrated titles | Dates rendered its own heading | Builder heading module owns presentation | Yes | No | No | STORAGE ONLY / MIGRATED TO BUILDER |
| Dates occurrence calendar action | Dates rendered one action per occurrence | Canonical Event Data URL plus native builder button | Yes | No | No | STORAGE ONLY / REMOVE RUNTIME / MIGRATED TO EVENT DATA |
| Dates global calendar action | Dates rendered an all-occurrences action | `calendar_all_occurrences_url` plus native Divi/Gutenberg button | Yes | No | No | STORAGE ONLY / REMOVE RUNTIME / MIGRATED TO BUILDER |
| Dates calendar button styles | Dates styled integrated actions | Native builder button styles | Yes | No | No | STORAGE ONLY / MIGRATED TO BUILDER |
| Dates mode/scope aliases | Historical selection vocabulary | Canonical date selection normalization | Yes | Yes | No | KEEP |
| Dates `show_time` alias | Historical shortcode singular option | Canonical `show_times` | Yes | Yes | No | KEEP |
| People integrated title | People rendered its own heading | Builder heading module owns presentation | Yes | No | No | STORAGE ONLY / MIGRATED TO BUILDER |
| People registration/information roles | Two contact role aliases | Canonical event-scoped `contact` | Yes | Yes, as read aliases | No | KEEP / REMOVE NEW UI |
| People module call/SMS action | Module selected telephone action | Event association owns `phone_action` | Yes | Yes, only for historical instances | No | REMOVE NEW UI |
| People separate email/phone/site styles | Separate legacy style attributes | Shared composable contact styling | Yes | Yes | No | KEEP / REMOVE NEW UI |
| People `details` and role aliases | Broad historical toggles | Composable visibility and canonical role filters | Yes | Yes | No | KEEP / REMOVE NEW UI |
| Visuals integrated title | Visuals rendered its own heading | Builder heading module owns presentation | Yes | No | No | STORAGE ONLY / MIGRATED TO BUILDER |
| Visuals wrapper decorations | Every internal wrapper exposed decoration groups | Business-level Module/Item/Image/Caption/Document groups | Yes | Yes | No | KEEP / REMOVE NEW UI |
| Visuals lightbox | Opt-in native WordPress lightbox adapter | Same builder-agnostic native contract | Yes | Yes | Yes | KEEP |
| Visuals media ID aliases | Raw legacy media IDs | Canonical normalized media objects | Yes | Yes, as normalization aliases | No | KEEP |
| Share actions | Integrated share/copy/email behavior | Dedicated Share module remains the business component | Yes | Yes | Yes | KEEP |
| Share wrapper decoration aliases | Historical internal style attributes | Business-level composable styles | Yes | Yes | No | KEEP / REMOVE NEW UI |
| Collection presentation titles | Group labels rendered from occurrence data | Still meaningful grouping content | Yes | Yes | Yes | KEEP |
| Collection filter aliases | Historical type/promotion selectors | Canonical collection query arguments | Yes | Yes | No | KEEP / REMOVE NEW UI |
| Content shortcodes/providers | Historical shortcode and Dynamic Data entry points | Canonical Event Data registry | Yes | Yes for public compatibility | No new legacy UI | KEEP |
| Dynamic Data visible prefix | `WP Seed Events — ...` | `WPSEvents — ...` | No stored-value impact | Yes | Yes | KEEP, LABEL-ONLY CHANGE |

## Runtime residue review

- The lightbox adapter is not an abandoned custom overlay. It delegates to the native WordPress image lightbox and remains opt-in.
- Contact and media aliases remain read-only compatibility paths; they are not offered as new choices.
- Collection titles are business content, unlike the removed integrated module headings.
- Calendar URL generation and ICS downloads remain canonical data capabilities. Only Dates-owned presentation helpers, wrappers, and CSS are removed.
- Hidden legacy attributes remain in module and block metadata so saved content stays readable without post-content migration.

## Priority after this milestone

1. Keep compatibility aliases covered while ensuring they never return to new UI.
2. Review hidden wrapper decoration execution only when a real historical fixture proves it is obsolete.
3. Retain native lightbox, share behavior, and collection grouping; they have no demonstrated replacement conflict.
