# Purpose and scope

Bedriox is a Minecraft Bedrock Edition server written in PHP. Its architecture emphasizes bounded untrusted-input handling, deterministic authoritative state, explicit component contracts, testability, and measurable performance.

The current playable scope includes:

- UDP discovery and reliable RakNet sessions;
- Bedrock login, authentication, encryption, and resource-pack negotiation;
- persistent default and flat overworld generation backed by immutable
  current-version protocol data;
- player-list visibility, bounded movement and collision, text chat, emotes, and disconnect handling;
- server-owned survival and creative inventory, world item entities, armor,
  offhand, tools, durability, ordinary consumption and nutrition, and
  authoritative reconciliation;
- server-owned personal and crafting-table grids with current-version recipe
  matching and atomic crafting transactions;
- persistent chest, trapped-chest, barrel, shulker-box, and player-owned Ender
  Chest inventories with atomic multiplayer window transactions;
- authoritative block breaking and placement, player combat, health, death,
  respawn, commands, operators, permissions, and player persistence; and
- an authoritative 20 TPS simulation with bounded worker-backed world and
  packet preparation.

This scope is a development boundary, not a retail compatibility claim. The authoritative supported client and protocol arrays remain empty until the qualification described in [compatibility](compatibility.md) and [testing](testing.md) is complete.

Processing stations, additional dimensions, multiple loaded worlds, complete
effects and projectiles, mobs, AI, natural
spawning, and broad version compatibility remain later capabilities.
Experimental plugin API 0.1
provides the bounded plugin, event, scheduler, item-behavior, and command
surfaces documented in [plugins and API 0.1](plugins.md) and
[commands](commands.md). Future capabilities should be added
behind owned interfaces and measured needs rather than coupled to transport
callbacks or unchecked packet data.

Repository responsibilities remain separate: Bedriox composes the server, RakNet owns transport, Protocol owns Bedrock wire behavior, Data owns admitted immutable data, RFCs owns architecture decisions, and this repository owns public guides.
