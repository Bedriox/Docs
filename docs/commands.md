# Commands

Bedriox provides one typed command model for the server console and Bedrock
slash-command input. The server advertises only commands the authenticated
player may use and routes both sender types through the same bounded parser.

## Experience

Operators with `bedriox.command.experience` can use `experience` or its `xp`
alias:

```text
/experience query <player>
/experience set <player> <amount> [points|levels]
/experience add <player> <amount> [points|levels]
```

The optional unit defaults to points. Values are bounded, levels are derived
from the authoritative total, and negative `add` amounts cannot reduce the
total below zero. Command changes use the same cancellable experience event and
committed post-event as orb collection, processing, and plugin requests.

## World time

Operators with `bedriox.command.time` can use:

```text
/time set day
/time set 13000
/time add 1000
/time query
/time stop
/time start
```

Named positions are represented by the typed `WorldTimePreset` enum. Time is
owned and persisted by each world, advances once per world tick, and is bounded
to the current Bedrock packet range. Natural spawning, player login, periodic
client correction, and command changes all consume the same authoritative
value. A stopped daylight cycle is a runtime choice and resumes after restart.

## Effects and particles

Operators with `bedriox.command.effect` can use the typed effect catalog:

```text
/effect <player> <effect> [seconds] [amplifier] [hideParticles]
/effect <player> <effect> infinite [amplifier] [hideParticles]
/effect <player> clear [effect]
```

Effect names omit the `minecraft:` prefix. Seconds default to 30, amplifiers
default to zero, and `hideParticles` defaults to `false`. These commands use
the same cancellable authoritative effect path as plugins and potion delivery.

Players with `bedriox.command.particle` can spawn a named current-version
particle in their current loaded world:

```text
/particle minecraft:heart_particle
/particle minecraft:basic_flame_particle ~ ~1 ~
```

The particle argument must exactly match a registered `ParticleType` value;
short aliases such as `minecraft:heart` and `minecraft:flame` are not accepted.
An unknown particle fails without spawning anything. Coordinates accept the
ordinary relative `~` form. The command is player-only because the world comes
from the player's current session, and delivery still uses chunk visibility
and particle budgets. See
[effects, particles, potions, and brewing](effects-and-particles.md) for the
public API and retail checklist.

## Register a command

Register commands while enabling a plugin:

```php
use Bedriox\Api\Command\AbstractCommand;
use Bedriox\Api\Command\AllowedCommandSenders;
use Bedriox\Api\Command\CommandContext;
use Bedriox\Api\Command\CommandResult;

final class HelloCommand extends AbstractCommand
{
    public function __construct()
    {
        parent::__construct('hello', 'Sends a greeting.');
    }

    protected function aliases(): array
    {
        return ['hi'];
    }

    protected function permission(): ?string
    {
        return 'example.command.hello';
    }

    protected function allowedSenders(): AllowedCommandSenders
    {
        return AllowedCommandSenders::CONSOLE_ONLY;
    }

    public function execute(CommandContext $context): CommandResult
    {
        return $this->success('Hello from Bedriox.');
    }
}

$this->context()->commands()->register(new HelloCommand());
```

Names begin with a lowercase ASCII letter and may contain lowercase letters,
digits, underscores, or hyphens. Descriptions, usage, aliases, permission
nodes, input lines, and parsed arguments are bounded. Lookup is
case-insensitive. Bedriox registers the deterministic `<plugin>:<command>`
form alongside the short name and rejects a later registration whose short
name or alias conflicts with an existing label.

Every registration belongs to its plugin. Disabling or failing that plugin
unregisters its names and aliases. A disabled plugin cannot execute commands.

## Identify the sender

`CommandSender::type()` returns the native `CommandSenderType` enum. Concrete
interfaces provide type-safe access when more information is needed:

```php
use Bedriox\Api\Command\ConsoleCommandSender;
use Bedriox\Api\Command\PlayerCommandSender;

$sender = $context->sender();

if ($sender instanceof ConsoleCommandSender) {
    $sender->sendMessage('This came from the server console.');
}

if ($sender instanceof PlayerCommandSender) {
    $player = $sender->player();
    $sender->sendMessage('Hello, ' . $player->name . '.');
}
```

The player value is the immutable public API view, not the mutable server
player. `AllowedCommandSenders::CONSOLE_ONLY`, `PLAYER_ONLY`, and `ANY` let the
dispatcher enforce the caller policy before the handler executes.

The console has console authority. Permission nodes, UUID-based operator state,
and explicit player grants are checked centrally before plugin code runs.

## Results and events

A handler returns a `CommandResult` created through `success()` or `failure()`.
An optional result message is sent to the command sender. Invalid input is
rejected before `execute()` and Bedriox sends the binding error plus every
generated usage form.

`CommandPreDispatchEvent` runs after lookup, sender restriction, and permission
validation. Cancelling it prevents the handler from running but cannot grant
permission or change the allowed sender type. `CommandDispatchedEvent` is an
immutable observation of completed execution. Both use the normal event
priority, ownership, cleanup, and failure-containment rules.

An uncaught handler exception is attributed to the command owner. Bedriox
disables and cleans up that plugin and its required dependants while healthy
plugins and players continue when server state remains consistent. Raw console
lines are not exposed through `CommandContext` or copied into routine
diagnostics; plugins receive only the bounded label and parsed arguments.

## Console operation

With `console.enabled=true`, Bedriox reads standard input without blocking the
server loop. Input lines, queued commands, arguments, commands per poll, and
plugin-owned cooperative jobs are bounded. Set the option to `false` for a
detached process without an operator input stream.

Command replies use the normal Bedriox logger and appear in `logs/server.log`
when file logging is enabled. Do not put credentials, tokens, or other secrets
in command arguments.

A command must return promptly. Bounded external work may implement
`CommandJob` and be submitted through the command registrar. Bedriox polls jobs
cooperatively and cancels them when their owner disables, but the plugin still
owns task-specific timeouts, process cleanup, and output limits.

See [plugins and API 0.4](plugins.md) for lifecycle and PluginTools packaging.
The [ExamplePlugin](https://github.com/Bedriox/ExamplePlugin) demonstrates safe
console/player sender discrimination without mutating gameplay state.
