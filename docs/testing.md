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
cleanup, causal-session failure isolation, and disconnect handling.

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
failure isolation, disconnect cleanup, and reconnect. Interaction and
inventory-open behavior join this permanent baseline when their protocol
handling lands; multiplayer visibility and actor cleanup join it in the next
milestone.

Tests accumulate. A new milestone may not replace, weaken, or silently skip an
earlier journey. Wire-visible changes additionally require a recorded retail
smoke test before qualification advances. The two-client procedure is
documented in [player and multiplayer lifecycle](player-multiplayer.md). See
[client journey](client-journey.md) for the cumulative contract.

## Flat-world streaming evidence

The settings and streaming milestone's automated tests cover configuration
defaults and precedence, malformed and duplicate entries, unknown keys,
numeric bounds, and all-or-none optional spawn coordinates. Startup-failure
tests prove that invalid configuration is rejected before a UDP socket is
bound.

World tests inspect the complete bedrock, dirt, grass, air, and biome layout at
positive and negative chunk coordinates. Independent decoding proves that
internal block-state IDs are translated to current Bedrock runtime IDs and
that complete chunk columns have valid palettes and framing.

View-manager tests cover radius calculations through the configured cap,
nearest-first ordering, deterministic ties, movement across every
chunk-boundary direction, duplicate suppression, cleanup, and out-of-view
requests. Runtime tests prove that generation, send, queue, and cache limits
hold on every tick and that active chunks cannot be evicted. Configuration
tests also reject cache limits smaller than
`server.max-players * (2 * chunks.view-distance + 1)^2` and products above
65536.

Retail qualification still requires recorded grass streaming across positive
and negative chunk boundaries, reconnection rebuilding the view, and no
unbounded queue or server crash. Automated evidence does not qualify inventory,
emotes, persistence, block interaction, or terrain generators other than flat.

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
