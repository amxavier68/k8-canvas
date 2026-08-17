# K8 Canvas v0.1 release gate

## Outcome

Prove a WordPress-native editor-to-page loop with one responsive block and safely scoped CSS declarations.

## Included

- editable eyebrow, heading, body and CTA;
- base, tablet (1024px) and mobile (767px) declaration controls;
- server-rendered public markup;
- per-block CSS scope;
- rejection of selectors, braces, imports, URLs and script-bearing CSS;
- keyboard-visible CTA focus and 44px minimum target;
- reduced-motion baseline.

## Explicitly excluded

- drag-and-drop page composition;
- global design tokens;
- templates and theme building;
- arbitrary selectors or raw stylesheets;
- Express or Render services;
- Elementor import or compatibility promises.

## Acceptance evidence

1. Install and activate the plugin on a non-production WordPress environment matching `kollabor8.net.au`.
2. Add the K8 Responsive Panel and edit all content fields.
3. Set visibly different base, tablet and mobile padding values.
4. Save, reload the editor and confirm values persist.
5. View the public page at desktop, tablet and mobile widths.
6. Confirm styles affect only the selected block.
7. Submit blocked CSS examples and confirm they do not reach public output.
8. Verify CTA keyboard focus, valid destination and no horizontal overflow.
9. Repeat the checks after deployment to `kollabor8.net.au` through a controlled rollout.

The gate passes only when screenshots and the tested URL demonstrate the complete path.

## Build the installable ZIP

Run `npm run check`, followed by `npm run build:plugin`. The release candidate is written to `dist/k8-canvas-v0.1.0.zip` with `k8-canvas/` as the archive root expected by WordPress.
