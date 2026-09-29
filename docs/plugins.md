# Plugins and API 0.3

Bedriox loads trusted in-process PHP plugins from strongly signed PHAR files.
Bedriox itself discovers only files named `*.phar` directly inside `plugins/`.
Installing [PluginTools](https://github.com/Bedriox/PluginTools) adds an
optional development source loader and console packaging workflow. Restart
Bedriox after installing, replacing, or changing PHP code because PHP cannot
safely unload changed classes.

## Install a plugin

1. Obtain the PHAR from a source you trust.
2. Verify the publisher's checksum or signature information.
3. Copy it to `plugins/ExamplePlugin.phar`.
4. Restart Bedriox and check the console or `logs/server.log` for validation
   and enablement results.

Plugins are trusted code running with the server process permissions. PHAR
signatures detect archive corruption or unreviewed changes; they do not prove
that a publisher is trustworthy or sandbox PHP code.

Bedriox rejects weakly signed, oversized, metadata-bearing, linked, unsafe, or
over-expanded archives before plugin code executes. It also rejects malformed
manifests, incompatible API constraints, duplicate plugins, missing required
dependencies, and dependency cycles. `plugins.enabled=false` disables loading;
`plugins.maximum` limits discovered archives from 0 through 256.

## Develop from source

Place `PluginTools.phar` and each source project directly under `plugins/`:

```text
plugins/
|-- PluginTools.phar
`-- MyPlugin/
    |-- plugin.json
    |-- src/
    `-- resources/
```

PluginTools automatically discovers, validates, and loads direct source-project
children when it starts. PluginTools owns the scan and namespace-restricted
loader; Bedriox admits its bounded definitions to the normal lifecycle but
never scans source directories itself. Removing PluginTools restores native
PHAR-only startup.

Source and PHAR plugins use the same manifest, API compatibility, dependency,
lifecycle, command, event, ownership, and failure rules. A source plugin may
depend on an installed PHAR plugin, but a PHAR plugin cannot require a source
plugin because the PHAR dependency plan is validated first. A source and PHAR
plugin cannot have the same name.

Source projects are trusted executable PHP, not sandboxed content. PluginTools
rejects unsafe paths, symbolic links, malformed projects, namespace and entry
point mismatches, duplicate names, invalid dependencies, and projects outside
its file and byte limits. Restart after every source change.

## Build a PHAR

With a source plugin discovered by PluginTools, use the recommended Bedriox
console workflow:

```text
makeplugin MyPlugin
```

The output is written to:

```text
plugin_data/PluginTools/MyPlugin.phar
plugin_data/PluginTools/MyPlugin.phar.sha256
```

Existing output is preserved unless replacement is explicit:

```text
makeplugin MyPlugin --overwrite
```

The command accepts a discovered plugin name, not a filesystem path. Packaging
runs in a bounded child PHP process and does not synchronously block the
simulation. A failed replacement leaves the previous output available.

For CI, automation, or an offline server, use the standalone CLI:

```shell
php -d phar.readonly=0 ../PluginTools/bin/plugin-tools . dist/MyPlugin.phar
```

The command validates `plugin.json`, canonical paths, archive bounds, and
exclusions; rejects symbolic links and path escapes; sorts entries; applies a
strong SHA-256 PHAR signature; and writes `MyPlugin.phar.sha256`.
`phar.readonly=0` is required only for building, never for running Bedriox.

PHP's PHAR API does not provide a portable entry-timestamp setter. PluginTools
makes selection, order, content, stub, signature algorithm, and checksum
deterministic without claiming byte-identical timestamps on every runtime.
Creating a PHAR does not install, reload, or enable it. PluginTools is intended
for development servers and should normally be removed from production when
source loading and in-server packaging are unnecessary.

## Manifest

Every archive root contains `plugin.json` and `src/`:

```json
{
  "schema": 1,
  "name": "ExamplePlugin",
  "version": "0.1.0",
  "api": "^0.3",
  "main": "Bedriox\\ExamplePlugin\\Main",
  "namespace": "Bedriox\\ExamplePlugin",
  "authors": ["Bedriox Team"],
  "dependencies": [],
  "softDependencies": [],
  "load": "WORLD_READY"
}
```

Names and dependency names begin with an uppercase ASCII letter and contain
only letters, digits, or underscores. `main` must belong to `namespace`.
`load` is `STARTUP` or `WORLD_READY`. Required dependencies must be present and
enable first; present soft dependencies influence ordering but are optional.

## Lifecycle

The entry point extends `Bedriox\Api\Plugin\Plugin` and may implement
`onLoad()`, `onEnable()`, and `onDisable()`. A load or enable exception fails
that plugin and its required dependants. A listener exception rolls back that
listener's controlled event changes and staged actions, disables the plugin,
and removes its listeners. A disable exception is logged while forced cleanup
continues.

Ordinary plugin failures are contained, but plugins are not isolated processes.
A segmentation fault, infinite loop, forced termination, or exhausted process
can still stop the server.

## Events and priorities

Annotate a public, non-static method that accepts exactly one Event subtype and
returns `void`:

```php
#[EventHandler]
public function onJoin(PlayerJoinEvent $event): void
{
    $event->player->sendMessage('Welcome');
}
```

No priority argument means `EventPriority::NORMAL`. Dispatch order is
`LOWEST`, `LOW`, `NORMAL`, `HIGH`, `HIGHEST`, then `MONITOR`; registration
order breaks ties. Programmatic registration is also available through the
plugin context event registrar.

Cancellable pre-events run after core validation and before authoritative
commit. Cancellation cannot bypass authentication, collision, reach,
inventory ownership, world bounds, or limits. Post-events are immutable.
`MONITOR` observes the final outcome and cannot modify, cancel, or stage server
actions. `receiveCancelled: true` opts a listener into already cancelled
pre-events.

Gameplay events also cover item use, consumption, nutrition, armor and offhand
equipment, durability loss, and item breakage. Validated intent is exposed
through cancellable pre-events such as `PlayerItemUseEvent`,
`PlayerItemConsumeEvent`, `PlayerFoodLevelChangeEvent`,
`PlayerEquipmentChangeEvent`, and `PlayerItemDamageEvent`. Immutable
past-tense events report only committed results. Consumption and nutrition
events accept bounded replacement values; equipment replacement is revalidated
for its typed slot; durability adjustments remain within the item bounds.
Nutrition-driven healing also passes through adjustable
`PlayerRegainHealthEvent` and observational `PlayerRegainedHealthEvent`.

Crafting exposes a cancellable `PlayerCraftItemEvent` after authoritative
recipe, grid, lineage, ingredient, result, repetition, and capacity validation
but before inventory commit. A listener may cancel the entire transaction or
replace its one-repetition outputs without increasing the validated stack or
item-count budget; Bedriox revalidates item admission and capacity afterward.
`PlayerCraftedItemEvent` observes only a fully committed craft. Both events
provide data-only player, recipe, grid, consumed-input, output, remainder, and
craft-count values rather than access to live inventory.

Storage exposes cancellable `InventoryOpenEvent` and
`InventoryTransactionEvent` before authoritative mutation. `InventoryOpenedEvent`,
`InventoryCloseEvent`, and `InventoryTransactionCommittedEvent` observe
completed lifecycle transitions. Chest pairing has its own cancellable pre-event
and committed post-event. These events use immutable inventory views and typed
causes; cancellation cannot retain a stale window or bypass stack lineage.

Entity events expose typed, immutable entity views rather than runtime
registries or network actor data. `EntitySpawnEvent`, `EntityDespawnEvent`,
`EntityInteractEvent`, and `EntityDamageEvent` are cancellable pre-events.
`EntitySpawnedEvent`, `EntityDespawnedEvent`, and `EntityDeathEvent` observe
committed outcomes.
`EntityDamageByEntityEvent` adds the typed player or entity that caused the
damage, while `EntityInteractionType` distinguishes ordinary and item-backed
interaction without exposing protocol action numbers.

Status effects use a generation-bound `EffectManager` on players and living
entities. Cancellable add and remove events run before authoritative mutation;
immutable added and removed events report committed state. Worlds also expose
typed, bounded particle presentation with optional player audiences and
delivered-chunk filtering. Brewing exposes cancellable fuel and result events
plus immutable committed notifications without mutable stand internals. See
[effects, particles, potions, and brewing](effects-and-particles.md) for
examples, replacement behavior, causes, and limits.

Player experience uses a generation-bound `ExperienceManager` returned by
`Player::getExperience()`. Its immutable snapshot exposes total points, derived
level, and progress. Bounded `setTotalPoints()`, `addPoints()`, and
`removePoints()` requests enter the authoritative simulation with a typed
`ExperienceChangeCause`; they do not mutate a retained snapshot directly.
`PlayerExperienceChangeEvent` and `ExperienceOrbSpawnEvent` are cancellable
pre-events, while `PlayerExperienceChangedEvent` and
`ExperienceOrbSpawnedEvent` observe committed outcomes.

Processing follows the same paired rule. Furnace fuel, smelt start, smelt
completion, extraction, campfire cooking, transient workstations, enchanting,
composters, and cauldrons expose bounded pre-events and immutable committed
events. Listeners receive canonical item identities, typed station kinds,
positions, and causes rather than mutable station state or protocol values.
Events fire only for semantic transitions, not processing ticks. A listener
cannot bypass reach, slot ownership, stack lineage, capacity, experience cost,
revision checks, or atomic commit. See
[processing stations and experience](processing-and-experience.md).

See the tested
[ExamplePlugin](https://github.com/Bedriox/ExamplePlugin) for lifecycle,
logging, default and explicit priorities, cancellation, monitoring, and safe
messaging examples.

## Commands

Register a class implementing `Command`, normally by extending
`AbstractCommand`, through `PluginContext::commands()`. Its definition declares
a lowercase name, description, aliases, optional permission, and `ANY`,
`CONSOLE_ONLY`, or `PLAYER_ONLY` sender policy. `defineArguments()` supplies
typed parameters and overloads used by the parser, generated usage, and
Bedrock autocomplete. Names and aliases are case-insensitive. A deterministic
`<plugin>:<command>` name is registered alongside the short name, and a later
registration that conflicts with an existing short name or alias is rejected.

`execute()` receives a `CommandContext` containing the sender, resolved label,
and validated `CommandValues`. It returns a success or failure result, with an
optional message for the sender. Use concrete sender types when behavior
depends on the caller:

```php
if ($context->sender() instanceof ConsoleCommandSender) {
    $context->sender()->sendMessage('Called from the console');
}

if ($context->sender() instanceof PlayerCommandSender) {
    $player = $context->sender()->player();
}
```

Bedriox accepts commands from the non-blocking server console and authenticated
Bedrock slash-command input. UUID-based operator state, explicit permission
grants, sender restrictions, and command events are enforced centrally before
plugin code runs.

Bedriox enforces sender and permission policy before plugin code runs.
`CommandPreDispatchEvent` can cancel valid dispatch after those checks;
`CommandDispatchedEvent` observes completed execution. Commands, aliases, and
cooperative jobs are removed or cancelled when their owning plugin disables.
A throwing handler is attributed to and disables only its owning plugin when
the server remains consistent. See [commands](commands.md) for the complete
boundary.

## Safe public API

API 0.3 provides immutable `Player`, `World`, `Position`, `BlockPosition`,
`Block`, `Inventory`, and `ItemStack` values. `PluginContext::server()` is the
global discovery surface: `getWorldManager()`, `getOnlinePlayers()`,
`getPlayerByUuid()`, and exact case-insensitive `getPlayerByName()`.
Authoritative behavior belongs to the object it affects:

```php
use Bedriox\Api\Inventory\ItemStack;
use Bedriox\Api\World\BlockPosition;
use Bedriox\Api\World\Position;

$server = $this->context()->server();
$player = $server->getPlayerByName('ExamplePlayer');
if ($player !== null) {
    $player->sendMessage('Welcome');
    $player->getInventory()->addItem(new ItemStack('minecraft:apple', 1));
    $player->teleport(new Position(100.5, 70.0, -25.5));
}

$world = $server->getWorldManager()->getDefault();
$spawnBlock = new BlockPosition(0, 64, 0);
$block = $world->getBlock($spawnBlock);
$world->setBlock($spawnBlock, 'minecraft:grass_block');
```

`Player` owns message, teleport, damage, game-mode, and access to its three
session-bound inventory capabilities. `World` owns canonical block reads and
writes.
Requests enter the same authoritative validation, event, persistence, and
synchronization paths as built-in gameplay. Stale player sessions and unloaded
world generations fail closed; plugins never receive the mutable server player
or heavy world runtime.

### Player inventories

Use the inventory capability that owns the slot being changed:

```php
$main = $player->getInventory();
$armor = $player->getArmorInventory();
$offHand = $player->getOffHandInventory();

$main->setItem(4, new ItemStack('minecraft:apple', 16));
$armor->setHelmet(new ItemStack('minecraft:diamond_helmet', 1));
$offHand->clear();
```

`PlayerInventory` covers the 36-slot main inventory and hotbar. It provides
`getSize()`, `getSelectedHotbarSlot()`, `getHeldItem()`, `getItem()`,
`getContents()`, `isEmpty()`, `contains()`, `getAddableQuantity()`, and
`firstEmpty()` for reads. Its authoritative mutations are `setItem()`,
`setContents()`, `addItem()`, `removeItem()`, `clear()`, `clearAll()`, and
`setSelectedHotbarSlot()`.

`ArmorInventory` provides generic `getItem()`, `setItem()`, and `clear()`
operations using `EquipmentSlot`, named helmet, chestplate, leggings, and boots
getters and setters, plus `getContents()`, `clearAll()`, and `isEmpty()`.
`OffHandInventory` provides `getItem()`, `setItem()`, `clear()`, and `isEmpty()`
for its single slot.

Content arrays and returned item stacks are snapshots. Mutate an inventory
through its methods rather than editing a returned array. Bulk replacement is
validated as one authoritative operation, and only changed slots are
synchronized. A main-inventory `clearAll()` does not clear armor or offhand.
Capabilities retained after disconnect or replacement login reject mutations
instead of targeting a newer session.

`PluginContext::items()` may register bounded item definitions and gameplay
behavior for canonical identifiers already admitted by the active Bedrock data
set. `ItemBehaviorDefinition` can describe instant or timed consumption,
cooldowns, nutrition and residue, armor slot and defense, maximum durability,
optional knockback resistance, and offhand eligibility. Definitions belong to
the registering plugin. An explicit replacement may override built-in behavior
or that owner's earlier definition, but not another plugin's definition.
Disablement removes the override and restores any built-in behavior. Custom
effects belong in typed event listeners and staged server API calls; behavior
registration does not expose protocol IDs or mutable internal catalogs.

`PluginContext::recipes()` registers immutable shaped or shapeless fixed-result
recipes while the plugin is enabled. Definitions use a canonical namespaced
recipe identity, `RecipeIngredient` alternatives, canonical `ItemStack`
outputs, optional shaped holes and mirroring, and an optional bounded priority.
For example:

```php
$this->context()->recipes()->register(new ShapelessRecipe(
    'myplugin:grass_block_from_dirt',
    [RecipeIngredient::exact('minecraft:dirt')],
    [new ItemStack('minecraft:grass_block', 1)],
));
```

A plugin may explicitly replace its own recipe identity, but cannot replace a
built-in recipe or another plugin's recipe. Disablement removes every recipe
owned by that plugin. Registration is limited to 512 recipes per plugin and
4,096 plugin recipes server-wide; each definition contains at most nine
occupied ingredients and four outputs. Item identifiers and counts must be
admitted by the active catalog. The API does not expose recipe network IDs,
container IDs, packet values, or mutable server registries. See
[crafting](crafting.md) for the gameplay and lifecycle boundary.

`PluginContext::containers()` returns a plugin-scoped
`ContainerManager`. It can resolve supported world storage at a canonical block
position or create a virtual single chest, double chest, hopper, dispenser, or
dropper. Handles expose immutable contents plus bounded set, add, remove, clear,
open, and close requests. Virtual inventories are memory-only and close when
their owning plugin disables. Plugins never receive window IDs, stack-network
IDs, block-entity objects, or mutable server inventories. See
[storage containers](storage-containers.md).

`PluginContext::entities()` is an owner-scoped custom mob registrar. A
definition combines a canonical non-`minecraft` identifier, a supported
vanilla catalog identity for client appearance, dimensions, maximum health,
category, behavior factory, and optional bounded state codec. The owner may then request an
authoritative spawn of that type at a public `Position`; registration does not
grant direct access to world or actor registries.

Custom behavior may implement `onSpawn()`, `onTick()`, `onAiTick()`, and
`onDespawn()`. Tick contexts expose a bounded controller for movement, look,
target, velocity, and despawn intent. Controller and staged API actions commit
only after their callback returns successfully; failure discards the complete
batch. Ordinary lifecycle ticks continue when entity AI is disabled; AI hooks
do not. Persistent custom state is an opaque schema-versioned byte string with
an explicit per-definition limit. Explicit replacement affects future spawns,
while each live mob keeps its original immutable definition generation. Plugin
disablement releases its definitions, and a factory, lifecycle, or codec
failure follows normal plugin failure isolation. See
[entities and custom mobs](entities.md) and the tested
[ExamplePlugin](https://github.com/Bedriox/ExamplePlugin) guide mob.

Plugins do not receive sockets, packets, encryption state, mutable registries,
internal queues, protocol stack IDs, or process-local block IDs. Mutations are
revalidated and staged while a listener runs. Marketplace distribution and a
security sandbox are not part of API 0.3.

## World lifecycle and generators

`Server::getWorldManager()` returns the API 0.3 `WorldManager`. It provides
constant-time loaded-world lookup plus queued create, load, save, and unload
operations through lightweight world handles. It does not expose the heavy
world runtime, LevelDB provider, mutable chunks, or internal queues.

`Position` accepts optional world, yaw, and pitch values so the common
same-world teleport remains concise while a supplied world requests an atomic
cross-world transition. Plugins, not Bedriox core, own world-management
commands, menus, aliases, and access policy.

`PluginContext::generators()` exposes owner-scoped namespaced definitions.
Generator classes are final, stateless, zero-argument implementations with
bounded immutable inputs. Built-ins run in core workers; plugin-defined
generators use bounded main-thread execution and do not receive mutable server
services. See
[worlds and teleportation](worlds-and-teleportation.md) and
[plugin world generators](plugin-world-generators.md) for the implemented
contract and current qualification limits.
