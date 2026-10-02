# Crafting

Bedriox provides server-authoritative personal and crafting-table crafting for
the active Bedrock data release. The server advertises the complete admitted
crafting-grid catalog, matches recipes against server-owned inputs, computes
the results, and commits every ingredient, result, remainder, and inventory
change as one transaction.

Client recipe identifiers, displayed results, requested counts, and recipe-book
selections are requests rather than inventory authority. A stale or rejected
request leaves the inventory unchanged and receives an authoritative
correction instead of creating or deleting part of a craft.

## Crafting grids

The inventory screen exposes the player's personal two-by-two grid. Interacting
with a reachable crafting table opens a three-by-three grid associated with
that table session. Bedriox validates the table, player, distance, active
container, recipe, stack lineage, ingredients, requested repetitions, and
destination capacity before committing a craft.

Crafting inputs are temporary server-owned inventory. Closing a grid returns
its remaining inputs to the main inventory; normal world-item overflow handling
is used when required. Closing the window, disconnecting, dying, teleporting,
moving out of range, or losing the active crafting table ends the corresponding
table session. Derived result previews are never stored or returned as items.

The active catalog includes shaped, mirrored, shapeless, alternative-ingredient,
tagged-ingredient, repeated, and supported input-derived crafting-grid recipes.
Both direct grid crafting and current recipe-book requests pass through the
same authoritative transaction path.

## Current boundary

This milestone covers recipes that belong to the personal or crafting-table
grid. Furnaces, blast furnaces, smokers, campfires, stonecutters, smithing
tables, cartography tables, looms, anvils, grindstones, enchanting tables,
brewing stands, and automated crafters are separate processing systems and do
not use a crafting-grid transaction. Implemented player-operated stations are
documented in [processing stations and experience](processing-and-experience.md)
and [effects, particles, potions, and brewing](effects-and-particles.md).
Persistent storage containers use the
same authoritative inventory foundation but are documented separately in
[storage containers](storage-containers.md).

The creative catalog and recipe catalog describe which item and recipe records
the active data release admits. They do not imply that every resulting item's
unique use behavior or an unrelated automation system has been implemented.

## Troubleshooting

- If a result briefly appears and then returns to its prior state, check the
  server log for a corrected crafting request. Stale stack lineage, a changed
  grid, insufficient ingredients, a full destination, or a cancelled event can
  all cause an authoritative correction without disconnecting the player.
- If a crafting-table screen closes, confirm that the table still exists and
  that the player has not moved out of range, teleported, died, changed world,
  or opened another container.
- If a plugin recipe is absent, confirm that the plugin enabled without an
  ownership or item-admission error and that every ingredient and output exists
  in the active item catalog. Recipe definitions are rejected atomically; a
  failed definition does not publish a partial catalog.
- Do not delete player or world data to repair a recipe failure. Record the
  Bedriox commit, active data release, client build, plugin versions, exact
  action, and the surrounding bounded log entries for diagnosis.

## Plugin recipes and events

Enabled plugins can register bounded shaped and shapeless fixed-result recipes
through the public recipe registrar. Registrations use canonical namespaced
recipe and item identifiers and stable grid values; packet recipe IDs, runtime
item IDs, container IDs, and mutable internal registries are not exposed.

Plugin recipes belong to the plugin that registered them. A plugin can replace
its own recipe explicitly, but cannot replace a built-in recipe or another
plugin's recipe. Disabling a plugin removes all recipes that it owns from the
active server registry.

The cancellable `PlayerCraftItemEvent` runs only after the request, active
container, recipe, stack lineage, inputs, outputs, repetitions, and capacity
have passed core validation. Cancelling it leaves every authoritative slot
unchanged. `PlayerCraftedItemEvent` observes a committed craft and cannot change
the result. See [plugins and API 0.4](plugins.md) and the tested
[ExamplePlugin](https://github.com/Bedriox/ExamplePlugin) for the concrete API.

## Retail test checklist

Before qualifying a release, verify at least the following with the pinned
retail client:

1. Craft one-by-one, two-by-two, three-by-three, mirrored, shapeless, tagged,
   remainder, multiple-result, and input-derived recipes.
2. Craft directly and through the recipe book, including repeated crafting.
3. Fill destination slots, cancel a craft through a plugin, and confirm that
   inputs and outputs remain unchanged.
4. Close both grid sizes with inputs present and verify that every item returns
   to inventory or becomes an ordinary owned world item.
5. Move out of range, break the active crafting table, teleport, die,
   disconnect, and reconnect without stale grids or duplicated items.
6. Join with two players, revise the plugin recipe catalog, and verify that both
   clients receive the same usable replacement catalog.

Automated recipe, transaction, protocol, and lifecycle coverage does not replace
this retail qualification. Record the exact Bedriox commit, data release,
client build, platform, and observable result with the rest of the
[client journey](client-journey.md).
