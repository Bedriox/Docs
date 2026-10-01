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

## Land-mob qualification

Canonical catalog identity and client rendering do not by themselves establish
species support. Cow, sheep, pig, chicken, rabbit, zombie, skeleton, the
common-hostile roster, and the tameable, neutral, and mount families documented below have dedicated gameplay
implementations. Other catalog-backed actors remain baseline entity admission,
not a claim of complete vanilla behavior. See
[roadmap](roadmap.md)
for the broader proposed roster and acceptance gates.

The coordinated public API for this development slice uses
`Vanilla\Sheep`, `Vanilla\Skeleton`, `VanillaEntityType::SHEEP`, and
`VanillaEntityType::SKELETON`. Sheep implements the `Animal`, `Ageable`,
`Breedable`, and `Shearable` capabilities. Its immutable view exposes
`isBaby()`, `isSheared()`, and `getWoolColor()`; `SheepController` provides
bounded `setBaby()`, `setSheared()`, and `setWoolColor()` intents using the
typed `WoolColor` enum. Skeleton implements `Monster`, `Undead`, and
`RangedMob` and uses the ordinary mob controller.

## Common passive animals

Cow, sheep, pig, chicken, and rabbit now use one authoritative age and
breeding lifecycle. Adults can be tempted and bred with their species food;
babies inherit their species identity, grow on a bounded timer, and accept a
growth boost from the same food. A successful breeding commit creates one
baby, places both parents on cooldown, and awards a bounded experience result.
`EntityBreedEvent` may cancel the child or adjust that experience before the
spawn, while `EntityBredEvent` observes the committed parents and child.

Cows accept wheat and fill a held bucket with milk. Pigs accept carrots,
potatoes, and beetroot and retain saddle state. Chickens accept the supported
seed families, fall slowly, and lay eggs on a durable bounded timer. Rabbits
accept carrots, golden carrots, and dandelions, retain a typed
`RabbitVariant`, and use hopping ground movement. Sheep keep their existing
wheat, wool, dyeing, shearing, and grazing behavior on the shared lifecycle.
Adult death drops, baby suppression, burning conversions, saddle returns, and
species variants are server-owned.

## Riding and passengers

Adult saddled pigs are rideable. Interact with a saddled pig to mount it, hold
a carrot on a stick to steer, and use the ordinary client dismount control to
leave it. Movement, collision, seat ownership, and dismount placement remain
server-authoritative. Nearby players and players who join later receive the
same vehicle relationship.

Plugins can inspect `Entity::getVehicle()`, `Entity::getPassengers()`,
`Player::getVehicle()`, and `Player::isRiding()`. A live player can request a
bounded transition with `Player::mount()` or `Player::dismount()`. The typed
pre-events `EntityMountEvent` and `EntityDismountEvent` are cancellable for
ordinary interaction and plugin requests; their past-tense counterparts
observe successful commits. Death, despawn, disconnect, teleport, and world
change always clear the relationship and cannot be cancelled.

Mount links are transient. Restarting the server does not restore a player or
entity as a passenger.

Horse, donkey, mule, camel, llama, trader-llama, skeleton-horse, and
zombie-horse actors use the same mount registry. Their durable entity state
retains ownership, temper, saddle state, and age or breeding state where the
species supports it. Camels expose two seats. Llamas may carry a rider after
taming but are not directly steered; controllable mounts require a saddle.
Mounted movement, jumping, collision, dismounting, visibility, and late-join
actor links remain authoritative.

## Tameable and neutral families

Wolves and cats expose `Tameable` and `Sittable`; wolves additionally expose
`Angerable`. Ownership is stored as the authenticated player's canonical UUID.
Bones tame wolves, raw cod or salmon tame cats, and one interaction performs at
most one action or item consumption. Owners can toggle sitting with an empty
hand. Sitting suppresses AI movement, while a standing companion follows its
online owner. A wolf damaged by a non-owner remembers and attacks that player
for a bounded duration.

`EntityTameEvent` is the cancellable validated intent and `EntityTamedEvent`
observes the committed owner. `TameableAnimalController` provides bounded
owner and sitting mutation; `WolfController` also provides anger-target
mutation. The read-only species contracts and controllers remain independent
of protocol actor IDs and packet metadata.

Ocelots, foxes, goats, pandas, polar bears, armadillos, mooshrooms, and
sniffers have dedicated classes, exact identities, durable typed state,
projection, loot, explicit spawning, and natural spawning where the current
world can satisfy their rules. Goats and mooshrooms can be milked; mooshrooms
also fill bowls with stew. Village-, trader-, structure-, and event-owned
spawns are not guessed by the ordinary natural-spawn table.

Advanced species actions such as fox pouncing and item carrying, goat ramming,
panda activities, armadillo scute production, mooshroom shearing, sniffer
digging, and complete mount inventory screens remain later qualified behavior.

The plugin API separates responsibilities explicitly:

- `Api\Entity\Vanilla` contains read-only species contracts;
- `Api\Entity\Capability` contains reusable markers and state contracts;
- `Api\Entity\Controller` contains staged mutation gateways; and
- concrete built-in entity classes remain internal under `Server\Entity\Vanilla`.

Spawn eggs, `/summon`, natural spawning, chunk unload/reload, and restart all
use the same exact definitions and persistence codecs. Catalog presence alone
still does not qualify any other species.

## Common hostile mobs

The dedicated common-hostile roster is husk, zombie villager, stray, bogged,
parched, wither skeleton, spider, cave spider, creeper, slime, magma cube,
enderman, endermite, silverfish, and witch. Each has an exact
`VanillaEntityType` case, read-only species contract, built-in implementation,
dimensions, health, spawn-egg identity, multiplayer projection, persistence
path, and loot behavior.

Spiders and cave spiders implement `Climbing`; other living entities do not
advertise climbing. Slimes and magma cubes expose the finite `SlimeSize`
values `SMALL`, `MEDIUM`, and `LARGE`. Their dimensions, health, network
variant, persistence, and bounded death splitting follow that value. Creepers
expose charged, ignited, and fuse state and use a loaded-terrain-only bounded
explosion planner. Endermen take water damage. Magma cubes are fire-immune.

`CreeperController` extends ordinary mob control with `setCharged()` and
`setIgnited()`. It remains bounded, availability-checked, and owner-attributed
like the rest of the entity API. Enderman carried-block mutation remains
withheld until its canonical state can be projected exactly to the client.

Ranged special attacks use the existing authoritative projectile runtime:
strays and bogged launch their supported tipped arrows, witches launch splash
potions, and cave-spider melee applies difficulty-sensitive poison. Daylight,
equipment, damage, effects, drops, death, and removal still pass through the
ordinary entity authority and event boundaries.

Natural hostile spawning retains the shared regional cap, local-density limit,
cadence, loaded-terrain checks, fair budget, and distance-despawn ownership.
The natural table includes only species whose current world and environment
provide their required context. Nether-only, structure-only,
infestation-created, and transformation-created mobs remain explicit spawns
until those systems can supply that context.

## Aquatic mobs

Cod, salmon, tropical fish, pufferfish, squid, glow squid, dolphins, turtles,
axolotls, drowned, and guardians use registered built-in definitions rather
than packet-only actors. Their movement is three-dimensional in loaded water,
while the ordinary swept collision resolver prevents movement through solid
terrain. Missing chunks stop movement and never trigger synchronous terrain
generation.

Swim headings are retained and revalidated against the loaded water volume.
Surface swimmers steer back into navigable water, water-only mobs stop their
own propulsion when displaced onto land, and amphibious mobs use horizontal
ground navigation outside water. Ordinary land-mob AI treats nearby water as
undesirable terrain, while mobs pushed or dropped underwater still consume
their bounded air supply and take cancellable drowning damage after it expires.

The public `Aquatic` capability exposes whether an entity breathes underwater,
whether it depends on water, and its current and maximum air supply. Air loss,
recovery, dry time, drowning or stranding damage, death, drops, and removal are
owned by the simulation. Damage remains visible to the ordinary cancellable
entity-damage event before it commits.

Passive water mobs use a separate bounded `WATER` population category.
Drowned and guardians remain monsters, so peaceful difficulty and hostile
population rules continue to apply. Natural water spawning requires a loaded,
stable, collision-free water position and remains constrained by world and
local caps, player distance, attempt count, and elapsed-time budgets.

Supported fish and axolotls can be captured with a water bucket and released
from their filled bucket. Capture first passes through the ordinary
`EntityInteractEvent`; release uses `SpawnCause::BUCKET` and the normal spawn
event, persistence, visibility, and capacity boundaries. Spawn eggs,
`/summon`, plugin spawning, natural spawning, and chunk restoration use the
same definitions and controllers.

Turtles accept seagrass and axolotls accept tropical-fish buckets through the
shared breeding lifecycle. Species drops use the same once-evaluated,
catalog-validated loot pipeline and remain adjustable through
`EntityDeathEvent`. Species-specific variants, turtle nesting, dolphin
treasure guidance, and the guardian beam presentation are not claimed as
complete vanilla parity in this slice.

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
- `EntityEquipmentChangeEvent` and `EntityEquipmentChangedEvent`: cancellable,
  adjustable equipment intent and immutable committed equipment state;
- `EntityTargetEvent` and `EntityTargetChangedEvent`: cancellable target intent
  with an adjustable `Player|Entity|null` target, typed `EntityTargetReason`,
  and immutable committed target state;
- `EntityShearEvent` and `EntityShearedEvent`: cancellable validated shearing
  with a bounded replaceable drop list, followed by the immutable committed
  player, entity, tool, and drops;
- `EntityBreedEvent` and `EntityBredEvent`: cancellable breeding with bounded
  experience followed by the committed parents and child;
- `EntityTameEvent` and `EntityTamedEvent`: cancellable ownership intent
  followed by the committed tameable entity and owner;
- `EntityExplosionPrimeEvent` and `EntityExplodedEvent`: cancellable and
  adjustable bounded explosion intent followed by the committed radius,
  block policy, fire chance, up to 4,096 unique affected blocks, and up to 256
  affected actors; the adjustable radius cannot exceed 16 blocks;
- `EntitySplitEvent`: cancellable size-family splitting with an adjustable
  child count bounded from 1 through 16;
- `EntityTransformEvent` and `EntityTransformedEvent`: cancellable and
  adjustable canonical replacement intent followed by the committed original,
  replacement, and typed reason;
- `EntityBlockChangeEvent` and `EntityBlockChangedEvent`: cancellable and
  adjustable same-position block replacement intent followed by the committed
  blocks and typed reason;
- `EntityDeathEvent`: non-cancellable death observation with the last damage
  event and a bounded, replaceable drop list;
- `ProjectileLaunchEvent` and `ProjectileLaunchedEvent`: cancellable launch
  from a `Player|LivingEntity` shooter with bounded mutable motion and
  immutable committed launch state; and
- `ProjectileImpactEvent` and `ProjectileImpactedEvent`: cancellable resolved
  player, entity, or block impact and immutable committed impact state.

Cancellation rejects the requested mutation through the authoritative path; it
does not bypass validation or grant direct access to actor packets. Post-events
are observational, and `MONITOR` listeners remain read-only.

The common-hostile runtime currently emits the explosion and split events.
The transform and entity-owned block-change pairs establish the public boundary
for mechanics that invoke them; their presence does not by itself claim zombie
curing, enderman block movement, or another unimplemented transformation.

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
advanced species behavior, bosses, and the remaining projectile families
remain later gameplay work; catalog admission or a rendered vanilla appearance
does not imply those mechanics are complete.
