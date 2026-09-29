# Sharing compatibility

One public renderer and one JavaScript engine serve every consumer. The default is
PRIMARY_SHARE_ONLY; Web Share is invoked during the click, AbortError is silent,
and unavailable/rejected APIs open the compact Copy / Email fallback.

## Public PHP calls

`wp_seed_events_render_event_share_menu($event, $layout = 'inline', $options = array())`
also accepts the historical `($event, $options)` call. Explicit third-argument
options override the legacy array. Invalid layout strings preserve the default.
The generic text/display/order normalizers remain available to older adapters.

Options: `secondary_actions` (none/copy/email/both, default none), `label`,
`copy_label`, `email_label`, `action_order`, `show_share`, `show_copy`, `show_email`.
The show flags govern action availability; historical true defaults do not opt
into permanently visible secondary actions. Explicitly hiding share displays the
enabled alternative actions. Labels and presentation values are escaped.

`action_attributes[share|copy|email]` accepts class, aria-label, data-icon,
data-icon-placement and hide_label. Executable attributes and URL overrides are
not accepted. Presentation classes, icon processing and presets belong to the
Divi adapter. The common core has no domain or builder condition.

## Divi

The existing content/decorations/preset handles from the reconstructed baseline
are retained, together with current generation handles. New secondary_actions
settings opt into permanent Copy/Email. REST preview and frontend call the same
renderer; Gutenberg shortcode/layout calls retain their defaults. Builder preview
fragments are enhanced by the shared script, without an additional event engine.

## Asset and deployment compatibility

`includes/public/event-share.js` is the only engine. The footer prints these same
public assets for current bootstraps; if an older bootstrap has enqueued the
public asset handles, it prints neither a second script nor a second stylesheet.
Divi-only styling is in `includes/integrations/divi/event-share.css`.

Do not overwrite a divergent installation wholesale. Compare it and deploy only
sharing files; retain unrelated Rich Content, media, context and renderer fixes.
Back up and hash-check the exact target files immediately before any deployment.
