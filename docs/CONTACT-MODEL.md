# Canonical event contact

WP Seed Events uses one user-facing event contact concept: `contact`.

## Storage

The source of truth remains `_wp_seed_event_contacts`. A contact is a person
association whose canonical role is `contact`; no second contact meta is kept in
sync. Multiple people may share that one role when an event has several valid
public contacts.

Historical stored roles are migrated as follows:

- `registration_contact` -> `contact`
- `information_contact` -> `contact`
- identical rows carrying both historical roles are deduplicated
- different registration and information values block the migration

The migration in `includes/admin/contact-migration.php` is explicit and never
runs on ordinary requests. It provides a preflight, an idempotent migration and
a rollback function; deployment must also retain the SQL and raw-meta backup.

## Public contracts

- Event Data: `contact` is the canonical public list. The read-only
  `registration_contact` and `information_contact` keys are V1 aliases.
- REST: `contact` is the only writable property. Public reads contain only
  coordinates explicitly authorized for publication; edit-context reads and
  writes require `edit_post`.
- Dynamic Data and block bindings: `contact` / "Contact" is the only new field.
- Gutenberg and Divi People modules expose one Contact filter.
- Historical shortcode roles, Gutenberg role attributes and Divi toggles map to
  the canonical role at runtime and do not create another source of truth.

ICS output is unchanged and does not add contact data.
