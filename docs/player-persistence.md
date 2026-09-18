# Player persistence

Bedriox keeps player state across clean disconnects and server restarts. Each
authenticated identity owns one file:

```text
player_data/<authenticated-uuid>.dat
```

The UUID is normalized and selects the file. A player's current name and XUID
are stored as diagnostic metadata but never become path components.

## Stored state

The current versioned record contains:

- authenticated UUID, XUID, and last known player name;
- first-played and last-played timestamps;
- world name, exact position, yaw, and pitch;
- the supported survival game mode;
- 36 main-inventory slots, the selected hotbar slot, and the cursor stack.

Items use canonical identifiers such as `minecraft:grass_block`. Bedrock stack
network IDs belong to one connection and are allocated again on every login.
JWTs, encryption keys, login chains, credentials, and raw packets are not
stored.

## Login behavior

The profile is loaded after authentication and before StartGame, initial
inventory synchronization, or chunk scheduling. The same resolved state is
used for the client bootstrap and the authoritative server-side player.

A valid returning position in the active world is restored exactly. Bedriox
does not relocate it because of collision, headroom, liquid, terrain, or fall
risk. A first-time player starts at the world's calculated spawn. If the saved
world is unavailable, the active world's spawn is used while the saved
inventory and identity history remain intact.

Plugins receive a cancellable `PlayerLoginEvent` after restoration but before
streaming. They may choose a bounded destination and orientation. The later
`PlayerJoinEvent` still means that the client completed initialization and
entered the world.

## Saving and recovery

Routine player saving is controlled independently by:

```properties
players.autosave-interval-ticks=6000
players.save-per-tick=8
```

Only committed server-authoritative movement and inventory state is captured.
Writes use a temporary sibling file, flush it, and replace the destination
atomically. A failed write remains dirty for a later bounded retry.

Corrupt, unreadable, oversized, or unsupported files are preserved in place.
Only the affected login is rejected. Bedriox does not reinterpret that player
as new and does not overwrite the file with default state. Back up
`player_data/` together with `worlds/` while the server is stopped.
