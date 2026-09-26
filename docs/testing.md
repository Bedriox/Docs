# Testing

The repository gates change as development proceeds; exact test counts belong
in immutable CI run and release evidence rather than this evergreen guide.
The current automated suite covers the following layers:

| Repository | Primary responsibility |
| --- | --- |
| Bedriox | Composition, authentication, sessions, simulation, worlds, and end-to-end contracts |
| RakNet | UDP discovery, negotiation, reliability, ordering, fragmentation, and cleanup |
| Protocol | Bounded protocol-2193 framing, encryption, packets, and literal wire vectors |
| Data | Immutable admitted registries, canonical states, hashes, and deterministic generation |

The owning `composer check` gates also cover dependency metadata and advisories, formatting, static analysis, documentation links, manifests, licenses, and repository-specific validation.

This automation exercises bounded transport and protocol failure paths,
deterministic data admission, protocol-2193 codecs and initialization payloads,
login/authentication state transitions, command queues, a deterministic 20 TPS
simulation, spawn synchronization, movement projection, chat, chunk streaming,
player registry cleanup, ordered multiplayer actor publication, absolute peer
movement, posture transitions, chunk-gated visibility, actor removal, reconnect
cleanup, authoritative inventory transfers, block breaking and placement,
prediction correction, causal-session failure isolation, and disconnect
handling.

Plugin and command tests additionally cover PHAR validation, source-definition
admission, dependency and lifecycle cleanup, sender and permission policy,
aliases and qualified fallback names, quoted parsing, command events, handler
failure containment, non-blocking console input, queue limits, and cooperative
job cancellation. PluginTools tests source discovery, namespace containment,
unsafe path rejection, `makeplugin`, overwrite protection, child-process
limits, signatures, checksums, and preservation of previous output on failure.
ExamplePlugin tests both console and player sender branches against the public
API without enabling Bedrock command packets. Its entity example additionally
checks owner-scoped definition registration, authoritative spawn requests,
bounded state round trips, lifecycle hooks, bounded controller intents, and
typed spawn and interaction events.

Entity runtime tests cover registry ownership and cleanup, spatial indexing,
physics, actor visibility, explicit spawning, spawn-egg authority, persistence
round trips and corruption isolation, bounded natural-spawn candidates,
activation budgets, behavior priority, navigation bounds, plugin lifecycle
attribution, and worker result validation. Retail qualification still requires
the complete multi-client entity journey described below.

## Run the gates

In each PHP repository:

```shell
composer install
composer check
```

In Docs and RFCs:

```shell
php tools/validate-docs.php
```

With all repositories present, Bedriox also provides:

```powershell
powershell.exe -NoProfile -File tools/verify-workspace.ps1
```

`-SkipClean` is useful only while coordinating uncommitted cross-repository work and is not a release gate.

## Outstanding qualification

Automated results do not establish retail compatibility. Before adding a supported client or protocol, the project still needs recorded retail-client runs across the claimed platforms, repeated join/spawn/movement/chat/disconnect scenarios, multi-client visibility verification including actor removal, adverse packet-loss and reordering tests, long-running soak tests, and measured CPU, memory, latency, and throughput targets.

Failures and skipped tests must remain visible in release evidence. Never convert an unavailable prerequisite into a passing result.

## Cumulative client journey

Every gameplay milestone must preserve discovery, RakNet negotiation,
authentication, encryption, spawn, correct grass terrain, movement across
chunk boundaries, normal breathing and gravity, attributed chat, session-local
failure isolation, disconnect cleanup, reconnect, inventory open and close,
authoritative stack movement and splitting, grass breaking and placement, and
multiplayer visibility and actor cleanup.

Tests accumulate. A new milestone may not replace, weaken, or silently skip an
earlier journey. Wire-visible changes additionally require a recorded retail
smoke test before qualification advances. The two-client procedure is
documented in [player and multiplayer lifecycle](player-multiplayer.md). See
[client journey](client-journey.md) for the cumulative contract.

## World generation and streaming evidence

The settings and streaming milestone's automated tests cover configuration
defaults and precedence, malformed and duplicate entries, unknown keys,
numeric bounds, and all-or-none optional spawn coordinates. Startup-failure
tests prove that invalid configuration is rejected before a UDP socket is
bound.

World tests inspect the fixed-flat profile and version-one default overworld at
positive and negative chunk coordinates. Default-generator tests cover smooth
noise continuity, regional biome/elevation distribution, safe spawn,
generation-order independence, chunk edges, and generator-version rejection.
Independent decoding proves that internal block-state IDs are translated to
current Bedrock runtime IDs and that complete chunk columns have valid palettes
and framing. The repository terrain-map tool emits bounded height, biome, and
distribution diagnostics for visual tuning.

View-manager tests cover radius calculations through the configured cap,
nearest-first ordering, deterministic ties, movement across every
chunk-boundary direction, duplicate suppression, cleanup, and out-of-view
requests. Runtime tests prove that generation, send, queue, and cache limits
hold on every tick and that active chunks cannot be evicted. Configuration
tests also reject cache limits smaller than
`server.max-players * (2 * chunks.view-distance + 1)^2` and products above
65536.

Retail qualification still requires recorded travel through plains, coast,
river, forest, mountain, cave, positive-coordinate, and negative-coordinate
terrain; reconnection rebuilding the view; restart persistence; and no
unbounded queue or server crash. Automated evidence covers world persistence
and the inventory, emote, and interaction slices, but does not by
itself qualify them for a public support claim.

## Inventory and block-interaction evidence

Inventory tests cover opening and closing the main window repeatedly, hotbar
selection, Take, Place, and Swap requests, atomic stack splitting, cursor
transfers, request and stack-ID lineage, stale-ID rejection, rollback after a
later invalid action, correction snapshots, selected-stack peer updates, and
placement from a resulting split stack.

Interaction tests cover break start, progress and completion, benign stop and
abort ordering, reach and block-state revalidation, simultaneous prediction
correction, all six placement faces, collision rejection, one-item inventory
consumption, world mutation, owner repair packets, byte-identical multiplayer
block updates, and held-equipment visibility.

Crafting tests cover exact shaped and shapeless matching, offsets, declared
mirroring, ingredient alternatives and tags, grid-size eligibility,
authoritative consumption and output, repeated crafting, stale lineage,
invalid action order, rollback, corrections, table open and close cleanup,
plugin ownership, cancellation, disablement, and complete current catalog
projection.

Storage-container tests cover canonical block entities, single and paired
inventories, chunk and player persistence, Ender Chest isolation, portable
shulker contents, active-window lifecycle, atomic player/storage transactions,
stale revision rollback, plugin cancellation and cleanup, independent
multi-view stack identities, block presentation, and exact affected-slot
responses. Processing stations require separate evidence.

## Multiplayer evidence

Automated tests establish deterministic registry indexes and cleanup, duplicate
and capacity rejection, join packet order in both directions, baseline actor
metadata, actor visibility only after its chunk is sent, hide and re-show
transitions across view boundaries, peer-only movement, posture updates only on
transitions, owner-only movement correction, `RemoveActor` before
`PlayerListRemove`, and isolation of event encoder or fan-out budget failures
to the causal session.

Retail qualification remains open. It requires two clients to execute the
join, reciprocal visibility, movement, posture, chat, departure, and rejoin
checklist, followed by the longer soak, loss, ordering, scale, and memory gates
in the roadmap. Do not infer that a successful single-client run qualifies
multiplayer.
