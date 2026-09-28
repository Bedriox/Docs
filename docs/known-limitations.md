# Known limitations

The current executable is a narrow experimental server foundation, not a general-purpose Bedrock gameplay server.

- No retail client version or network protocol is listed as fully supported.
  Protocol 2193 / Bedrock 1.26.50 is the sole wire target, and Bedrock 1.26.51
  is an initially qualified same-protocol client, but the complete release
  qualification gate remains open.
- The server supports multiple independently persisted LevelDB overworlds with
  bounded movement-driven chunk streaming. New worlds default to deterministic
  seeded continents with climate-driven biomes, mountain ranges, rivers,
  oceans, caves, regional ore veins, snow, biome-specific forests, and
  deterministic structures; the fixed flat profile remains selectable.
  Additional dimensions and vanilla seed parity remain unavailable. The
  lifecycle, teleport, and generator boundaries are documented in
  [worlds and teleportation](worlds-and-teleportation.md) and
  [plugin world generators](plugin-world-generators.md). Cross-world retail
  qualification remains open and is not a compatibility claim.
- Inventory is server-owned across the 36-slot main inventory, hotbar, cursor,
  four armor slots, and offhand. It supports ordinary movement, splitting,
  dropping and pickup, admitted tools and durability, typed equipment, and the
  implemented ordinary food and drink behaviors. Personal and crafting-table
  grids support the complete admitted crafting-grid catalog through
  authoritative transactions. Chests, trapped chests, barrels, shulker boxes,
  and private Ender Chests use persistent authoritative inventories. Processing
  stations, enchantments, complete effects, projectiles, and many unique item
  mechanics remain unavailable. See [crafting](crafting.md) and
  [storage containers](storage-containers.md).
- The creative inventory includes the complete admitted groups, entries,
  damage variants, block-state variants, and bounded item NBT for the pinned
  release. Catalog presence does not imply that every item-specific vanilla
  mechanic is implemented. See
  [versioned data and the creative catalog](data-and-creative-catalog.md).
- Supported block interaction validates reach, collision, selected inventory
  state, tool-aware hardness, durability, drops, placement definitions, and
  authoritative world state. Catalog admission still does not provide every
  block-specific vanilla interaction.
- The first entity runtime slice supports catalog-backed actor projection,
  explicit spawning, entity persistence, bounded natural populations, staged
  mob decisions, and owner-scoped custom mobs. Complete species-specific
  vanilla behavior, breeding and taming, bosses, projectiles, complete status
  effects, and persistent arbitrary plugin data remain unavailable. See
  [entities and custom mobs](entities.md) and
  [plugins and API 0.3](plugins.md).
- Multiplayer actor join, movement, posture, chat, departure, and reconnect
  have automated coverage, but the repeatable two-client retail checklist,
  longer soak, loss, scale, and memory-recovery gates remain open.
- `SELF_SIGNED` authentication is for isolated development and does not provide production identity assurance.
- World chunks and authoritative block changes persist across restarts. Player
  identity, position, orientation, inventory, health, game mode, nutrition, and
  equipment use schema-versioned persistence.
- Soak, packet-loss, cross-platform retail, and published performance qualification remain outstanding.

These limitations are kept separate from the [roadmap](roadmap.md): the roadmap describes sequencing, while this page describes the observable boundary of the current software.
