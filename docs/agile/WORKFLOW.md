# Agile Delivery Workflow

## Story states

| State | Meaning |
| --- | --- |
| Proposed | Captured for assessment; not authorised |
| Approved | Accepted by the owner for refinement |
| Ready | Approachable, bounded and testable |
| Active | The single current implementation gate |
| Proven | Acceptance criteria passed with evidence |
| Locked | Accepted baseline; reopen only with new evidence or a deliberate decision |
| Benched | Intentionally deferred because it does not serve the current gate |

## Ready conditions

A story may become Ready only when it:

- advances the canonical Epic;
- identifies the product problem and intended outcome;
- has owner approval;
- is small enough to implement and validate safely;
- has objective acceptance criteria and required evidence;
- declares exclusions and dependencies; and
- contains no unresolved commercial, permission or architectural decision.

## Working rule

Maintain one Active story. Follow:

**Propose → approve → refine → build → test → substantiate → owner acceptance → lock**

Implementation evidence does not prove runtime behaviour. Deployment evidence does
not prove the product outcome.
