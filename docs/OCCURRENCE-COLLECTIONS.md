# Occurrence Collections

## Public contract

Occurrence collections are additive to the existing event collections. Event collections return one item per event; occurrence collections return one item per dated occurrence.

```php
$flat = wp_seed_events_query_occurrence_collection( $args );
$grouped = wp_seed_events_query_grouped_occurrence_collection( $args );
```

Both functions are read-only. They expose published events only and never expose lifecycle options, SQL table names, locks, cursors, server paths or projection timestamps.

## Flat collection

`wp_seed_events_query_occurrence_collection()` accepts:

| Argument | Default | Contract |
| --- | --- | --- |
| `promotion` | empty | Promotion ID or slug. |
| `promotion_id` | `0` | Positive Promotion ID. |
| `promotion_slug` | empty | Promotion slug. |
| `parcours_year` | empty | Integer from 1 to 4. |
| `event_id` | empty | Positive event/theme ID. |
| `type` | empty | Existing public event type slug or label. |
| `status` | `upcoming` | `upcoming`, `past` or `all`, based on the occurrence start date in the WordPress timezone. |
| `pinned` | `all` | `all` or `only`. Pinned events remain first, then occurrence dates determine order. |
| `include_cancelled` | `false` | Include cancelled occurrences when true. |
| `from` | empty | Inclusive overlap lower bound. |
| `to` | empty | Inclusive overlap upper bound. |
| `order` | `upcoming` | `upcoming`, `chronological` or `chronological_desc`. |
| `page` | `1` | Positive page number. |
| `per_page` | `20` | From 1 to 100. |

Date bounds accept `YYYY-MM-DD` or `YYYY-MM-DD HH:MM`. An occurrence matches when its normalized end is not before `from` and its normalized start is not after `to`.

`promotion`, `promotion_id` and `promotion_slug` are useful aliases for the same selector. If several are supplied, they must identify the same Promotion. Archived Promotions remain readable for historical collections.

The response contains `items`, `page`, `per_page`, `total_items`, `total_pages`, `has_previous`, `has_next` and normalized public `args`.

Each item contains:

- `event_id`, `event_title`, `event_slug`, `event_type`, `event_status`, `is_pinned`;
- `occurrence_uid`, `occurrence_index`, `start`, `end`, `start_sort`, `end_sort`, `is_cancelled`;
- `promotion_id`, the normalized public `promotion`, `parcours_year` and `parcours_year_label`.

## Deterministic order

Flat collections order pinned events first, then `start_sort`, `end_sort`, `event_id` and `occurrence_uid`. Descending order reverses the two date comparisons but keeps pinned events first and stable identity tie-breakers ascending.

`upcoming` and `chronological` both use ascending chronological order. `upcoming` is the user-facing default; `status` controls whether future, past or all occurrences are selected.

## Grouped collection

`wp_seed_events_query_grouped_occurrence_collection()` returns:

```text
promotions[]
  promotion
  years[]
    parcours_year
    parcours_year_label
    themes[]
      event
      occurrences[]
```

Every level also exposes `count`, `first_start_sort` and `last_end_sort`. Empty groups, occurrences without a Promotion and events without matching occurrences are omitted. The same event/theme may appear in several Promotion/year paths.

Grouped V1 deliberately has no ambiguous nested pagination. It accepts a global `limit` from 1 to 500, default 200, and reports `total_items`, `returned_items` and `is_limited`. Passing `page` or `per_page` is an error. Its only order is `canonical_path`:

1. Promotions: manual `order`, `start_year`, name, ID;
2. parcours years: 1 through 4;
3. themes: first matching occurrence, pinned state, title, ID;
4. occurrences: start, end, event ID, occurrence UID.

## Index and exact fallback

When lifecycle v3 is ready and the projection table exists, filtering, counting, deterministic ordering and pagination happen in prepared SQL. Only events on the selected page are hydrated.

When lifecycle v3 is not ready, its table is missing or an indexed query fails, the API rebuilds exact projection rows from the canonical occurrence storage and applies the same filters, order and pagination in PHP. The public response is identical. No frontend request starts a repair and no new persistent cache is added.

## REST

Read-only public routes:

```text
GET /wp-json/wp-seed-events/v1/occurrences
GET /wp-json/wp-seed-events/v1/occurrences/grouped
```

The flat route exposes the same filters and pagination, plus `X-WP-Total` and `X-WP-TotalPages`. The grouped route exposes the same business filters with `order=canonical_path` and the bounded global `limit`. REST controllers delegate to the public PHP functions and never read business meta directly.

## Stable errors

Invalid input returns `WP_Error`; an empty result is successful. Stable code families cover invalid Promotion, conflicting Promotion selectors, parcours year, event ID, type, status, pinned mode, booleans, order, page, per-page value, limit, dates and incoherent combinations.

## Examples

```php
// All upcoming occurrences, every event type.
$upcoming = wp_seed_events_query_occurrence_collection();

// Promotion 2026, by slug.
$promotion = wp_seed_events_query_occurrence_collection(
    array( 'promotion_slug' => 'promotion-2026' )
);

// First parcours year.
$first_year = wp_seed_events_query_occurrence_collection(
    array( 'parcours_year' => 1 )
);

// One theme, including cancelled occurrences.
$theme = wp_seed_events_query_occurrence_collection(
    array(
        'event_id'          => 123,
        'status'            => 'all',
        'include_cancelled' => true,
    )
);

// Second page.
$page = wp_seed_events_query_occurrence_collection(
    array( 'page' => 2, 'per_page' => 20 )
);

// Canonical parcours tree.
$tree = wp_seed_events_query_grouped_occurrence_collection(
    array( 'status' => 'all', 'limit' => 300 )
);
```

## Builder boundary

This foundation is builder-independent. Future Divi, Gutenberg, Spectra or Content Kit adapters must consume these public functions instead of reading occurrence meta, projection tables or lifecycle options. This lot does not add a final builder adapter, HTML agenda, CSS or a competing template engine.

See also the canonical [Domain Model](../DOMAIN-MODEL.md).
