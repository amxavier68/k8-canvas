# Architecture Boundary

## WordPress layer

Owns plugin lifecycle, editor integration, content persistence, revisions,
rendering and the explicit publish action.

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

- PBAC and multi-user responsibility models
- subscriptions, billing and entitlements
- autonomous content generation or publication
- databases, queues and background workers
- customer-facing hosted infrastructure

Deferred items are not implied requirements and must return through owner approval.
