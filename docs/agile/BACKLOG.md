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
- selectable WordPress users with explained access profiles;
- atomic membership profile replacement and membership revocation;
- schema diagnostics and human-readable audit events.
- deny-by-default permission matching for organisation and site resources;
- membership-scoped organisation, relationship, site, feature, membership and
  audit queries;
- server-side ownership resolution for site operations;
- immediate enforcement of membership revocation at the REST boundary.

**Current gate:**

- CI PHP syntax validation must continue to pass;
- the disposable WordPress/MySQL PBAC contract must prove clean installation and
  idempotent schema upgrade;
- Owner A, Viewer A and Owner B must pass the two-organisation access matrix;
- guessed-ID cross-boundary reads and mutations must be denied without state or
  audit changes;
- profile replacement must leave one active grant and revocation must take effect
  on the caller's next request;
- non-administrator access may not ship until this runtime contract passes and the
  owner accepts its evidence.

**Implemented but not locked:** memberships, permission profiles and grants,
server-side PBAC resolution, REST enforcement, immediate revocation and the
append-only audit ledger. Their presence in code is not runtime proof.

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
