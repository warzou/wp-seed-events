# Occurrence Projection and Lifecycle V3

## Role

Lifecycle v3 adds an internal SQL projection with one row per normalized event
occurrence. The projection supports future indexed reads by promotion, parcours
year and date without changing the public Event Occurrences API.

The source of truth remains the canonical `_wp_seed_event_occurrences` post
meta. The table is disposable, versioned and fully rebuildable. Consumers must
not read it directly.

## Table

The table name is:

`{$wpdb->prefix}wp_seed_event_occurrences`

Each row contains only technical public-domain projections:

- event ID and occurrence UID;
- source occurrence position;
- promotion ID and parcours year;
- raw and sortable start/end values;
- cancelled flag;
- primary event type;
- event status and pinned flag;
- projection update timestamp.

Indexes cover event identity, promotion, parcours year, start date and the
combined collection filters. The pair `(event_id, occurrence_uid)` is unique.

The table never stores people, contact details, places, media, rendered HTML,
builder state, secrets or private metadata.

## Occurrence identity

A persisted occurrence UUID is authoritative and remains stable when rows are
reordered.

Legacy rows without a UUID receive a deterministic projection UID derived from
their normalized business fields. Strictly identical legacy duplicates receive
an ordinal suffix. This keeps distinct legacy rows stable across ordinary
reordering, but changing a business field changes their projection UID. The
canonical post meta is never backfilled or rewritten by lifecycle v3.

A duplicate persisted UUID inside one event is an explicit projection error.
The event is not partially projected.

## Synchronization

Every relevant event save rebuilds that event projection in one transaction:

1. normalize the canonical occurrences;
2. prepare all rows in memory;
3. delete the previous rows for the event;
4. insert the complete replacement;
5. commit only when every write succeeds.

Any failure rolls the transaction back. Permanent event deletion removes its
projection rows. Promotion or event-type changes reuse the existing targeted
lifecycle refresh path.

## Versioned migration

Lifecycle v3 extends the existing lifecycle options and backfill. It does not
create a second migration system.

- expected lifecycle index version: `3`;
- bounded ascending-ID batches;
- resumable cursor;
- expiring atomic lock;
- bounded error list;
- idempotent retry;
- final integrity checks before readiness.

Upgrades from lifecycle v2 create the table and rebuild every event. Fresh
activation creates the schema and starts the same bounded process. The version
option is set to `3` only after every event succeeds and structural integrity
checks pass.

An event modified while the migration is running is synchronized immediately.
If its ID has not yet passed the cursor, the backfill safely rebuilds it again.

## Read fallback

The internal projection reader uses indexed rows only when lifecycle v3 is
ready. Until then, it returns rows built from the canonical Event Occurrences
API for the requested event. Public Event Data and existing collections keep
their established fallback behavior.

No frontend request starts a global repair.

## Recovery

The table can be dropped and rebuilt without data loss. An interrupted batch
resumes from its cursor. A missing table forces a new v3 reconstruction.
Integrity checks reject duplicate identities, orphan rows and invalid
promotion/year pairs.

Operators can use the existing lifecycle reconstruction command in WordPress
admin. Rollback of the plugin does not require a data migration because the
canonical occurrence post meta is unchanged.

## Public boundary

This projection is not a public API. Stable consumer contracts remain:

- `wp_seed_events_get_event_occurrences()`;
- `wp_seed_events_get_event_data()`;
- the documented Promotion APIs.

Public occurrence collections consume this projection through their exact
index/fallback boundary. Gutenberg, Divi, Spectra and Content Kit adapters remain
separate integration lots.


## Public occurrence collection consumer

Lifecycle v3 alimente desormais les collections publiques d'occurrences quand son etat est `ready`. Le selecteur SQL applique filtres, total, ordre stable et pagination avant hydratation. Tant que l'index n'est pas pret, si la table manque ou si une requete echoue, la collection reconstruit exactement les lignes depuis le stockage canonique et applique les memes regles en PHP.

Le choix du chemin reste interne. Ni les fonctions publiques ni REST n'exposent table, version, option, verrou, curseur ou horodatage de projection. Voir [Occurrence Collections](OCCURRENCE-COLLECTIONS.md).
