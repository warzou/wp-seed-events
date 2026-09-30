# Event image lightbox

Communication images preserve the attachment ratio. The renderer checks the requested WordPress size against `full` and falls back to `full` when the selected size was cropped (allowing only one pixel of thumbnail rounding). Responsive `srcset` and native width/height attributes remain intact. Do not reset image `aspect-ratio` to `auto`: WordPress `sizes="auto"` lazy images need the intrinsic ratio from those attributes.

The default click action is now the lightbox for the public renderer, shortcode and new builder modules. Explicit `click_action="none"` disables interaction; `click_action="original"` keeps a plain full-size link. Explicit saved actions remain authoritative. Legacy `lightbox`/`link_original` options remain readable.

Gutenberg, the shortcode and theme renderers use an actual `WP_Block` for `core/image`, with `lightbox.enabled=true` and `scale=contain`. This lets WordPress enqueue its Interactivity API view module and render its own overlay, focus return, Escape and pointer handling. The prior direct callback call skipped asset registration and supplied a stdClass where current Core requires WP_Block.

A small script contains Tab/Shift+Tab among visible native controls. Core 7.1.2 otherwise includes hidden gallery navigation controls in its focus boundary for a single image. The script is scoped to an active overlay displaying a WP Seed Events figure. CSS supplies the dark backdrop, contain fit and background scroll lock only for that selected figure. No external library or independent overlay is added.

A `noscript` link exposes the full-size source. WordPress versions without the native image lightbox retain the figure and a visible source link. Existing alt text is preserved; no attachment or editorial content is changed. A classic theme rendering after wp_head gets the image stylesheet printed in the footer.

The Divi module retains its existing Divi-native popup adapter and original-link fallback; new modules select the lightbox by default. No additional popup library is introduced there. Shared image ratio protection applies to Divi as well. Divi runtime deployment must follow its own site validation.

Site-specific columns belong to the site composition, never to the generic renderer. Avoid fixed image heights or equal-height panels. Tablet stacking and maximum media column widths are site choices.

Run `npm run test:visuals` with PHP in PATH. This covers the native typed block adapter, default/explicit actions, no-JS source, shortcode, cropped portrait/landscape/square/tall sizes, and native focus compatibility. Builder metadata and compiled defaults are checked by the existing Gutenberg/Divi contracts.
