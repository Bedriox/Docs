# Architecture

Bedriox is split into narrowly owned repositories with one-way dependencies:

```text
Bedriox server
  |-- RakNet   UDP transport, sessions, reliability, ordering, recovery
  |-- Protocol Bedrock framing, login, encryption, and packet codecs
  `-- Data     immutable, hash-verified protocol data artifacts
```

The executable server is the composition root and the sole owner of mutable player and world state. Socket callbacks do not mutate the simulation directly.

## Runtime data flow

```text
UDP datagram
  -> bounded RakNet session processing
  -> bounded Bedrock frame and packet decoding
  -> authentication and encryption state machine
  -> immutable gameplay command
  -> authoritative 20 TPS simulation
  -> immutable gameplay event
  -> protocol encoding, batching, encryption, and RakNet delivery
```

Transport, protocol, and simulation queues have explicit limits. Invalid input is rejected at the owning boundary before it can affect state.

Console commands follow a separate non-network path:

```text
bounded non-blocking console input
  -> command parse and lookup
  -> sender and permission policy
  -> cancellable pre-dispatch event
  -> plugin-owned handler
  -> immutable post-dispatch event
```

Commands, aliases, and cooperative jobs remain owned by their plugin and are
released when it disables. The API models both console and player senders, but
this release does not route Bedrock slash-command packets into the dispatcher.
See [commands](commands.md).

Bedriox natively discovers signed PHAR plugins only. PluginTools may discover
development source projects and submit one bounded definition batch through
the public admission boundary; Bedriox never scans those directories itself.

## Login and play initialization

The runtime first establishes RakNet, then performs the Bedrock request-network-settings, login, encryption, resource-pack, and play-status sequence. Successful login enters an initialization stage rather than immediately publishing a player.

For the protocol-2193 path, the server sends StartGame; item, actor, and biome registries; the local player-list entry; chunk radius and publisher state; the fixed-flat spawn area; and `PLAYER_SPAWN` last. A matching serverbound local-player-initialized acknowledgement gates the authoritative join event.

Normal movement is accepted through the bounded PlayerAuthInput projection. Simulation positions are feet-based; the protocol adapter applies the Bedrock eye-height offset at the wire boundary. Text packets become bounded chat commands. Inventory stack requests and block intents become immutable commands; the simulation alone owns inventory transfers, prediction reconciliation, block mutation, and placement consumption. Simulation results are broadcast as protocol packets without giving network code ownership of player or world state.

Crafting follows the same ownership boundary. Data supplies immutable canonical
recipe records, Protocol represents the current wire catalog and bounded stack
actions, and the server builds the active recipe registry. The simulation owns
personal and crafting-table grids, matches only authoritative inputs, stages
plugin events, and commits or corrects the complete inventory transaction.
Recipe network IDs and client-provided result stacks never become gameplay
authority. See [crafting](crafting.md).

Persistent storage uses that same authority boundary. Canonical block entities
and player profiles own durable contents, while the simulation owns every live
window and atomically stages player and container revisions. Runtime code only
projects open, contents, affected-slot, block-state, and close events. Plugins
receive immutable views and bounded operations rather than internal inventories
or protocol identifiers. See [storage containers](storage-containers.md).

The simulation owns a capacity-bounded player registry indexed by session,
authenticated identity, and runtime actor ID. Multiplayer projection publishes
player-list membership at join, gates actor add and baseline metadata on each
recipient's sent chunk view, sends accepted visible-peer movement as absolute
actor movement with posture deltas, and removes the actor before its player-list
entry on disconnect. Event encoding or fan-out failure is isolated to the
session that caused the event when that owner is known. See
[player and multiplayer lifecycle](player-multiplayer.md).

## Data ownership

Data admits pinned artifacts through deterministic generation, hashes every output, and exposes validated values needed by the protocol layer. Protocol codecs must not invent registry IDs or treat unchecked client data as authoritative.

The expanded data pipeline keeps preparation outside the runtime.
Release maintainers use the access-restricted
[DataBuilder](https://github.com/Bedriox/DataBuilder) project to prepare,
verify, compare, approve, and publish a candidate for independent admission by
Data. Bedriox and Protocol consume only admitted Data APIs and never fetch or
transform upstream datasets during startup. See
[versioned data and the creative catalog](data-and-creative-catalog.md) for the
public lifecycle and current scope boundary.

See [known limitations](known-limitations.md) for intentionally incomplete gameplay behavior.

## World-generation pipeline

The world runtime replaces the fixed spawn packet loop with these separate
responsibilities:

```text
bedriox.settings
  -> validated level and chunk limits
  -> selected deterministic default or flat generator
  -> bounded generated-chunk repository and cache
  -> per-player nearest-first view queue
  -> Bedrock chunk serialization
  -> LevelChunk delivery
```

The authoritative Bedriox world uses process-local block-state IDs resolved
from canonical names such as `minecraft:grass_block`. These IDs are not a
storage or network format. At the packet boundary, Protocol translates
each state through the active `BlockNetworkTranslator` and serializes a
complete chunk column for the negotiated protocol.

Each player view tracks requested, queued, and sent chunk coordinates. Crossing
a chunk boundary schedules only newly visible chunks. Generation, delivery,
queue size, radius, and cache residency remain bounded, and nearby chunks are
sent first. Generation and sending consume separate per-world-tick budgets,
with completed generation held in a bounded staging queue. The default
generator stages continental/climate sampling, biome resolution, surface rules,
cave carving, regional ores, and vegetation before serialization. Persistent
generator-version metadata prevents unsupported algorithms from extending an
established world.

The multi-world architecture keeps exactly one heavy runtime for each
loaded world in a canonical `WorldManager` registry. Public values carry a
lightweight world handle and load-generation token rather than retaining the
provider, chunk cache, entities, or simulation. Cross-world teleport prepares
the destination and transfers authoritative membership as one transaction.
Built-in generation uses bounded workers. Plugin-defined generators use a
bounded main-thread fallback because arbitrary plugin code is not loaded into
core workers. Results remain subject to generator identity, chunk, and
lifecycle validation before installation. See
[worlds and teleportation](worlds-and-teleportation.md) and
[plugin world generators](plugin-world-generators.md). The public world
handles, lifecycle service, and owner-scoped plugin generator registrar are
part of API 0.3; cross-world retail qualification remains a release gate and
the implementation is not a retail compatibility claim.

This is private-alpha behavior and does not establish retail compatibility.
The cross-repository decision is tracked in
[RFC 0011](https://github.com/Bedriox/RFCs/blob/main/rfcs/0011-flat-world-configuration-and-streaming.md).

See the [repository map](repository-map.md) for layer ownership, the
[change-safety policy](change-safety.md) for preservation rules, and the
[client journey](client-journey.md) for the cumulative observable contract.
