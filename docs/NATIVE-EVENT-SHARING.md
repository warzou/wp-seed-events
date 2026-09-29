# Single primary event sharing

The common renderer `wp_seed_events_render_event_share_menu()` defaults to
PRIMARY_SHARE_ONLY: one visible Share button after JavaScript initialization.
Divi, automatic public rendering and the Gutenberg shortcode use this same
renderer. Existing one- and two-argument calls remain valid. Both historical
layout values (`menu`, `inline`) now use the compact component, with no details
accordion. Event/template deduplication is unchanged.

The optional third argument is `array('secondary_actions' => 'none')`.
Allowed values are `none` (default), `copy`, `email`, and `both`.
Unknown values use the default. The shortcode exposes the same option:

```
[wp_seed_event_share]
[wp_seed_event_share secondary_actions="copy"]
[wp_seed_event_share secondary_actions="email"]
[wp_seed_event_share secondary_actions="both"]
```

In a secure context with Web Share, clicking Share calls `navigator.share`
synchronously with the current event's public title and canonical URL.
AbortError is silent and leaves the fallback closed. Other failures open the
compact fallback, as does an unavailable API. The primary button remains useful
without Web Share. A pending native share disables repeated activation.

The fallback contains Copy link and a PHP-generated mailto link. It has a unique
ID associated with the button's aria-controls and aria-expanded. Opening moves
focus to Copy; Escape closes and restores primary focus. A second primary click
closes it; outside clicks close without stealing focus. Native HTML buttons and
links provide normal Tab/Enter/Space behavior. Focus outlines and polite copy
feedback are shared across builders. The panel is an action group, not an ARIA
menu requiring arrow-key navigation.

Clipboard API and the existing textarea/execCommand fallback are retained.
Without JavaScript, the primary is hidden and a noscript email link remains
usable. Explicit optional copy actions stay disabled until enhancement.
Secondary options do not disable the automatic fallback.

Only prefixed generic structure/styles are added. No builder integration, site
composition, Dynamic Data contract, or PDE deployment is changed. This branch
builds on the validated Therapsy V2 commit db4a385; the historical dirty checkout
must not be replaced with it.
