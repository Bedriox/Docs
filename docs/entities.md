# Entities and custom mobs

Bedriox owns every entity's identity, position, motion, health, lifecycle, and
visibility. Client actor IDs, metadata, interaction actions, and spawn requests
are never world authority. The current entity slice provides explicit spawning,
durable non-player records, bounded natural populations, staged mob behavior,
and an experimental owner-scoped custom mob API.

## Built-in spawning

Operators with `bedriox.command.summon` may create an admitted entity with:

```text
/summon cow
/summon minecraft:zombie 120 72 -40
```

A player may omit the position to use their current position. The console must
supply coordinates. An unknown type, unloaded or invalid destination,
collision, cancelled event, or capacity limit rejects the complete spawn
without creating a partial actor.

Supported spawn eggs use the same authoritative path. Bedriox validates the
server-held egg and destination before creating the entity. A successful
survival spawn consumes exactly one egg; a rejected or cancelled spawn consumes
none and corrects the client prediction.

## Public entity views

`Entity`, `LivingEntity`, and `Mob` are read-only public capabilities. They
expose canonical type and category, stable UUID and runtime identity, world and
transform, persistence, health, alive state, and mob activation state.
Dedicated types such as `Cow` and `Zombie` support ordinary `instanceof`
checks. Plugins must not retain a view as proof that the entity is still loaded
or alive.

`EntityCategory` distinguishes animals, monsters, ambient, water, flying,
villager, and miscellaneous entities. `MobActivationState` reports active,
reduced, sleeping, or forced scheduling without exposing the scheduler or its
queues.

## Register a custom mob

Register custom definitions while the plugin is enabled. Identifiers must be
canonical, namespaced, and must not use the reserved `minecraft` namespace.
The vanilla appearance controls what an unmodified Bedrock client renders; it
does not change the custom type's server identity.

```php
use Bedriox\Api\Entity\CustomEntityType;
use Bedriox\Api\Entity\CustomMobDefinition;
use Bedriox\Api\Entity\EntityCategory;
use Bedriox\Api\Entity\VanillaEntityIdentifier;

$guide = new CustomEntityType('example:guide');
$entities = $this->context()->entities();
$entities->register(new CustomMobDefinition(
    $guide,
    new VanillaEntityIdentifier('minecraft:cow'),
    EntityCategory::ANIMAL,
    width: 0.9,
    height: 1.4,
    maximumHealth: 10.0,
    factory: static fn(): GuideBehavior => new GuideBehavior(),
    stateCodec: new GuideStateCodec(),
    maximumStateBytes: 64,
));
```

`networkAppearance` accepts a `VanillaEntityIdentity`. Dedicated implemented
types are available through `VanillaEntityType`; `VanillaEntityIdentifier`
selects another canonical identity from the active Data catalog. Registration
fails if that appearance is not an admitted built-in identity. The appearance
controls client rendering only and never aliases the custom canonical type.

The factory creates behavior for one server-owned mob. `onSpawn()` receives the
typed `SpawnCause`; `onTick()` handles bounded lifecycle work; `onAiTick()` runs
only when mob decisions are enabled and scheduled; and `onDespawn()` performs
instance cleanup. Hooks run on the authoritative simulation thread and must be
small, deterministic, and non-blocking.

A plugin may replace its own definition explicitly with `replace: true`, but
cannot claim another plugin's identifier or a built-in type. Replacement
creates a new definition generation for later spawns. Already-live mobs keep
the immutable behavior factory, state codec, dimensions, health contract, and
appearance generation with which they were created. This prevents an in-place
replacement from decoding old state with a new codec or changing a live actor's
shape. Disabling and later re-enabling a plugin creates a new owner epoch;
records remain dormant only while their definition is unavailable. A later
chunk activation uses the then-current definition, so replacement codecs must
retain explicit schema migration for state written before unload or restart.

Disabling the owner unregisters its definitions and lifecycle resources. A
factory, hook, or codec failure follows normal plugin failure isolation.

## Control a custom mob

`CustomMobTickContext::$controller` accepts at most 16 bounded intents during
one tick callback:

- `moveToward()` steers toward a bounded position at a finite speed from 0
  through 10;
- `lookAt()` updates orientation toward a bounded position;
- `target()` combines steering and looking at an entity's authoritative
  snapshot from that tick, provided it remains in the same world;
- `setVelocity()` requests finite velocity bounded to 100 blocks per tick on
  each axis; and
- `despawn()` requests authoritative removal after the callback commits.

```php
use Bedriox\Api\Entity\CustomMobTickContext;
use Bedriox\Api\World\Position;

public function onAiTick(CustomMobTickContext $context): void
{
    $position = $context->mob->getPosition();
    $target = new Position($position->x + 2.0, $position->y, $position->z);
    $context->controller->moveToward($target, 0.12);
    $context->controller->lookAt($target);
}
```

Controller calls are intents, not immediate mutations. Each factory, lifecycle,
AI, and state-codec callback owns one plugin transaction. Controller intents
and other staged plugin API calls commit together only if the callback returns
successfully and its owner activation is still available. An exception or
stale owner discards the complete batch before normal plugin failure isolation;
part of a callback can never leak into authoritative state.

## Spawn a plugin-owned type

The owner may request a spawn after registering the definition:

```php
use Bedriox\Api\World\Position;

$this->context()->entities()->spawn(
    $guide,
    new Position(100.5, 70.0, -24.5),
    yaw: 90.0,
    pitch: 0.0,
);
```

This is bounded intent, not direct registry mutation. Bedriox revalidates the
world, loaded chunk, coordinates, collision, capacity, ownership, and spawn
event before committing the entity. A plugin may spawn only a custom type that
it currently owns.

## Persistent custom state

Persistent custom mobs may attach one opaque `CustomEntityState` produced by
their definition's `CustomMobStateCodec`. The state has a schema version and a
bounded byte payload. The default definition limit is 16 KiB and no definition
may exceed the API's 64 KiB hard limit.

Use a compact documented format and reject unsupported schema versions or
malformed values. Do not serialize PHP objects, closures, resources, player
objects, or server internals. Bedriox stores the payload without interpreting
it and invokes the owning codec when that definition is available. Unknown
plugin-owned records remain dormant rather than being reinterpreted as another
type.

## Typed events

The entity event surface includes:

- `EntitySpawnEvent`: cancellable after core validation and before admission;
- `EntitySpawnedEvent`: immutable observation after the actor exists;
- `EntityDespawnEvent`: cancellable ordinary despawn intent;
- `EntityDespawnedEvent`: immutable observation after removal;
- `EntityInteractEvent`: cancellable player interaction with a typed
  `EntityInteractionType`;
- `EntityDamageEvent`: cancellable damage with a bounded mutable amount;
- `EntityDamageByEntityEvent`: damage plus its typed player or entity source;
- `EntityDeathEvent`: immutable death observation with the last damage event.

Cancellation rejects the requested mutation through the authoritative path; it
does not bypass validation or grant direct access to actor packets. Post-events
are observational, and `MONITOR` listeners remain read-only.

```php
use Bedriox\Api\Event\Entity\EntityInteractEvent;
use Bedriox\Api\Event\EventHandler;

#[EventHandler]
public function onEntityInteract(EntityInteractEvent $event): void
{
    if ($event->entity->getType()->identifier() !== 'example:guide') {
        return;
    }

    $event->cancel();
    $event->player->sendMessage('The guide noticed you.');
}
```

The first-party
[ExamplePlugin](https://github.com/Bedriox/ExamplePlugin) demonstrates the
complete registration, lifecycle, state-codec, spawn-command, and event path.

## Operational boundaries

`spawn-animals=true` and `spawn-monsters=true` in `server.properties` control
only natural spawning in those categories. Spawn eggs, `/summon`, and
plugin-requested spawns remain available when either setting is false.
`difficulty=peaceful` independently prevents natural monster spawning.

`entities.ai.enabled=false` disables mob decision and navigation work. It does
not stop entity physics, collision, damage, lifecycle ticks, persistence, or
network visibility. Custom `onAiTick()` hooks are skipped, while `onTick()`
continues.

Entity state remains part of world persistence, separate from player profiles.
Graceful shutdown flushes loaded entity ownership with world data. Complete
species-specific behavior, bosses, breeding and taming, projectiles, and status
effects remain later gameplay work; catalog admission or a rendered vanilla
appearance does not imply those mechanics are complete.
