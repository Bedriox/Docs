# Purpose and scope

Bedriox is a Minecraft Bedrock Edition server written in PHP. Its architecture emphasizes bounded untrusted-input handling, deterministic authoritative state, explicit component contracts, testability, and measurable performance.

The initial playable scope is intentionally narrow:

- UDP discovery and reliable RakNet sessions;
- Bedrock login, authentication, encryption, and resource-pack negotiation;
- fixed-flat spawn initialization backed by immutable protocol data;
- player-list visibility, bounded movement, text chat, and disconnect handling;
- an authoritative 20 TPS in-memory simulation.

This scope is a development boundary, not a retail compatibility claim. The authoritative supported client and protocol arrays remain empty until the qualification described in [compatibility](compatibility.md) and [testing](testing.md) is complete.

Inventory gameplay, block interaction, persistence, general world generation, entities, combat, commands, permissions, plugins, and broad version compatibility are later capabilities. They should be added behind owned interfaces and measured needs rather than coupled to transport callbacks or unchecked packet data.

Repository responsibilities remain separate: Bedriox composes the server, RakNet owns transport, Protocol owns Bedrock wire behavior, Data owns admitted immutable data, RFCs owns architecture decisions, and this repository owns public guides.
