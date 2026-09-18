# Logging and crash reports

Bedriox writes useful lifecycle, world, network, plugin, player, warning, and
shutdown records without logging every packet, movement, tick, or chunk.

The console format is:

```text
[17-Sep-2026 21:42:10] Bedriox INFO > Starting Bedriox 0.1.0-alpha.1
[17-Sep-2026 21:42:11] Bedriox INFO > [ExamplePlugin] Plugin enabled
```

Levels are `DEBUG`, `INFO`, `NOTICE`, `WARNING`, `ERROR`, and `CRITICAL`.
Console colors are automatic by default and absent from files. Plugin loggers
are bound to their plugin name.

## Settings

```properties
logging.level=INFO
logging.console=true
logging.console-colors=auto
logging.file=true
logging.file-max-size=16777216
logging.file-history=10
logging.protocol-trace=false
crash-report.include-player-identifiers=true
```

`logging.console-colors` accepts `auto`, `true`, or `false`. File size ranges
from 65536 through 1073741824 bytes; history ranges from 0 through 100.
Protocol trace remains bounded and disabled by default.

Plain UTF-8 output appends to `logs/server.log`. At the configured size it
rotates into `logs/archive/` and retains the configured number of archives.
A file sink failure produces a bounded console warning and does not change
gameplay behavior.

## Crash reports

Supported uncaught exceptions and fatal shutdown errors create an atomic local
report such as:

```text
crashes/2026-09-18_01-42-33_UTC.txt
```

Reports include the Bedriox, PHP and operating-system versions, time, tick,
memory, failure and bounded trace, active plugin attribution, recent logs, player
count, and relevant session evidence. They exclude token and JWT contents,
encryption secrets, passwords, credentials, raw packets, complete settings,
`phpinfo()` output, raw source excerpts, and known private root paths.

Player identifiers are enabled by default because names, UUIDs, XUIDs, remote
IP addresses and ports, platform, and session phase can be necessary to
diagnose account or multiplayer failures. These reports contain sensitive
personal and network data. Restrict access and redact them before sharing.
Set `crash-report.include-player-identifiers=false` to omit those identifiers
from future reports.

Crash reports are never uploaded automatically. Bedriox cannot reliably write
one after a segmentation fault, forced operating-system termination, power
loss, or an infinite same-process plugin loop; the last flushed server log and
an external service manager may be the only evidence.
