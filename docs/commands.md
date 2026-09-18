# Commands

Bedriox API 0.1 provides one typed command model for the server console and
future player command input. The current executable dispatches console input
only; receiving slash commands from a Bedrock client is not implemented.

## Register a command

Register commands while enabling a plugin:

```php
use Bedriox\Api\Command\AllowedCommandSenders;
use Bedriox\Api\Command\CommandContext;
use Bedriox\Api\Command\CommandDefinition;
use Bedriox\Api\Command\CommandResult;

$this->context()->commands()->register(
    new CommandDefinition(
        'hello',
        'Sends a greeting.',
        'hello',
        aliases: ['hi'],
        permission: 'example.command.hello',
        allowedSenders: AllowedCommandSenders::CONSOLE_ONLY,
    ),
    function (CommandContext $context): CommandResult {
        if ($context->arguments() !== []) {
            return CommandResult::USAGE;
        }
        $context->sender()->sendMessage('Hello from Bedriox.');
        return CommandResult::SUCCESS;
    },
);
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

The console has console authority. Permission nodes are checked centrally, but
persistent player assignments, groups, and operator management remain future
work. Plugin code should still declare permissions now so the same definition
works when player command input is introduced.

## Results and events

A handler returns:

- `CommandResult::SUCCESS` when it completed;
- `CommandResult::FAILURE` when it handled a bounded failure; or
- `CommandResult::USAGE` when Bedriox should display the registered usage.

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

See [plugins and API 0.1](plugins.md) for lifecycle and PluginTools packaging.
The [ExamplePlugin](https://github.com/Bedriox/ExamplePlugin) demonstrates safe
console/player sender discrimination without mutating gameplay state.
