# Troubleshooting

## The server rejects an option

Use exact `--name=value` syntax. Names are case-sensitive; duplicates, unknown options, empty values, non-decimal numeric values, and values outside the documented limits fail closed. Compare the command with [configuration](configuration.md).

## FULL authentication fails before the server binds

This is intentional fail-closed behavior. Confirm that PHP has cURL and OpenSSL support, outbound HTTPS and DNS are available, the Microsoft discovery endpoint is reachable, and the system clock is correct. Do not switch a public server to `SELF_SIGNED` merely to bypass discovery failure.

## The UDP port cannot be bound

Confirm that `--bind` is a literal local IPv4 address, the port is not already in use, and the operating system permits the process to bind it. The default Bedrock UDP port is 19132.

## A retail client cannot connect or reports an incompatible server

No retail client or network protocol is currently supported. Protocol 2193 /
Bedrock 1.26.50 is the sole implementation target, and Bedrock 1.26.51 has only
bounded same-protocol qualification. Check [compatibility](compatibility.md)
rather than inferring support from the advertised target version.

## Another client still sees a disconnected player

The runtime sends `RemoveActor` before `PlayerListRemove`. Record the exact
client builds, both players' join/departure sequence, and sanitized diagnostics.
Then verify that the server accepted the authoritative disconnect and reconnect
cleanup before changing transport or packet code. Do not include account IDs,
authentication material, or raw personal packet captures.

## One multiplayer action disconnects other players

World-event encoding and fan-out budget failures should disconnect only the
session whose command caused the event. If an unrelated recipient disconnects,
record the sanitized `event_type`, failure category, both session phases, and
last completed client-journey step. Treat this as a regression in causal-session
containment, not as permission to weaken malformed-input checks.

## A client is disconnected during movement

Malformed, oversized, unsupported, or inconsistent PlayerAuthInput data is rejected at the protocol boundary. Preserve sanitized server diagnostics, but never publish login JWTs, identity chains, encryption keys, raw packet captures containing personal data, or credentials.

## Settings or streamed terrain do not work

A settings parse error names the offending key and stops before binding UDP.
Correct the invalid value using the documented range. Do not work around an
invalid key by silently deleting limits.

For missing terrain, record the sanitized effective view distance, player chunk
coordinate, queued and sent chunk counts, and whether generation reached its
per-tick budget. A white or empty area beyond loaded grass normally indicates a
streaming or serialization failure, not a need to raise an unbounded radius.
Never attach authentication chains or encryption material to a report.

## Console commands do not run

Confirm that `console.enabled=true` and the server process has an open standard
input stream. Detached service managers may close input; use
`console.enabled=false` when no interactive console is expected. An unknown
command, denied permission, unsupported sender, malformed quoted argument, or
disabled owner produces bounded feedback in the console and `logs/server.log`.

Bedriox does not currently accept slash commands from a Bedrock player. A
plugin may define player-capable commands for the stable sender contract, but
only console input reaches the dispatcher in this release.

## PluginTools does not load or package a source plugin

Confirm that `PluginTools.phar` is directly inside `plugins/` and the source
project is a direct child with `plugin.json` and its namespaced entry point
under `src/`. Restart after adding or changing source. Do not install a PHAR
and source project with the same plugin name.

`makeplugin` accepts the discovered manifest name, not a path. Its output is
under `plugin_data/PluginTools/`. If output exists, run
`makeplugin MyPlugin --overwrite` only when replacement is intentional. Check
the PluginTools warnings for unsafe paths, links, limits, manifest errors,
dependency failures, or a missing packaging child process. A failed overwrite
should preserve the previous archive and checksum.

## Reporting a problem

Include the Bedriox version, PHP version, operating system, exact sanitized command, authentication mode, reproduction steps, and relevant bounded log excerpts. Use the private process in the owning repository's `SECURITY.md` for suspected vulnerabilities.
