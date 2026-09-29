# Effects, particles, potions, and brewing

Bedriox exposes status effects as authoritative gameplay state and particles as bounded presentation. Plugins use canonical typed values; numeric effect IDs, packet IDs, sockets, and raw packet payloads remain outside the gameplay API.

## Reading and changing effects

Every public player and living-entity view exposes an `EffectManager` through `getEffects()`. Its `all()`, `get()`, and `has()` methods read an immutable snapshot. `add()`, `remove()`, and `clear()` submit bounded authoritative work for that live player or entity generation.

```php
use Bedriox\Api\Effect\EffectCause;
use Bedriox\Api\Effect\EffectInstance;
use Bedriox\Api\Effect\EffectType;

$effects = $player->getEffects();
$effects->add(new EffectInstance(
    EffectType::SPEED,
    durationTicks: 20 * 10,
    amplifier: 0,
), EffectCause::PLUGIN);

if ($effects->has(EffectType::POISON)) {
    $effects->remove(EffectType::POISON, EffectCause::PLUGIN);
}
```

Durations use simulation ticks. At 20 TPS, 200 ticks is ten seconds. Amplifier zero is effect level one. `visible`, `ambient`, and `infinite` describe the client presentation and lifetime without exposing protocol flags. The current Bedrock packet does not provide an independent effect-icon visibility bit.

The manager implements replacement, extension, expiration, and stronger-effect fallback rules. Active player effects are synchronized to clients and persisted with player state. A retained manager belongs to the session or entity generation that created it; it cannot mutate a replacement connection or entity.

The behavior layer applies movement speed and slowness, haste and mining fatigue, strength and weakness, resistance, instant health and damage, jump boost, regeneration, poison, fatal poison, wither, hunger, saturation, fire resistance, water breathing, health boost, absorption, levitation, slow falling, invisibility, and conduit power to their authoritative gameplay domains. Nausea, blindness, night vision, and darkness are client presentation effects. Bad Omen, Trial Omen, and Village Hero retain typed state and presentation, while their encounter systems remain outside the current world simulation.

## Effect events

`EntityEffectAddEvent` is cancellable and may replace the proposed `EffectInstance` without changing its type. `EntityEffectRemoveEvent` is cancellable for an explicit removal. The corresponding `EntityEffectAddedEvent` and `EntityEffectRemovedEvent` are immutable committed notifications.

```php
use Bedriox\Api\Effect\EffectInstance;
use Bedriox\Api\Effect\EffectType;
use Bedriox\Api\Event\Entity\EntityEffectAddEvent;
use Bedriox\Api\Event\EventHandler;

#[EventHandler]
public function limitSpeed(EntityEffectAddEvent $event): void
{
    if ($event->effect()->type !== EffectType::SPEED
        || $event->effect()->amplifier <= 1) {
        return;
    }

    $effect = $event->effect();
    $event->setEffect(new EffectInstance(
        $effect->type,
        $effect->durationTicks,
        amplifier: 1,
        visible: $effect->visible,
        ambient: $effect->ambient,
        infinite: $effect->infinite,
    ));
}
```

The event cause is a typed `EffectCause`, such as `PLUGIN`, `COMMAND`, `FOOD`, `MILK`, `DEATH`, or `EXPIRATION`. Plugin callbacks retain the normal transactional rule: staged actions commit only after the callback returns successfully.

## Operator commands

Operators with `bedriox.command.effect` can add, remove, or clear effects without using numeric protocol IDs:

```text
/effect xZeroOfficial speed 30 1 false
/effect xZeroOfficial night_vision infinite
/effect xZeroOfficial clear speed
/effect xZeroOfficial clear
```

Durations are seconds at the command boundary, while amplifiers remain zero-based. The last boolean controls whether effect particles are hidden.

The player-only `/particle` command requires `bedriox.command.particle` and uses the executing player's loaded world:

```text
/particle minecraft:heart_particle
/particle minecraft:basic_flame_particle ~ ~1 ~
```

The particle name must be an exact `ParticleType` string value. Friendly
aliases such as `minecraft:heart` and `minecraft:flame` are not accepted, and
an unknown name fails without spawning a particle. Command requests enter the
same bounded effect and world-action paths used by plugins.

## Spawning particles

Use the world that owns the position. With no explicit audience, Bedriox selects connected players in that world who have already received the containing chunk.

`ParticleType` contains the complete set of 116 named particle resources supported by the pinned Bedrock client. These use `SimpleParticle`; callers choose a canonical `minecraft:*` identity rather than a numeric protocol value.

```php
use Bedriox\Api\World\Particle\ParticleType;
use Bedriox\Api\World\Particle\SimpleParticle;

$world->spawnParticle(
    $position,
    new SimpleParticle(ParticleType::HEART),
);
```

Pass player snapshots to narrow delivery. Bedriox still removes stale sessions, players from another world load, and players that have not received the chunk.

```php
$world->spawnParticle(
    $player->position,
    new SimpleParticle(ParticleType::FLAME),
    [$player],
);
```

Some named effects accept bounded MoLang variables through `ParticleVariables`:

```php
use Bedriox\Api\World\Particle\ParticleVariables;

$particle = new SimpleParticle(
    ParticleType::SPLASH_SPELL,
    new ParticleVariables([
        'variable.tint_r' => 1.0,
        'variable.tint_g' => 0.25,
        'variable.tint_b' => 0.5,
    ]),
);
```

Variable names use the `variable.*` namespace and scalar values. The variable count, encoded payload, target list, authoritative command queue, per-source work, per-world work, and final packet fan-out are bounded. A particle is presentation only: it does not apply an effect, damage an entity, change a block, or authorize client state.

Bedrock also has data-backed particles that cannot be represented by a resource identifier alone. Bedriox exposes these through separate typed values:

- `StandardParticle` for level particles without additional data;
- `ScalarParticle` for scale, lifetime, and force-field values;
- `ColoredParticle` with bounded RGBA channels;
- `BlockParticle` with a canonical `ParticleBlockState` and a required face for punch particles;
- `ItemBreakParticle` with a canonical API item stack;
- `DragonEggTeleportParticle` with bounded signed offsets; and
- `MobSpawnParticle` with bounded width and height.

```php
use Bedriox\Api\World\BlockFace;
use Bedriox\Api\World\Particle\BlockParticle;
use Bedriox\Api\World\Particle\BlockParticleType;
use Bedriox\Api\World\Particle\ColoredParticle;
use Bedriox\Api\World\Particle\ColoredParticleType;
use Bedriox\Api\World\Particle\ParticleBlockState;
use Bedriox\Api\World\Particle\ParticleColor;

$world->spawnParticle(
    $position,
    new ColoredParticle(ColoredParticleType::DUST, new ParticleColor(255, 80, 20)),
);
$world->spawnParticle(
    $position,
    new BlockParticle(
        BlockParticleType::PUNCH,
        new ParticleBlockState('minecraft:oak_log', ['pillar_axis' => 'y']),
        BlockFace::UP,
    ),
);
```

The server resolves canonical block and item values against the active data set at the packet boundary. Plugin code does not pack colors, faces, item IDs, block runtime IDs, or event IDs into integers.

## Potions and effect delivery

The admitted creative inventory retains every current potion metadata variant for drinkable, splash, and lingering containers. `PotionType` gives each variant a typed identity and supplies fresh immutable effect values; `PotionContainer` distinguishes the three item forms without exposing item metadata as an effect ID.

Drinkable potions use the ordinary authoritative item-use lifecycle. The item-use event is the all-or-nothing consumption boundary. After it accepts, completion consumes the selected survival item, returns a glass bottle, and independently offers each dose through the same cancellable effect events as commands and plugins. Cancelling one dose skips that effect without refunding the item or rolling back nutrition, cooldown, residue, or other accepted doses. Creative use retains the item. Milk consumes normally and returns a bucket while independently retaining any effect whose removal event was cancelled. Golden and enchanted golden apples follow the same per-effect rule alongside their food result.

Splash and lingering potions are server-owned projectiles. On impact, splash effects use direct-hit and distance-based strength within four blocks. Lingering potions create bounded area-effect clouds with radius shrink, lifetime, application cadence, and per-entity reapplication delays. Tipped arrows carry the potion variant from their authoritative item metadata and apply the reduced arrow duration after an entity hit.

Effect events distinguish `POTION`, `SPLASH_POTION`, `LINGERING_POTION`, and `TIPPED_ARROW` delivery. Clients report bounded use or attack intent; they do not select affected entities, effect strength, duration, projectile motion, or inventory results.

## Brewing stands

A brewing stand has three bottle slots, one ingredient slot, and one blaze-powder fuel slot. Bedriox resolves container conversions and potion metadata transitions from the active Data release. A valid operation takes 400 simulation ticks, uses one fuel charge when it begins, consumes one ingredient when it commits, and replaces every still-valid bottle result as one authoritative update.

The stand's inventory, fuel, progress, and block-entity state persist with the world. Closing and reopening the screen, unloading and reloading the chunk, or restarting the server must preserve committed contents and in-progress state. Invalid or stale inventory requests receive ordinary authoritative correction.

Hopper automation is not implemented. A future hopper system must use the same authoritative container boundary rather than changing brewing slots or timers directly.

Plugins can observe or influence brewing through typed events:

- `BrewingFuelConsumeEvent` is cancellable and may replace the bounded number of fuel uses before blaze powder is consumed.
- `BrewingFuelConsumedEvent` observes committed fuel consumption.
- `BrewingEvent` is cancellable and may replace the three proposed bottle-slot results with admitted item stacks or empty slots.
- `BrewedEvent` observes the committed results.

Events expose a canonical block position and immutable API item values. They do not expose window IDs, stack-network IDs, recipe metadata tables, mutable block entities, or timers.

## Retail test checklist

Before qualifying a release, use the pinned retail client and verify:

1. Add, replace, hide, remove, clear, and persist effects with `/effect`; confirm client presentation, particles, movement, health, and reconnect behavior.
2. Spawn named and data-backed particles at absolute and relative positions, with one and two players in the same loaded chunk.
3. Drink ordinary, strong, long, instant, compound, and effect-free potions in survival and creative; verify the selected stack and glass-bottle result.
4. Drink milk and eat both golden-apple variants; verify effect removal or addition and inventory residue.
5. Throw splash potions at a direct target and at increasing distances; verify nearby players and mobs receive only the server-calculated dose.
6. Throw lingering potions; remain in, leave, and re-enter the cloud while observing radius, expiry, and reapplication delay.
7. Hit a living entity with a tipped arrow and verify its reduced duration without duplicating ammunition or effects.
8. Brew effect, duration, strength, splash, and lingering transitions. Close and reopen the stand, use two viewers, unload its chunk, and restart during an active brew.
9. Cancel the item-use pre-event and verify consumption is rejected without inventory or nutrition mutation. Separately cancel individual effect add/remove events and verify only those doses are skipped or retained while accepted consumption and residue remain committed. Cancel each brewing pre-event and verify there is no partial fuel, ingredient, or bottle mutation.

Record the Bedriox commit, Data release, client build, platform, exact action, and observable result with the rest of the [client journey](client-journey.md).

## Current boundary

The typed effect manager, lifecycle events, current named and data-backed particles, drinkable and throwable potion forms, tipped-arrow delivery, area-effect clouds, and brewing stands are implemented for the active data release. Catalog admission still does not imply that every unrelated item, projectile, processing station, or block-specific mechanic is available. Retail qualification remains open until the checklist above is completed against the pinned client build.
