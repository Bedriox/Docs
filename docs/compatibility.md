# Compatibility

Bedriox does not currently claim support for any retail Minecraft Bedrock client or network protocol. The executable server's compatibility manifest intentionally contains:

```json
{
  "bedrock": {
    "clientVersions": [],
    "networkProtocols": []
  }
}
```

Protocol 2193, associated with Bedrock 1.26.50, is the sole implementation
target. Bedrock 1.26.51 has completed an initial same-protocol retail
qualification for discovery, login, spawn, flat-world chunk streaming,
movement, collision, multiplayer visibility, chat, emotes, the narrow
authoritative inventory slice, and grass breaking and placement. This evidence
does not yet satisfy the complete support gate, so the public support arrays
remain empty. Older packet paths are not compatibility aliases and may not be
selected by a client version string.

The empty arrays are deliberate. They prevent an implemented packet slice, a successful unit test, or an isolated login from being presented as end-to-end compatibility.

## Qualification required for a support claim

A client/protocol pair can be added only after all component gates pass and the release records repeatable retail-client coverage for discovery, login, spawn, terrain, interaction, movement, chat, disconnect, reconnect, malformed traffic, loss recovery, soak, and performance on the claimed platforms.

See [testing](testing.md) for automated evidence and the outstanding qualification gates. See [known limitations](known-limitations.md) before experimentation.
