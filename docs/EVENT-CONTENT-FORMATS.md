# Event rich content contract

The canonical full event description is the authored `post_content` stored by
the `wp_seed_event` post. Event Data exposes that value unchanged as
`description`; it is not a dedicated meta field and it is not a summary.

Public HTML consumers call `wp_seed_events_render_rich_content()` exactly once.
The helper runs the native WordPress `the_content` pipeline so paragraphs,
shortcodes, embeds, and native blocks can render, then applies `wp_kses_post()`
to the result. Text-only consumers must use `excerpt` or another text field.

| Value | Source | Event Data | Registry format | Consumer contract |
| --- | --- | --- | --- | --- |
| Full description | `post_content` | authored value | `rich_html` | WordPress content pipeline once |
| Effective short description | dedicated resolver | plain multiline text | `multiline_text` | escape as text, preserve lines |
| Practical information | event/place model | plain multiline text | `multiline_text` | escape as text, preserve lines |

`WPSEvents — Contenu` (`wp-seed-events/event-content-block`) is a dynamic
Gutenberg block. It inherits `postId`, `postType`, and `queryId`; a published
event context renders the canonical description, while a normal page or invalid
context renders nothing. This supports a singular event and an event item in a
native Query Loop when WordPress supplies that item context. The block does not
persist an event ID.

Public rendering accepts published events only. The authenticated editor preview
may render a draft event after its REST permission check succeeds.

The editor preview uses an authenticated REST request. The frontend uses PHP
server rendering and requires no public JavaScript or stylesheet. The block and
the public rich-content contract contain no Divi or ET Builder dependency.

The block refuses to render when the canonical event content contains the block
itself. This prevents recursion and duplication; authors should place the block
in a template or Query Loop rather than inside the event description it reads.
