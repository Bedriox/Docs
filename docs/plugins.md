# Plugins and API 0.1

Bedriox loads trusted in-process PHP plugins from strongly signed PHAR files.
Only files named `*.phar` directly inside `plugins/` are discovered; source
directories are not loaded. Restart Bedriox after installing or replacing a
plugin because PHP cannot safely unload changed classes.

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

## Build a PHAR

[PluginTools](https://github.com/Bedriox/PluginTools) is the first-party
development-only builder. From a plugin project:

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

## Manifest

Every archive root contains `plugin.json` and `src/`:

```json
{
  "schema": 1,
  "name": "ExamplePlugin",
  "version": "0.1.0",
  "api": "^0.1",
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
    $this->context()->server()->sendMessage($event->player, 'Welcome');
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

Initial events cover join and quit, movement, chat, block breaking and
placement, and inventory changes. See the tested
[ExamplePlugin](https://github.com/Bedriox/ExamplePlugin) for lifecycle,
logging, default and explicit priorities, cancellation, monitoring, and safe
messaging examples.

## Safe public API

API 0.1 provides immutable Player, World, Position, BlockPosition, Block,
Inventory, and ItemStack views. Server operations can list players, look up a
player by UUID, read the world or a block, send a message, request a teleport,
set a canonical block identifier, or request an inventory-slot change.

Plugins do not receive sockets, packets, encryption state, mutable registries,
internal queues, protocol stack IDs, or process-local block IDs. Mutations are
revalidated and staged while a listener runs. Commands, permissions,
scheduling, persistence, custom entities, marketplace distribution, and a
security sandbox are not part of API 0.1.
