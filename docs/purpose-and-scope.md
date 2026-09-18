# Purpose and scope

Bedriox is a Minecraft Bedrock Edition server written in PHP. Its architecture emphasizes bounded untrusted-input handling, deterministic authoritative state, explicit component contracts, testability, and measurable performance.

The initial playable scope is intentionally narrow:

- UDP discovery and reliable RakNet sessions;
- Bedrock login, authentication, encryption, and resource-pack negotiation;
- fixed-flat spawn initialization backed by immutable protocol data;
- player-list visibility, bounded movement and collision, text chat, emotes, and disconnect handling;
- a server-owned survival inventory slice with hotbar selection, main-inventory and cursor transfers, stack splitting, and authoritative reconciliation;
- authoritative grass-block breaking and placement with bounded reach, collision, inventory, and world-state validation; and
- an authoritative 20 TPS in-memory simulation.

This scope is a development boundary, not a retail compatibility claim. The authoritative supported client and protocol arrays remain empty until the qualification described in [compatibility](compatibility.md) and [testing](testing.md) is complete.

Persistence, general world generation, expanded blocks and items, crafting,
container inventories, entities, combat, Bedrock slash-command input,
persistent player permissions, and broad version compatibility are later
capabilities. Experimental plugin API 0.1 now provides the bounded plugin and
console-command surfaces documented in [plugins and API 0.1](plugins.md) and
[commands](commands.md).
Future capabilities should be added
behind owned interfaces and measured needs rather than coupled to transport
callbacks or unchecked packet data.

Repository responsibilities remain separate: Bedriox composes the server, RakNet owns transport, Protocol owns Bedrock wire behavior, Data owns admitted immutable data, RFCs owns architecture decisions, and this repository owns public guides.
