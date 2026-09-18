# Known limitations

The current executable is a narrow experimental server foundation, not a general-purpose Bedrock gameplay server.

- No retail client version or network protocol is listed as fully supported.
  Protocol 2193 / Bedrock 1.26.50 is the sole wire target, and Bedrock 1.26.51
  is an initially qualified same-protocol client, but the complete release
  qualification gate remains open.
- The server supports one persistent LevelDB overworld with bounded
  movement-driven chunk streaming. New worlds default to deterministic seeded
  terrain with hills, five biome profiles, water, caves, ores, and oak trees;
  the fixed flat profile remains selectable. Vanilla seed parity, structures,
  additional dimensions, and multiple loaded worlds are unavailable.
- Inventory is limited to the server-owned 36-slot main inventory, hotbar, and
  cursor. It supports opening and closing the main window, selection, Take,
  Place, Swap, and stack splitting. Crafting, armor manipulation, containers,
  item drops, tools, durability, and general item use are unavailable.
- Block interaction is limited to breaking generated blocks and placing grass
  in the authoritative world. Placement consumes the starter grass stack and validates
  reach, collision, selected inventory state, and authoritative world state.
  Block drops, tools, hardness by tool, and broader block behavior are not
  implemented.
- Combat, mobs, persistent player permissions, Bedrock slash-command input,
  general scheduling, and persistent plugin data are outside the implemented
  gameplay slice. Experimental plugin API 0.1 provides lifecycle, events,
  views, safe operations, and console-dispatched typed commands as documented
  in [plugins and API 0.1](plugins.md) and [commands](commands.md).
- Multiplayer actor join, movement, posture, chat, departure, and reconnect
  have automated coverage, but the repeatable two-client retail checklist,
  longer soak, loss, scale, and memory-recovery gates remain open.
- `SELF_SIGNED` authentication is for isolated development and does not provide production identity assurance.
- World chunks and authoritative block changes persist across restarts. Player
  position, inventory, and identity persistence remain unavailable.
- Soak, packet-loss, cross-platform retail, and published performance qualification remain outstanding.

These limitations are kept separate from the [roadmap](roadmap.md): the roadmap describes sequencing, while this page describes the observable boundary of the current software.
