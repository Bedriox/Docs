# Storage containers

Bedriox provides server-authoritative persistent inventories for chests,
trapped chests, barrels, shulker boxes, and Ender Chests. Client inventory
actions are requests: the active server window, current player inventory,
canonical storage contents, stack lineage, item rules, and both revisions decide
whether a complete transaction commits.

## Storage ownership

Chests, trapped chests, barrels, and shulker boxes store their contents as block
entities in the owning LevelDB chunk. A valid paired chest presents one
deterministically ordered 54-slot view while preserving two independently
stored 27-slot halves. Breaking, replacing, pairing, or unpairing a block
updates the canonical world state rather than retaining a detached screen.

Ender Chest contents belong to the authenticated player. Every Ender Chest
opens that player's private 27-slot inventory, which is stored in the player
profile and never shared with another viewer.

Shulker boxes preserve bounded contents and their custom block name in the
server-owned item when broken in survival. Placing the item restores those
contents and derives the new orientation from the placement face. Shulker items
stack to one and cannot be nested inside the shulker box currently being used.

## Window and transaction behavior

Opening storage validates the current block, block entity, range, player state,
and window capacity. Moving, splitting, merging, swapping, or dropping a stack
stages the player and storage changes together. A revision or stack-lineage
mismatch changes neither inventory and receives an authoritative correction.

The active window closes when the client closes it, another window opens, the
player moves out of range, the backing block changes, or the player teleports,
dies, disconnects, or shuts down with the server. Closing is idempotent. First
and last viewer transitions control the chest animation and barrel open state;
one player leaving does not close a shared container for remaining viewers.

Multiple viewers use independent stack-network identities. A committed change
is projected immediately into every active window without exposing one
session's identifiers to another. Contents remain durable across chunk unload
and clean server restart.

## Plugin API

`PluginContext::containers()` returns a plugin-scoped `ContainerManager`. A
plugin may look up supported world storage by loaded-world handle and canonical
block position or create a virtual layout:

```php
use Bedriox\Api\Inventory\ContainerLayout;
use Bedriox\Api\Inventory\ItemStack;
use Bedriox\Api\World\BlockPosition;

$containers = $this->context()->containers();
$worldChest = $containers->at($world, new BlockPosition(10, 65, -4));
$menu = $containers->create(
    ContainerLayout::HOPPER,
    'Travel menu',
);
$menu->setItem(0, new ItemStack('minecraft:apple', 4));
$menu->open($player);
```

Virtual layouts are single chest, double chest, hopper, dispenser, and dropper.
Their sizes are fixed by `ContainerLayout`; arbitrary sizes are rejected.
Virtual inventories are memory-only, owned by the creating plugin, and closed
and released when that plugin disables.

Container handles expose immutable views plus bounded `setItem()`, `addItem()`,
`removeItem()`, `clear()`, `open()`, and `close()` requests. They do not expose
packets, window IDs, stack-network IDs, mutable block entities, or internal
registries.

The event surface includes cancellable open, transaction, and chest-pair
pre-events plus observational opened, close, committed-transaction, and paired
events. Plugin cancellation leaves all authoritative slots unchanged. A plugin
exception discards its staged changes and follows normal plugin isolation.

## Retail checklist

1. Place and open each supported storage block.
2. Move, split, merge, and remove stacks; verify immediate visible updates.
3. Close and reopen each window and confirm the contents remain.
4. Restart the server and repeat the contents check.
5. Join with two players, open one chest, and transact from both clients.
6. Verify each player sees only their own Ender Chest contents.
7. Break and replace a shulker box with contents and a custom name.
8. Move out of range, replace an open block, teleport, die, disconnect, and
   confirm each active window closes without item loss or duplication.

Record the exact Bedriox and component commits, client build and platform, and
sanitized settings. Do not retain login tokens, identity chains, encryption
keys, credentials, or raw personal packet captures.

## Current boundary

Storage containers do not implement furnace-style processing, fuel, brewing,
enchanting, smithing, stonecutting, automation, redstone behavior, or hoppers
moving items between world inventories. Those systems build on this inventory
and block-entity foundation in later milestones.
