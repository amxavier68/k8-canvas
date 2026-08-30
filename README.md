# K8 Canvas

K8 Canvas is the WordPress-native visual development and scoped custom CSS layer for the Kollabor8 Web Collectives ecosystem.

Development follows evidence-led release gates. Functional changes are made on branches and released only after responsive and real-path validation.

## Foundation status

The engineering foundation now separates two concerns:

- **K8 Canvas plugin** — the production WordPress-native design and presentation layer.
- **K8 Site Support Bundle** — a separate diagnostic utility under `tools/` for technical triage. It is not loaded by the K8 Canvas plugin.

The support utility exports a privacy-conscious JSON snapshot covering WordPress, PHP, theme, plugin and selected component/runtime information while excluding customer data and secrets.

See `docs/DEVELOPMENT.md` for the staging, Git and release boundaries.
