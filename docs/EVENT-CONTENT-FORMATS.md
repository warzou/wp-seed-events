# Event content formats

The Dynamic Data registry is the source of truth for a public field's consumer format.

| Field | Source | Authoring | Event Data / REST | Format | Rendering |
| --- | --- | --- | --- | --- | --- |
| `title` | `post_title` | plain input | string | `plain_text` | escaped text |
| `types`, `status` | canonical classifications | structured controls | labels | `plain_text` | escaped text |
| date/time fields | Occurrences API | structured controls | formatted string | `plain_text` | escaped text |
| `place`, `place_address` | Place API | plain inputs | string | `plain_text` | escaped text |
| `contact` | People API | structured associations | newline-separated public projection | `multiline_text` | escaped text with preserved lines |
| `description` | `post_content` | `wp_editor` rich content | authored HTML | `rich_html` | one WordPress `the_content` pass, then `wp_kses_post` |
| `short_description`, `short_description_effective`, `excerpt` | dedicated meta / resolver | textarea | text with `\n` | `multiline_text` | escaped text with preserved lines |
| `practical_info` | event/place details | textarea | text with `\n` | `multiline_text` | escaped text with preserved lines |
| document filename | Media API | attachment | string | `plain_text` | escaped text |
| public links | canonical URLs | URL inputs | absolute URL | `url` | URL consumer escaping |
| communication visual | Media API | media selector | normalized media object | `image` | image consumer |

`description` is the only event field authored with rich HTML. The native Divi Text module can consume its Dynamic Data source because the module content attribute supports HTML. Gutenberg text-only attributes are not suitable for a block-level document, so `WP Seed - Contenu de l'evenement` is the canonical Gutenberg consumer.

The historical Dynamic Data source remains available. Consumers must not run `the_content` a second time, flatten rich HTML, or interpret multiline text as HTML.

## Consumer compatibility

The typed registry is builder-agnostic. Core Event Data contains no Divi, Astra,
Spectra, or other builder branch. Builder-specific adaptation stays in its
integration layer.

| Consumer | Plain text | Multiline text | Rich HTML |
| --- | --- | --- | --- |
| Gutenberg core | text-capable block/binding | text-capable block preserving line breaks | `WP Seed - Contenu de l'evenement` |
| Spectra or another Gutenberg library | text-capable field/binding | only where the target preserves line breaks | official WP Seed content block or an explicitly HTML-capable consumer |
| Divi | text-capable Dynamic Data target | text-capable Dynamic Data target preserving lines | HTML-capable Text module target |
| Standard/Astra frontend | escaped text | escaped text with preserved lines | sanitized WordPress HTML |

Do not bind `rich_html` to a text-only attribute. Theme typography may style the
result, but it must not change the field format or flatten the document.

## Public list inventory

| List | Renderer and classes | Kind | Default | Consumers |
| --- | --- | --- | --- | --- |
| Occurrences | `rendering.php`; `.wp-seed-event-dates > .wp-seed-event-date` | main, stylable | shared module setting; `none` when unset | frontend, Divi, Gutenberg |
| Communication visuals/documents | `rendering.php`; `.wp-seed-event-visuals__list > .wp-seed-event-visuals__item` | main, stylable | shared module setting; `none` when unset | frontend, Divi, Gutenberg |
| People | `rendering.php`; `.wp-seed-event-people__list > .wp-seed-event-people__item` | main, stylable | shared module setting; `none` when unset | frontend, Divi, Gutenberg |
| Person roles | `rendering.php`; `.wp-seed-event-people__roles > .wp-seed-event-people__role` | structural | always no marker and no implicit indent | frontend, Divi, Gutenberg, standard themes |
| Public coordinates | `rendering.php`; `.wp-seed-event-people__contacts > .wp-seed-event-people__contact` | structural | always no marker and no implicit indent | frontend, Divi, Gutenberg, standard themes |
| Event types | `rendering.php`; escaped inline labels separated by a bullet character | not a list element | n/a | frontend/shortcode |
| Rich description lists | authored `<ul>`, `<ol>`, and `<li>` inside `.wp-seed-events-rich-content` | editorial | theme/WordPress list semantics preserved | Gutenberg, Divi HTML consumer, standard themes |

Admin-only autocomplete and lifecycle lists are interface controls and are not
part of the public event rendering contract.
