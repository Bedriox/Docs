# Bedriox Documentation

Public operator and contributor documentation for Bedriox, an experimental Minecraft Bedrock Edition server written in PHP with bounded input handling, deterministic simulation, and performance-conscious architecture.

Documentation in this repository is licensed under GPL-3.0-only.

Project website: [bedriox.com](https://bedriox.com)

## Start here

- [Purpose and scope](docs/purpose-and-scope.md)
- [Getting started](docs/getting-started.md)
- [Configuration](docs/configuration.md)
- [Plugins and API 0.1](docs/plugins.md)
- [Logging and crash reports](docs/logging-and-crashes.md)
- [Architecture](docs/architecture.md)
- [Player and multiplayer lifecycle](docs/player-multiplayer.md)
- [Repository map](docs/repository-map.md)
- [Change safety](docs/change-safety.md)
- [Client journey](docs/client-journey.md)
- [Compatibility](docs/compatibility.md)
- [Known limitations](docs/known-limitations.md)
- [Testing](docs/testing.md)
- [Troubleshooting](docs/troubleshooting.md)
- [Roadmap](docs/roadmap.md)

The [compatibility page](docs/compatibility.md) is authoritative. Automated protocol coverage does not by itself establish retail-client support.

Architecture decisions and proposals are maintained separately in [RFCs](https://github.com/Bedriox/RFCs).

## Contributing

Use focused changes, preserve the distinction between verified behavior and planned work, and run:

```shell
php tools/validate-docs.php
```

See [CONTRIBUTING.md](CONTRIBUTING.md) and [AGENTS.md](AGENTS.md) before making larger changes.
