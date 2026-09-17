# Change safety

Existing working behavior is frozen unless a task explicitly requires changing
it. A passing test suite does not authorize unrelated behavior changes.

Every change records its observable objective, owning repositories, intended
file areas, protected behavior, test evidence, documentation impact, and
rollback point. Unexpected cross-layer work pauses until a focused reproduction
and characterization test prove that the additional layer must change.

The permanent protected surfaces include discovery fields, ports, protocol
identity, authentication, encryption transitions, packet IDs, registry hashes,
chunk framing, block translation, player state, dependency pins, and public
configuration defaults. They may change only in a focused change with explicit
migration and qualification evidence.

## Required gate

```text
declare scope and preserved behavior
-> run the baseline
-> add a failing regression or characterization test
-> implement in the owning layer
-> run focused and owning-repository checks
-> run consumer and workspace checks
-> inspect the complete diff
-> push and confirm CI
-> perform the applicable retail journey
-> retain a known-good rollback point
```

Tests from completed milestones remain permanent and run for every later
milestone. Vendor directories are never edited as a substitute for changing
and repinning the owning component.
