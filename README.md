# K8 Canvas

K8 Canvas is a governed, visibility-first WordPress building system. It is being
developed as a standalone Kollabor8 product with a WordPress-native editor and a
Python intelligence layer.

## Product promise

**Build with freedom. Publish with proof. Own the result.**

## Repository map

- `apps/wordpress-plugin/` — WordPress dashboard, REST interface and visual editor boundary
- `services/intelligence/` — Python 3 analysis and evidence services
- `docs/product/` — canonical Epic, capabilities and product decisions
- `docs/agile/` — approved story lifecycle and backlog
- `render.yaml` — reproducible, free-plan preview infrastructure

## Local intelligence service

```bash
python3 -m venv .venv
. .venv/bin/activate
pip install -r services/intelligence/requirements-dev.txt
uvicorn k8_canvas.main:app --app-dir services/intelligence/src --reload
```

Run the controlled baseline tests with:

```bash
python3 -m unittest discover -s services/intelligence/tests -v
php -l apps/wordpress-plugin/k8-canvas.php
```

Development follows **inspect → gate → execute → test → prove → lock**. Proposed
work is not implementation authority. Only owner-approved, approachable stories
move into active development.

## Current urgency-mode API

Activating the plugin creates the first normalised multi-agency schema and exposes
administrator-only endpoints under `/wp-json/k8-canvas/v1` for organisations,
agency-client relationships, sites, features and feature assignments. This is a
bootstrap boundary: customer access stays closed until membership, PBAC and tenant
isolation tests are complete.

The WordPress **K8 Canvas** menu now provides the first visible dashboard. An
administrator can create organisations, connect agency-client relationships,
register sites, switch context and control features without leaving WordPress.

Version `0.3.1-alpha.2` adds organisation memberships, seeded owner/editor/viewer
permission profiles, boundary-scoped grants and an append-only audit view. These
controls remain administrator-operated while cross-tenant isolation is tested.

Version `0.4.0-alpha.1` connects those permission profiles to the REST boundary.
Non-administrator API requests are denied by default and scoped to the caller's
active organisation membership; WordPress administrators retain recovery access.
