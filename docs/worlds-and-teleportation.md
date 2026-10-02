# Worlds and teleportation

> **Experimental API 0.4:** These interfaces exist in the current development
> implementation, but Bedriox has not completed cross-world retail
> qualification. This page describes the public API contract, not a retail
> compatibility claim.

Bedriox supports multiple independently persisted worlds without making world
administration a built-in command system. Core owns loading, saving, unloading,
isolation, and teleport safety. Plugins decide how operators
and players create or select worlds.

The architectural contract is tracked by
the [roadmap](roadmap.md).

## World identity

Every world has two different names:

- a canonical world ID used by APIs, player records, and the directory below
  `worlds/`; and
- a display name stored in `levelname.txt` for presentation.

Canonical IDs are unique and path-safe. Display names are not identifiers and
may repeat. A plugin must resolve a world by canonical ID rather than scanning
display names.

One heavy runtime owns a loaded world's LevelDB provider, chunks, entities,
containers, simulation, generator, and caches. `WorldManager` is its only
long-lived owner. Public APIs receive a shared lightweight `World` handle, not
the heavy runtime. The handle includes a load-generation token, so a value
retained after unload cannot accidentally address a later world loaded under
the same canonical ID.

## Lifecycle API

The public `Server::getWorldManager()` service provides constant-time
loaded-world lookup and queued storage operations:

```php
use Bedriox\Api\World\WorldCreationOptions;
use Bedriox\Api\World\WorldUnloadOptions;

$worlds = $this->context()->server()->getWorldManager();

$default = $worlds->getDefault();
$arena = $worlds->get('arena');
$loadedWorlds = $worlds->getLoaded();

$create = $worlds->create('new-arena', new WorldCreationOptions(generator: 'flat'));
$load = $worlds->load('archived-arena');
if ($arena !== null) {
    $save = $worlds->save($arena);
    $unload = $worlds->unload($arena, new WorldUnloadOptions(save: true));
}
```

Creation options describe the generator, seed, generator-specific options,
display name, difficulty, initial time, and optional spawn. When the spawn is
omitted, the generator calculates it.

Create, load, save, and unload return `WorldOperation`. An operation exposes
its type, canonical world ID, current state, terminal result, cancellation, and
an `onComplete()` callback. Completion callbacks run exactly once on the main
server thread and are never called inline by `onComplete()`. Equivalent pending
operations for the same world and operation type share one handle instead of
opening a second provider.

Failures use bounded `WorldOperationFailureCode` values such as `NOT_FOUND`,
`STALE_HANDLE`, `DEFAULT_WORLD`, `OCCUPIED`, `STORAGE`, and `GENERATOR` rather
than exposing provider exceptions or private paths.

Only the world configured by `level.name` loads automatically at startup.
Plugins explicitly load additional worlds. The default world cannot be
unloaded through the ordinary public API. A populated non-default world must
be emptied before unload; the current `WorldUnloadOptions` controls whether the
world is saved and does not perform player evacuation automatically.

Core does not provide a `/world` command. Server owners may install or develop a
plugin that exposes commands, menus, aliases, access rules, presets, or
minigame-specific world workflows.

For development qualification only, the local untracked `TestFeatures` plugin
may register this command surface:

```text
/world list
/world info [world]
/world create <world> [default|flat|void|test] [seed]
/world load <world>
/world save <world>
/world unload <world>
/world tp <world> [player]
```

The `test` preset exercises a deterministic custom generator. This local
command reports queued completion and lifecycle failures, does not delete world
data, and cannot unload an occupied world. It is a test harness, not a bundled
Bedriox command or a compatibility promise.

## Lifecycle events

API 0.4 exposes validated pre-events and committed post-events:

- `WorldCreateEvent` and `WorldCreatedEvent`;
- `WorldLoadEvent` and `WorldLoadedEvent`;
- `WorldSaveEvent` and `WorldSavedEvent`; and
- `WorldUnloadEvent` and `WorldUnloadedEvent`.

An ordinary pre-event may cancel its operation. A post-event observes an
already committed result and cannot reverse it. Shutdown drains and recovery
cleanup remain non-cancellable so a plugin cannot prevent required storage
closure.

Event values do not expose LevelDB providers, mutable chunk collections,
internal queues, filesystem paths, or heavy world runtimes.

## `Position` contract

`Position` represents coordinates and an optional destination world and
orientation:

```php
new Position(
    x: 100.5,
    y: 70.0,
    z: -25.5,
    yaw: null,
    pitch: null,
    world: null,
);
```

For entity operations such as teleportation:

- omitted world means the entity's current world;
- omitted yaw preserves its current yaw;
- omitted pitch preserves its current pitch; and
- a supplied world must be a currently loaded handle generation.

The operation resolves omitted values from one authoritative entity snapshot.
Positions returned by players, entities, and events are fully resolved. A
`BlockPosition` remains integer X/Y/Z block coordinates and does not carry
world or rotation.

Examples of this convenience are:

```php
// Same world and orientation.
$player->teleport(new Position(100.5, 70.0, -25.5));

// Same world with a new orientation.
$player->teleport(new Position(100.5, 70.0, -25.5, 90.0, 0.0));

// Another loaded world while preserving orientation.
$player->teleport(new Position(
    100.5,
    70.0,
    -25.5,
    world: $arena,
));
```

## Cross-world transition behavior

A cross-world teleport is one authoritative transaction. Bedriox prepares
the destination view, closes transient windows and interactions, removes the
source view, changes world membership once, resets client movement and terrain
state, publishes destination state, and restores destination-world visibility.

Inventory, equipment, health, hunger, effects, game mode, identity, and the
network connection remain attached to the player. Fall distance and movement
prediction reset. Blocks, containers, entities, sounds, particles, and chunks
are visible only within their world. Chat and player-list membership remain
global unless a plugin applies a separate policy.

Plugins may deliberately choose a destination inside a solid block. Bedriox
validates finite coordinates, world bounds, and current world ownership, but it
does not replace plugin policy with an automatic safe-location search.

The built-in `/tp <player>` command will follow the destination player into
another loaded world. It will not gain world creation, loading, or named-world
selection syntax.

## Persistence and restart

A player profile stores canonical world ID, X/Y/Z, yaw, and pitch as one
destination. A returning player is restored there only when that world has
already been loaded. Login does not block while implicitly opening a world from
a profile. If the saved world is unavailable, the player enters the default
world at its calculated spawn while inventory and identity history remain
intact.

Each world keeps its own LevelDB data, generator metadata, chunks, containers,
entities, spawn, time, and difficulty. Saved data from one world must never be
looked up through another world's runtime or cache.

## Qualification boundary

API 0.4 and the current implementation include multi-world lifecycle,
world-aware positions, and cross-world teleportation. The public compatibility
arrays remain empty, and repeatable cross-world retail, restart, loss, soak,
and scale qualification remains outstanding. See
[known limitations](known-limitations.md) and [testing](testing.md) before
production use.
