# K8 Canvas development workflow

K8 Canvas is developed as a WordPress plugin, not as a replacement for WordPress itself.

## Environment model

1. **Production** remains the customer-facing WordPress site.
2. **Staging** is the required place for plugin/theme conflict diagnosis, Elementor work and controlled WooCommerce tests.
3. **GitHub** is the source of truth for K8-owned code, exported templates, technical decisions and release evidence.
4. WordPress database content, uploads, customer records and secrets are not committed to Git.

## Release gate

Inspect → next gate → execute → test → correct → lock → move on.

A change is releasable only when:

- PHP syntax validation passes;
- the plugin activates without a fatal error;
- the relevant WordPress/Elementor path is exercised on staging;
- desktop/tablet/mobile behaviour is checked when UI is affected;
- no customer data or credentials are present in the repository;
- rollback is known.

## Site support bundle

`tools/k8-site-support/` contains a separate diagnostic utility plugin. It is intentionally not loaded by K8 Canvas.

Install it only when technical triage is needed. From **Tools → K8 Support Bundle**, an administrator can export a JSON diagnostic snapshot containing WordPress/PHP/theme/plugin/component versions and selected runtime settings.

The exporter deliberately excludes users, orders, customer records, form submissions, passwords, API keys, tokens, secret option values and database contents.

## Client-site repository boundary

A client-specific repository may contain:

- an Astra/child-theme layer if custom theme code is genuinely required;
- small K8-owned plugins or MU-plugins;
- Elementor template/kit exports suitable for version control;
- plugin/theme baseline manifests;
- technical decisions, test evidence and rollback notes.

Do not commit full WordPress databases, `wp-content/uploads`, cache directories, backups, payment data or live credentials.
