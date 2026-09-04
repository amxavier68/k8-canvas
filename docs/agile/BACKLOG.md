# Controlled Backlog

The foundation is retained as the locked baseline. Owner approval on 2026-09-04
promoted the multi-agency ownership slice into urgency mode.

## Locked

### KC-001 — Establish the executable product foundation

**Story:** As the product owner, I want a minimal repository and runtime boundary so
future stories can be implemented and evidenced without inventing architecture each
time.

**Acceptance criteria:**

- canonical Epic and Agile workflow are version controlled;
- WordPress plugin shell activates without executing product functionality;
- Python service exposes a versioned health contract;
- automated health-contract tests pass;
- PHP entry file passes syntax validation;
- Render configuration uses a free plan and creates no database or worker;
- no PBAC, commercial or autonomous publishing capability is introduced.

## Active

### KC-002 — Establish multi-agency ownership and feature control

**Story:** As the platform owner, I want agencies to manage independently owned
client organisations and sites through a normalised, versioned interface so K8
Canvas can serve Kollabor8 and agency-level customers without entangling ownership.

**Implemented in the first vertical slice:**

- normalised WordPress tables for organisations, relationships, sites, features
  and feature assignments;
- versioned REST operations for organisation creation, listing, updating and
  archival, relationship creation, site registration and feature control;
- an administrator-only multi-level dashboard shell;
- explicit organisation and site boundaries for feature assignments.
- visible agency/client/site context switching;
- administrator forms for organisations, sites and agency-client relationships;
- organisation- and site-scoped feature toggles using the REST interface.
- WordPress-user memberships with owner, editor and viewer permission profiles;
- organisation-bound permission grants and a server-side permission resolver;
- append-only audit events for organisation, relationship, site, feature and
  membership changes;
- visible Access & Audit dashboard controls.

**Current gate:**

- CI PHP syntax validation must pass;
- schema creation must be exercised in a disposable WordPress instance;
- duplicate, invalid and cross-boundary requests must have contract tests;
- memberships, PBAC grants and an append-only audit ledger must be implemented;
- non-administrator API access remains prohibited until isolation tests pass.

**Exclusions:** billing, subscriptions, autonomous publishing and unrestricted
customer access.

## Proposed capability containers

- KC-CAP-01 Visual Canvas
- KC-CAP-02 Semantic Components
- KC-CAP-03 Design Tokens and Families
- KC-CAP-04 Responsive Behaviour
- KC-CAP-05 Visibility Intelligence
- KC-CAP-06 Accessibility and Quality
- KC-CAP-07 Performance Governance
- KC-CAP-08 Evidence and Recovery
- KC-CAP-09 Open Ownership and Portability
- KC-CAP-10 Python Intelligence
- KC-CAP-11 Extension Contracts

## Promoted from commercial-access bench

- PBAC and delegated responsibility — next KC-002 security increment
- customer accounts and workspaces — organisation foundation implemented

## Still benched

- billing, subscriptions and entitlements
- external publishing roles
