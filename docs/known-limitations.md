# Known limitations

The current executable is a narrow experimental server foundation, not a general-purpose Bedrock gameplay server.

- No retail client version or network protocol is listed as fully supported.
  Protocol 2193 / Bedrock 1.26.50 is the sole wire target, and Bedrock 1.26.51
  is an initially qualified same-protocol client, but the complete release
  qualification gate remains open.
- The world is an in-memory flat generator with bounded movement-driven chunk
  streaming. Terrain generators other than flat, block interaction,
  persistence, dimensions, and disk world loading are unavailable.
- Inventory behavior, item use, crafting, combat, mobs, commands, permissions, and plugin APIs are outside the implemented gameplay slice. Registry transmission during initialization does not provide those systems.
- Movement accepts the normal bounded PlayerAuthInput projection. Fixed-flat
  block breaking is cosmetic and restores the unchanged authoritative block;
  item-use transactions are notification-only and cannot mutate gameplay.
- Multiplayer actor join, movement, posture, chat, departure, and reconnect
  have automated coverage, but the repeatable two-client retail checklist,
  longer soak, loss, scale, and memory-recovery gates remain open.
- `SELF_SIGNED` authentication is for isolated development and does not provide production identity assurance.
- Player state and worlds are not persisted across restarts.
- Soak, packet-loss, cross-platform retail, and published performance qualification remain outstanding.

These limitations are kept separate from the [roadmap](roadmap.md): the roadmap describes sequencing, while this page describes the observable boundary of the current software.
