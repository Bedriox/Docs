# Ecosystem milestone roadmap

This roadmap describes the dependency order and exit criteria for building a
small but production-minded Minecraft: Bedrock Edition server in modern PHP. It
is intentionally independent of dates, release numbers, and current completion
status. Release notes and the compatibility policy are the authority for what a
particular Bedriox release supports.

Each milestone builds on the previous milestone. A feature is not complete
because packets can be exchanged once: its tests, limits, failure behavior,
documentation, and provenance must also satisfy the milestone gate.

## 1. Foundation and repository contracts

Establish the project boundaries, engineering rules, and reproducible toolchain
before protocol behavior grows.

Planned outcomes include:

- separate ownership for the main runtime, RakNet transport, Bedrock protocol,
  reviewed protocol data, public documentation, and RFC decisions;
- supported PHP and dependency constraints, deterministic dependency locks, and
  automated formatting, static analysis, unit tests, license checks, and
  vulnerability audits;
- bounded configuration types, structured errors, monotonic time abstractions,
  logging contracts, and clean shutdown behavior; and
- GPL-3.0-only code and tooling, GPL-3.0-only documentation, focused commit
  history without required personal-email trailers, and auditable third-party
  provenance.

The gate requires every repository to pass its local checks and the ecosystem
workspace verifier, with documented ownership, security reporting, contribution
workflow, and release responsibilities. No library may depend on another
repository's unversioned default branch for a release.

## 2. UDP discovery

Provide the smallest observable Bedrock server behavior: safe handling of an
unconnected status ping and a bounded pong response.

The implementation must parse exact wire fields and integer byte order, reject
wrong identifiers, magic, lengths, and unsupported values, sanitize advertised
status fields, cap response amplification, surface socket failures, and close
idempotently. Golden vectors must be implemented independently of production
encoders. Loopback tests must cover repeated requests, malformed datagrams,
status updates, bind failures, and lifecycle cleanup.

The gate requires deterministic unit and raw-UDP integration tests, repeated
loopback stability, documented status configuration, and a successful discovery
probe from each retail client platform claimed by the compatibility policy.

## 3. Offline connection negotiation

Negotiate RakNet protocol version, observed client endpoint, client identity,
and a safe maximum transmission unit before allocating a connected session.

The server must implement the Request 1/Reply 1 and Request 2/Reply 2 exchange,
including the incompatible-version response. Pending handshakes must be bounded
and expire on a monotonic clock. Request 2 may not raise the negotiated MTU, a
client GUID may not own multiple endpoints, retransmission must be idempotent,
and state becomes established only after a reply is sent successfully. Session
removal and server shutdown must release every related index and capacity slot.

The gate requires exact wire vectors, truncation and malformed-field matrices,
MTU boundary tests, capacity and timeout tests, raw-UDP retransmission tests,
clean lifecycle tests, fuzz seeds, and successful negotiation by every claimed
retail client. Internet-facing deployment remains out of scope until abuse
controls appropriate to connection setup are defined and verified.

## 4. Reliable RakNet transport

Turn negotiated endpoints into bounded reliable message channels without
embedding game rules in the transport library.

Required areas include datagram sequence tracking, ACK and NACK ranges,
retransmission timing, reliable message indexes, ordered and sequenced channels,
split-packet reassembly, duplicate suppression, congestion and send-window
limits, disconnect handling, and wraparound-safe integer arithmetic. Queues,
fragments, retransmission work, and per-peer memory must all have explicit
limits.

The gate requires property and wraparound tests, deterministic virtual-network
tests under loss, duplication, delay, jitter, and reordering, malformed and
resource-exhaustion fuzzing, multi-session isolation, soak tests, and packet
capture comparison against independently sourced wire expectations. A stalled
or hostile peer must not exhaust global memory or starve healthy sessions.

## 5. Bedrock login and session security

Layer versioned Bedrock packet codecs and an explicit login state machine over
the transport without allowing network callbacks to mutate world state.

This milestone covers packet framing, compression negotiation, protocol-version
selection, login chain and skin-data validation, proof-of-possession checks,
encryption setup where required, resource-pack negotiation, timeouts, and
disconnect reasons. Size, nesting, decompression ratio, CPU, and state-transition
limits must be enforced before expensive work. Authentication modes and their
security consequences must be explicit configuration, not silent fallback.

The gate requires cryptographic known-answer tests, invalid and expired chain
tests, decompression-bomb and malformed-input tests, state-machine transition
coverage, replay and downgrade checks, secret-safe logging review, independent
client tests, and retail-client login qualification for every advertised
protocol version.

## 6. Minimal spawn, movement, and chat

Deliver a deliberately narrow playable slice: clients join a fixed world,
spawn, see peers, move, chat, disconnect, and reconnect. Inventory, crafting,
combat, persistence, advanced terrain, and broad gameplay rules may remain out
of scope.

The authoritative world loop consumes validated immutable commands from session
queues at a fixed tick rate. It owns player state and emits snapshots or events
for packet encoding. Movement validation must reject non-finite coordinates,
invalid rotations, stale or impossible state, and abusive rates. Chat must be
length- and rate-limited, preserve ordering and attribution, and follow the
configured moderation and logging policy.

The functional gate requires two retail clients to spawn in a fixed flat world,
see each other, exchange 100 ordered attributed chat messages, and move for ten
minutes without accumulating drift or server errors. A supported client must
complete 20 consecutive joins, and the server must complete 100 sequential
join/spawn/disconnect cycles without a ghost session. Twenty-five synthetic
clients must spawn, move, and chat for 30 minutes. After disconnect, memory must
return to within 10% or 32 MiB, whichever is greater, of the post-warmup
baseline. Under simulated 50 ms round-trip latency, 10 ms jitter, and 2% packet
loss, sessions must remain usable for 15 minutes; normal LAN movement
replication must remain below 150 ms at p95.

The fixed-world slice includes an on-demand flat generator, complete chunk
serialization, a bounded generated-chunk cache, and a per-player view manager.
The view manager caps the negotiated radius, prioritizes nearby chunks, and
schedules only newly visible chunks when a player crosses a chunk boundary.
Generation and delivery have separate per-tick budgets so initial spawn or
movement cannot monopolize the world loop. Bedriox-owned block-state IDs remain
inside world state and are translated to Bedrock runtime IDs only at the
protocol boundary.

## 7. Hardening and performance qualification

Convert functional success into repeatable operational confidence. Threat
models and limits must cover discovery amplification, handshake floods, parser
complexity, decompression, queues, fragments, chat, authentication, plugins,
and shutdown. Failures should isolate a client where possible and preserve
enough structured telemetry to diagnose the cause without exposing secrets or
personal data.

Benchmark methodology must publish hardware, operating system, PHP version,
configuration, workload, warmup, duration, client model, and raw results. Track
tick latency, join latency, movement latency, throughput, CPU, memory, queue
depth, retransmissions, and disconnect causes. Performance regressions require
an explicit reviewed exception, never a silent baseline reset.

The public-alpha gate requires 100 synthetic players for 60 minutes on the
documented reference system, with p95 tick duration below 25 ms, p99 below 40
ms, no sustained tick above the 50 ms budget, no crash or protocol corruption,
bounded queues, and post-warmup memory growth no greater than 5% per hour.
Security review, dependency audit, fuzzing, clean install/upgrade/rollback, and
graceful shutdown tests must also pass.

## 8. Extensions and public release

Expose a small, versioned extension surface only after the runtime boundaries
are stable enough to defend. Plugins should receive capability-oriented APIs
and immutable events rather than internal mutable objects. Lifecycle,
permissions, scheduling, resource budgets, dependency constraints, failure
isolation, and API compatibility policy must be documented before third-party
code is encouraged.

Release artifacts must be reproducible, checksummed, provenance-bearing,
installable without a source checkout, and paired with configuration examples,
upgrade and rollback instructions, compatibility declarations, known
limitations, and a security contact. A release candidate must pass all local
and cross-repository gates from a clean checkout, plus retail-client smoke tests
for each claimed platform and version.

The public-release gate requires a documented support window, semantic version
policy, deprecation process, plugin compatibility rules, signed or otherwise
verifiable artifacts, incident and disclosure procedures, and a completed
release checklist. Experimental interfaces remain clearly marked and outside
compatibility guarantees until promoted through an accepted RFC.

## Planned gameplay expansion sequence

After the minimal multiplayer and extension foundations satisfy their gates,
gameplay expansion follows this dependency order. This section records future
direction and does not claim that any listed behavior is currently supported.

### Runtime distribution reliability

Every supported Runtime target must package its OpenSSL provider configuration,
qualify the P-384/ES384 operations used by Bedrock login, and start reliably
from a relocated installation. Windows launchers must provide an external
OPcache file-cache fallback without writing mutable files into the immutable
runtime package. All artifacts must come from one Runtime commit and pass native
qualification before their hashes are recorded in Bedriox.

### Default world generation

The stable generator contract retains the flat generator as `flat`. New worlds
use `default` unless configured otherwise. The resolved generator identifier,
Bedriox algorithm version, and seed are persisted in world metadata; an
existing world is never silently converted to another generator, version, or seed.

The version-one `default` implementation provides domain-warped continents,
erosion-shaped mountains and valleys, climate-driven biomes, rivers, deep
oceans, slope-aware surfaces, snow and ice, bounded cross-chunk caves, regional
ore veins, boulders, biome-specific forests, negative-coordinate coverage, and
terrain-derived safe spawn selection. Saved LevelDB chunks take precedence and
only missing chunks are generated. Water and deep lava use non-solid collision
while dry spawn selection prevents false-underwater presentation. Generated
structures and Minecraft seed parity remain later expansions.

The automated gate covers seed determinism, generation-order independence,
negative-coordinate and unit-scale continuity, regional biome and elevation
distribution, unchanged flat output, bounded generation work, generator-version
rejection, and save/reload behavior. Final qualification requires retail travel
across multiple chunks followed by a restart that preserves both terrain and
player block changes.

### Player persistence

Store one bounded schema-versioned little-endian NBT record per authenticated
player UUID. Persist canonical inventory, selected slot, cursor, position,
rotation, world, game mode, timestamps, authenticated XUID, and last-known
name. Names, network runtime IDs, stack-network IDs, login tokens, skins, and
cryptographic material are never storage authority.

Loading occurs after authentication and before authoritative admission. Missing
records create a new player at the world's calculated spawn. A valid saved
position is restored exactly when its world is available; an unavailable world
uses that spawn without discarding the remaining profile. Corrupt, unreadable,
oversized, and unsupported records reject only the affected login and are not
overwritten. Dirty revisions are saved through bounded autosave, atomic
replacement, quit flush, and complete graceful-shutdown flush. Save failures
remain dirty for retry and do not affect unrelated players. The gate includes
malicious and corrupt inputs, identity mismatch, write failure, revision races,
two-player isolation, and retail restart checks for position, rotation, and
inventory.

### Multi-world lifecycle and generators

The active world milestone provides one authoritative runtime per loaded world,
lightweight generation-tagged handles, constant-time canonical lookup,
asynchronous deduplicated lifecycle operations, and atomic cross-world
teleportation. `Position` accepts optional world and orientation values so
same-world and cross-world destinations share one public value type.

The same milestone adds built-in `default`, `flat`, and `void` generator
definitions plus an owner-scoped plugin registrar. Built-ins run through
bounded workers. Plugin-defined generators currently use bounded main-thread
execution with immutable inputs; isolated worker admission remains a later
performance improvement.

Core does not add a `/world` command; plugins own administration and access
policy. See [worlds and teleportation](worlds-and-teleportation.md) and
[plugin world generators](plugin-world-generators.md). The public lifecycle
surface and plugin registrar are implemented in API 0.4; this section does not
claim qualified retail support.

### Remaining milestone order

The authoritative health milestone is complete. Bedriox owns bounded health,
fall distance and landing damage, cancellable damage, death-state
isolation, invulnerability, schema-versioned persistence, multiplayer
hurt/death projection, both qualified respawn input forms, full respawn state
resynchronization, and typed damage, death, and respawn plugin events. Inventory
remains server-owned and retained across death until world item entities exist.

Persistent transactional storage is now complete across chests, trapped
chests, barrels, shulker boxes, Ender Chests, block-entity persistence,
multiplayer windows, and the plugin container API. Authoritative processing
stations and experience now build on that foundation. Multi-world lifecycle,
teleportation, typed entity runtime, persistence, natural spawning, and staged
mob behavior are also present. Remaining gameplay work follows this dependency
order:

1. stabilize the public beta across installation, upgrades, supported clients,
   plugins, worlds, and recovery paths;
2. add redstone and automation that can power blocks, move items, operate
   processing stations, and build persistent machines;
3. deepen world generation, structures, decoration, swimming, currents, and
   environmental block behavior;
4. complete villagers, professions, trading, settlements, golems, patrols,
   illagers, and raids;
5. add Nether and End dimensions with portals, generation, travel, spawning,
   structures, and dimension-specific rules;
6. complete boats, minecarts, rail transportation, and remaining projectile
   gameplay;
7. support resource packs and plugin-defined client content through stable
   custom item, block, entity, sound, and particle boundaries;
8. qualify bosses and other stateful world encounters; and
9. repeat production profiling and long-running qualification across the
   completed systems before stable release claims.

### Post-beta development milestones

The public beta is the foundation for these milestones, not a claim that they
are already available. Their order may adjust when compatibility findings or
player testing expose a dependency, but each completed milestone must preserve
existing worlds, multiplayer authority, plugin isolation, and bounded server
work.

Redstone and automation will make farms, sorting systems, powered builds, and
automatic processing possible. Advanced world work will focus on more coherent
structures and decoration alongside natural movement through water and other
environments. Settlement gameplay will then connect villagers, professions,
trading, reputation, golems, patrols, and raids into persistent worlds.

Dimension work will introduce complete Nether and End environments and safe
portal travel. Transportation will add boats, minecarts, rail behavior, and the
remaining projectile journeys. Custom-content support will let plugins provide
the resources needed for original blocks, items, entities, sounds, and visual
effects without depending on internal protocol details.

Bosses and special encounters will build on those worlds, entities, effects,
and structures. Performance qualification continues throughout development and
finishes with sustained mixed workloads, upgrade testing, and recovery testing
before stable compatibility is declared.

The complete block-and-item milestone begins with an accepted, deterministic
current-version data bundle and full creative-catalog projection. Data admits
the immutable source-neutral runtime bundle after candidate review. Creative
completeness covers groups, entries,
variants, and safe authoritative selection. It does not by itself implement
every item's unique gameplay behavior. See
[versioned data and the creative catalog](data-and-creative-catalog.md).

The health milestone may define the authoritative result of a death inventory
transition, but visible world drops belong to the complete item-entity
milestone. Until then, a documented keep-inventory policy prevents fake drops,
silent item deletion, and duplication.

### Survival and administration

Health, damage, death, respawn, persistence, and their plugin events now build
on durable player records. Player command input, operators, permission nodes,
command feedback, and the sender-aware command API use the same authoritative
player identity. Neither plugins nor clients may bypass validation.

### Broader gameplay

Canonical blocks and items, partial collision shapes, block drops, placement
rules, equipment, ordinary item use, nutrition, durability, complete player
inventories, bounded world item entities, authoritative personal and table
crafting, persistent transactional containers, processing stations, and
experience now share one atomic authority model. Multiple independently
persisted worlds, per-world safe spawn, loading and unloading, teleportation,
and bounded plugin APIs provide the lifecycle foundation for advanced terrain.

Advanced generation then adds structures, richer decoration, and complete
breathing/swimming authority. Automation and remaining entity behavior keep
their own authority, performance, persistence, and plugin-event gates.

### Entity runtime and behavior

The accepted entity direction is delivered in dependency order. This roadmap
does not claim that any stage is implemented until its owning release notes,
tests, and compatibility evidence say so.

The foundation first adds stable vanilla and custom entity types, dedicated
mob implementations, animal and monster categories, shared capability
interfaces, lifecycle ownership, spatial indices, visibility, bounded ticking,
attributes, metadata, plugin registration, and the complete supported actor
packet conversation. Dropped items must retain their already-qualified
gravity, collision, pickup, persistence, and inventory behavior while moving
onto the shared runtime.

Explicit spawning follows through spawn eggs, `/summon`, and the plugin API.
Spawning is one authoritative transaction: a cancelled or failed spawn creates
no actor and consumes no egg, while a successful survival spawn consumes
exactly one server-owned egg. Catalog presence alone does not make a mob
playable; each admitted type needs a dedicated implementation and a complete
client conversation.

Entity persistence comes before natural spawning. Non-player entities belong
to their current chunk and retain canonical type, UUID, position, motion,
health, equipment, variant, and bounded custom state across autosave, unload,
restart, and cross-chunk movement. Unknown plugin-owned records remain dormant
and preserved rather than being silently deleted. Players remain in the
separate player-profile store.

Natural spawning then adds deduplicated player-centered candidate chunks,
category caps, local density, biome, light, surface or liquid, collision,
distance, and difficulty rules. Named, tamed, leashed, ridden, plugin-forced,
and otherwise persistent entities remain exempt from ordinary distance
despawning. `spawn-animals` and `spawn-monsters` become user settings only when
these rules are implemented and qualified.

Mob behavior is divided into six cumulative gates:

1. navigation: direct movement, walking, swimming, flying, climbing, jumping,
   doors, bounded paths, invalidation, caching, worker execution, and stuck
   recovery;
2. sensors, memories, and goals: typed state, deterministic priorities,
   conflicting-control exclusion, activation, wakeups, plugin extensions,
   fair budgets, and telemetry;
3. passive behavior: looking, wandering, avoidance, panic, temptation,
   following, and idle movement in each supported medium;
4. animal behavior: aging, breeding, parenting, taming, sitting, food
   preferences, durable state, and species interactions;
5. hostile behavior: targets, line of sight, pursuit, retaliation, melee,
   ranged attacks, cooldowns, group alerts, and multiplayer target changes; and
6. special behavior: individually qualified creepers, endermen, villagers,
   golems, aquatic and flying mobs, Nether families, bosses, and other
   current-version exceptions.

The first land-mob expansion checkpoints qualify sheep and skeleton, common
passive animals, and the common hostile families. The hostile checkpoint adds
dedicated zombie and skeleton variants, spiders, creepers, slimes, magma cubes,
endermen, endermites, silverfish, and witches with bounded explosions,
climbing, ranged status attacks, size splitting, natural spawning, loot,
projection, and persistence. Typed explosion and split events are connected to
their built-in mechanics; transform and entity-owned block-change pairs define
the boundary for later mechanics that invoke them. Tameable, neutral, mount,
villager, illager, golem, piglin, Nether-ground, and special land families
remain later cumulative checkpoints. The complete proposed land roster and
acceptance matrix are tracked by
[entity and custom-mob guide](entities.md).

Core physics, collision, damage, fire, air, persistence, and network projection
remain separate from intelligence. The advanced `entities.ai.enabled` setting
appears only with the first AI gate and disables decisions and navigation, not
those core lifecycle rules.

Performance is part of every behavior gate. Near-player or combat behavior is
active; loaded but less relevant mobs use reduced decision frequency; idle
distant mobs sleep with bounded wakeups; reviewed bosses or plugin behavior may
be forced active. Stable offsets stagger sensors and goal selection. Spatial
indices replace world-wide searches, immutable definitions are shared across
same-type mobs, and per-entity memory stays compact and typed.

Navigation first tries direct travel and reuses a valid path. Expensive searches
are bounded, cached, deduplicated, and retried with delay. Only immutable path
searches may use managed workers; mutable AI and entity state stay on the
simulation thread, and stale worker results are rejected by entity, target,
world, and chunk revision. Fair elapsed-time and operation budgets defer lower
priority work without deferring critical physics or damage.

The cumulative gate includes two-player actor visibility, every admitted spawn
egg, restart and cross-chunk persistence, bounded natural populations, mixed
terrain and water navigation, unreachable-target recovery, plugin-defined
entities and goals, large sleeping and active populations, worker saturation,
packet coalescing, memory recovery, and the existing tick and soak budgets.
`/status advanced` must attribute active, reduced, sleeping, and forced counts,
AI cost, deferred work, sensor and goal work, path results, cache behavior, and
bounded plugin ownership without exposing player identity or entity payloads.

### Measurement-driven scaling

Profile realistic joins, movement, generation, persistence, plugins, and entity
loads before introducing workers or new caches. Optimization must preserve
determinism, authority, cleanup, and failure isolation and must pass the
hardening and performance gates above with published reproducible evidence.

## Applying the roadmap

Milestone scope or gates may change through the
[contribution process](../CONTRIBUTING.md).
When that happens, update this roadmap, the relevant testing and compatibility
pages, and the owning implementation documentation together. The
[testing guide](testing.md) contains the shared client-journey and performance
expectations, while the [compatibility policy](compatibility.md) defines how
support claims are made.
