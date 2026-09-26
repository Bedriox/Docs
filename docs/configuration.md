# Configuration

The private-alpha executable accepts configuration through
`bedriox.settings` and `serve` command options.

```shell
php bin/bedriox serve --bind=0.0.0.0 --port=19132 --name="Bedriox Server" --max-players=20 --auth=FULL
```

| Option | Default | Contract |
| --- | --- | --- |
| `--bind` | `0.0.0.0` | Literal IPv4 address. Host names and IPv6 addresses are rejected. |
| `--port` | `19132` | Decimal integer from 1 through 65535. |
| `--name` | `Bedriox Server` | Valid UTF-8, 1 through 128 bytes. |
| `--max-players` | `20` | Decimal integer from 1 through 1024. |
| `--auth` | `FULL` | Exactly `FULL` or `SELF_SIGNED`, including case. |

Every option must use `--name=value`. Duplicate options, unknown options, empty values, and out-of-range numeric values fail startup rather than being ignored.

## Authentication modes

`FULL` validates the client identity chain against Microsoft-published Xbox keys. Key discovery happens before the UDP socket is bound; missing cURL support, discovery failure, invalid discovery data, or unusable keys stop startup.

`SELF_SIGNED` is restricted to isolated development. It permits self-signed identity chains but retains the remaining login and input validation. It is never selected automatically.

Neither mode changes the [compatibility contract](compatibility.md). A successful bind or login test does not imply that a retail client version is supported.

## `bedriox.settings`

The first `php bin/bedriox serve` run creates a UTF-8 `bedriox.settings` file in
the current working directory. It uses one `key=value` entry per line. Blank
lines and lines beginning with `#` are ignored. Effective values are resolved
in this order:

```text
built-in defaults -> bedriox.settings -> explicit CLI overrides
```

Unknown keys, duplicates, malformed entries, invalid UTF-8, partial grouped
values, and out-of-range values stop startup before the UDP socket is bound.
Bedriox does not silently rewrite invalid values.

The generated defaults are:

```properties
# Server
server.name=Bedriox Server
server.motd=Powered by Bedriox
server.max-players=20

# Network
network.bind-address=0.0.0.0
network.port=19132
network.authentication=FULL

# Level
level.name=world
level.generator=default
level.seed=0
level.default-gamemode=survival
level.difficulty=normal
level.autosave-interval-ticks=6000

# Chunk streaming
chunks.view-distance=4
chunks.spawn-radius=4
chunks.send-per-tick=4
chunks.generate-per-tick=1
chunks.cache-limit=2048
chunks.save-per-tick=8

# Player persistence
players.autosave-interval-ticks=6000
players.save-per-tick=8

# Runtime
runtime.ticks-per-second=20

# Console
console.enabled=true

# Plugins
plugins.enabled=true
plugins.maximum=64

# Logging and crash reports
logging.level=INFO
logging.console=true
logging.console-colors=auto
logging.file=true
logging.file-max-size=16777216
logging.file-history=10
logging.protocol-trace=false
crash-report.include-player-identifiers=true

# Optional spawn override. Leave all three empty to use the level default.
# level.spawn-x=
# level.spawn-y=
# level.spawn-z=
```

| Setting | Accepted value or range |
| --- | --- |
| `server.name` | Valid UTF-8, 1 through 128 bytes. |
| `server.motd` | Non-control UTF-8, 1 through 128 bytes. |
| `server.max-players` | 1 through 1024. |
| `network.bind-address` | Literal IPv4 address. |
| `network.port` | 1 through 65535. |
| `network.authentication` | Exactly `FULL` or `SELF_SIGNED`. |
| `level.name` | Non-control UTF-8, 1 through 64 bytes. |
| `level.generator` | `default` for the version-one continental overworld or `flat` for the fixed classic profile. |
| `level.seed` | -2147483648 through 2147483647; deterministic for `default`. |
| `level.default-gamemode` | Exactly `survival`. |
| `level.difficulty` | `peaceful`, `easy`, `normal`, or `hard`. |
| `level.autosave-interval-ticks` | 20 through 72000 ticks. |
| `chunks.view-distance` | 1 through 32 chunks. |
| `chunks.spawn-radius` | 1 through the configured view distance. |
| `chunks.send-per-tick` | 1 through 64 per world tick. |
| `chunks.generate-per-tick` | 1 through 64 per world tick. |
| `chunks.cache-limit` | Required view capacity through 65536 chunks. |
| `chunks.save-per-tick` | 1 through 64 dirty chunks per scheduled save tick. |
| `players.autosave-interval-ticks` | 20 through 72000 ticks. |
| `players.save-per-tick` | 1 through 64 dirty player profiles per autosave tick. |
| `runtime.ticks-per-second` | 1 through 100. |
| `console.enabled` | Exactly `true` or `false`; disables standard-input command reading when false. |
| `plugins.enabled` | Exactly `true` or `false`. |
| `plugins.maximum` | 0 through 256 total admitted plugins. |
| `logging.level` | `DEBUG`, `INFO`, `NOTICE`, `WARNING`, `ERROR`, or `CRITICAL`. |
| `logging.console` | Exactly `true` or `false`. |
| `logging.console-colors` | Exactly `auto`, `true`, or `false`. |
| `logging.file` | Exactly `true` or `false`. |
| `logging.file-max-size` | 65536 through 1073741824 bytes. |
| `logging.file-history` | 0 through 100 archives. |
| `logging.protocol-trace` | Exactly `true` or `false`. |
| `crash-report.include-player-identifiers` | Exactly `true` or `false`; defaults to `true`. |
| `level.spawn-x`, `level.spawn-z` | -30000000 through 30000000. |
| `level.spawn-y` | -64 through 319. |

Spawn overrides are all-or-none. When all three values are absent or empty,
the level calculates its default spawn. The default generator performs a
bounded dry-land search over its terrain; the flat generator uses `(0, 64, 0)`.
Supplying all three bounded integer coordinates overrides that position.
Supplying only one or two is an error.

World settings generate chunks as players need them, cap the
negotiated client radius at `chunks.view-distance`, and apply separate
generation and send budgets on each world tick. Generated packets wait in a
bounded staging queue, so generation cannot create unbounded pending network
work.

Existing `level.dat` generator, Bedriox generator version, seed, display name,
and spawn metadata remain authoritative on reopen. An unsupported stored
generator version fails before missing chunks can be generated. A new world creates the native `level.dat`,
`levelname.txt`, and `db/` layout; saved chunks and player block changes are
loaded before any missing coordinate is generated.

Player profiles are stored separately under `player_data/`. Player autosave
uses its own interval and per-tick budget so it does not borrow the chunk save
budget. See [player persistence](player-persistence.md).

## Entity spawning and decisions

The user-facing `server.properties` file contains:

```properties
difficulty=normal
spawn-animals=true
spawn-monsters=true
```

Both spawn switches accept exactly `true` or `false` and affect natural
spawning only. Spawn eggs, `/summon`, and plugin-created entities remain
available when a category is disabled. `difficulty=peaceful` prevents natural
monster spawning regardless of `spawn-monsters`.

The advanced `bedriox.settings` file contains:

```properties
entities.ai.enabled=true
```

Setting it to `false` skips built-in decision and navigation work plus custom
mob `onAiTick()` callbacks. Entity physics, damage, persistence, visibility,
ordinary lifecycle `onTick()` callbacks, explicit spawning, and natural-spawn
ownership remain authoritative. See
[entities and custom mobs](entities.md) for the complete boundary.

The cache must cover every maximum-size simultaneous player view. Bedriox
requires:

```text
required chunks = server.max-players * (2 * chunks.view-distance + 1)^2
required chunks <= chunks.cache-limit <= 65536
```

Invalid combinations fail startup. Active player views remain leased until no
player needs them, and inactive chunks are least-recently-used eviction
candidates.

There is deliberately no `query.port`. Minecraft server-list discovery uses
the configured game port. A separate query setting is deferred until Bedriox
implements and tests a separate query service.
