# Architecture Boundary

## WordPress layer

Owns plugin lifecycle, editor integration, content persistence, revisions,
rendering and the explicit publish action.

The urgency-mode MVP also provides a WordPress administrative control surface and
versioned REST interface for organisations, agency-client relationships, sites and
feature assignments. The initial API is deliberately restricted to WordPress
administrators until organisation memberships and PBAC enforcement are proven.

## Relational ownership layer

K8 Canvas models agencies and clients as organisations. Agency-client ownership is
not encoded as a recursive customer field; it is an explicit, time-bound
relationship. Sites retain an independent owning organisation.

The first normalised schema comprises:

- organisations;
- organisation relationships;
- sites;
- feature definitions; and
- organisation- or site-scoped feature assignments.

Archival and relationship termination replace destructive deletion. Memberships,
permission grants and the audit ledger are the next required isolation gate.

## Python intelligence layer

Owns deterministic analysis, recommendations and evidence generation. Initial
capabilities may include semantic structure, accessibility, responsive risk,
visibility, schema consistency, performance budgets and design-system drift.

The initial service contract exposes only operational metadata and a health check.
Analysis endpoints require separately approved stories.

## Contract boundary

WordPress communicates with Python through versioned HTTP contracts. Results are
advisory until a future approved story defines a blocking quality gate. Python may
not mutate WordPress content or publish pages in the foundation phase.

## Deferred architecture

- subscriptions, billing and entitlements
- autonomous content generation or publication
- queues and background workers
- customer-facing hosted control-plane infrastructure

PBAC and multi-user responsibility are no longer deferred. They are explicitly the
next security gate and must be completed before non-administrator access is opened.

Deferred items are not implied requirements and must return through owner approval.
