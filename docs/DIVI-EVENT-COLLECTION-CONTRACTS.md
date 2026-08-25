# Divi event collection contracts

## Pinned ranking priority

The canonical event collection accepts `pinned_priority`:

- `first` keeps the historical behavior and ranks pinned events before other
  events. This is the default when the argument is absent or invalid.
- `none` removes only the pinned ranking partition. All selected events then
  follow the collection's canonical business ordering.

The existing `pinned` argument continues to control inclusion independently.
For example, `pinned=all&pinned_priority=none` includes pinned and unpinned
events in one canonical chronological order.

Divi exposes the same contract as `wpSeedEventPinnedPriority` in saved Loop
attributes and `wp_seed_event_pinned_priority` in Visual Builder REST previews.
Frontend and Visual Builder therefore use the same canonical selector.

## Current event type condition

The public Divi condition `wpSeedEventsCurrentEventHasType` displays an element
when the current loop event or event detail page has at least one selected
canonical event type.

- choices come from the active event type registry;
- saved values are canonical type keys, not term or event IDs;
- multiple selected types use OR semantics;
- no selection, an unknown type, or a non-event context evaluates to false;
- deleted historical values remain readable and are never remapped, but are no
  longer offered for new selections.

The condition reuses the shared Divi event resolver and
`wp_seed_events_event_type_keys_for_event()`. Consumer sites remain responsible
for button labels and destination URLs.

## Compatibility

Loops without `wpSeedEventPinnedPriority` retain pinned-first ordering. The
existing `wpSeedEventsHasResults` condition and all existing event collection,
Event Data, and Dynamic Data contracts are unchanged.
