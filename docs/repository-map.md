# Repository map

Bedriox separates transport, wire protocol, immutable versioned data, mutable
gameplay, public documentation, and architectural decisions. A change belongs
in the repository that owns its state and invariants.

| Repository | Owns | Must not own |
| --- | --- | --- |
| Bedriox | Startup, configuration, authentication policy, sessions, players, simulation, worlds, commands, observability | RakNet algorithms, packet layouts, versioned registries |
| RakNet | UDP, discovery framing, negotiation, reliability, ordering, fragmentation, transport sessions | Bedrock packets or gameplay |
| Protocol | Bedrock framing, encryption envelopes, packet codecs, protocol-version authority | Sockets, mutable players or worlds |
| Data | Immutable admitted artifacts, canonical states, palettes, hashes, offline generators | Runtime downloads or mutable server state |
| Docs | Supported operator and ecosystem-developer behavior | Unimplemented promises or architectural decisions |
| RFCs | Decisions, alternatives, consequences, and acceptance criteria | Claims that a feature is already supported |

The dependency direction is one-way: the Bedriox executable consumes RakNet,
Protocol, and Data through pinned public contracts. Components never reach back
into the executable or depend on one another's internal classes.

If a failure appears after gameplay input, first reproduce it at the typed
packet or runtime boundary. RakNet changes require evidence that UDP transport,
reliability, ordering, or session state is actually at fault.

The public description of authoritative players, actor publication, peer
movement, departure, reconnect, and causal-session failure containment lives in
[player and multiplayer lifecycle](player-multiplayer.md).
