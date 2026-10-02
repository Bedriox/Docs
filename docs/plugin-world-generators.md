# Plugin world generators

> **Experimental API 0.4:** The owner-scoped generator registrar exists in the
> current development implementation. Generator and cross-world retail
> qualification remains open, so this contract is not a retail compatibility
> claim.

The generator foundation supports deterministic built-in and plugin-defined
terrain. Built-in generators use the managed-worker chunk pipeline. Plugin
generators currently use a bounded main-thread fallback because arbitrary
plugin code is not admitted into core workers.

The decision and acceptance requirements are tracked by
the [world lifecycle and teleportation guide](worlds-and-teleportation.md).

## Registry and identity

World creation accepts these built-in short names:

- `default` for the Bedriox overworld generator;
- `flat` for the fixed flat profile; and
- `void` for an empty world with a calculated or explicitly selected spawn.

Plugins use canonical namespaced identifiers such as
`example:checkerboard`. A plugin cannot use the built-in namespace, replace a
built-in definition, or replace another plugin's definition.

A public plugin definition contains:

- a stable namespaced identifier;
- an implementation version from 1 through 65535; and
- a final, stateless generator class with a zero-argument constructor.

Register definitions through `PluginContext::generators()`:

```php
use Bedriox\Api\World\Generator\GeneratorDefinition;

$this->context()->generators()->register(new GeneratorDefinition(
    identifier: 'example:checkerboard',
    version: 1,
    generatorClass: CheckerboardGenerator::class,
));
```

Each plugin may own at most 16 definitions. It may explicitly replace its own
definition, but it cannot replace a built-in or another plugin's definition.
All of its definitions are removed when the plugin is disabled.

World metadata stores the identifier, version, seed, and normalized options.
It never stores a PHP object, closure, local path, or plugin service container.

## Generator contract

A plugin generator implements `Generator`. Its `generate()` method receives an
immutable `GeneratorContext` and `ChunkPosition`; `defaultSpawn()` calculates
the world's initial spawn. The context contains the seed, canonical dimension,
and normalized generator options. It contains no live world, registry, or
server object.

The generator returns a `GeneratedChunk` containing bounded sections, palette
indices, canonical block states, and a namespaced biome. Generator code does
not receive a player, entity, session, socket, command map, event bus, LevelDB
provider, mutable server service, wall clock, or process-global random state.

Plugin generator classes must be final, stateless, and constructible without
arguments. Generator options accept only bounded scalar, list, and string-keyed
map values: at most eight levels, 256 values, 4096 bytes per string, and 32768
encoded bytes.

## Execution model

Built-in definitions are worker-backed. The worker coordinator deduplicates
pending requests by chunk position, caps pending work, verifies task receipts,
decodes bounded canonical chunks, and rejects a result whose coordinates do not
match its request.

Plugin definitions deliberately use a bounded main-thread execution path until
Bedriox has a reviewed mechanism for loading plugin code into isolated workers.
They remain constrained by the world's bounded generation and chunk-streaming
budgets. Registering a plugin generator does not grant worker, filesystem,
network, or mutable-server access. Expensive plugin generation can consume the
simulation tick budget, so plugins should keep each chunk calculation small and
measure it under realistic travel.

## Determinism

For identical declared inputs, output must remain the same across:

- generation request order;
- server restart;
- repeated requests; and
- positive or negative chunk coordinates.

Random choices derive from the declared world seed and chunk coordinates. A
generator must not depend on wall-clock time, request order, mutable static
variables, network access, or undeclared neighboring state.

## Plugin lifecycle

The public registrar derives ownership from the active `PluginContext`;
plugins do not supply or impersonate an owner name. Existing chunks remain
valid world data when a definition is removed; they are not deleted or
regenerated.

A loaded world that still needs its disabled custom generator cannot generate
new chunks. Bedriox reports the unavailable generator instead of silently
falling back to `default`, `flat`, or a newer registration. Operators must
restore the exact generator or avoid entering ungenerated terrain.

Replacing a definition affects only worlds that explicitly use the matching
identifier and implementation version. It must not reinterpret established
world metadata or silently change terrain at chunk boundaries.

## Failure and resource limits

A generator exception fails its current bounded request and is attributed to
the owning plugin and generator identity. A malformed, oversized, stale, or
contradictory result is rejected before it can modify authoritative world
state.

The scheduler applies world and server limits for generation demand, queued
chunks, bytes, and per-tick work. Plugin generation cannot monopolize core
workers because it does not run in them, but expensive plugin code can still
harm tick latency and must be measured before deployment.

Seeds, complete generator options, chunk contents, private paths, and arbitrary
plugin state are excluded from ordinary telemetry and crash summaries.

## Qualification expectations

A plugin generator is not considered supported merely because it produces a
chunk. Qualification covers:

- registration, ownership cleanup, and replacement isolation;
- option validation and metadata persistence;
- deterministic positive and negative coordinates;
- generation-order independence;
- save, unload, load, and restart;
- plugin exception and invalid-result isolation;
- plugin disablement and exact-version recovery;
- multiplayer chunk visibility and block persistence; and
- sustained generation without violating the established 20 TPS budget.

Until those gates pass, this API 0.4 surface is not a retail support claim.
Operators should continue using the
built-in `default` or `flat` setting documented in
[configuration](configuration.md) for production-like qualification.
