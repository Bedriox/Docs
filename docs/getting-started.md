# Getting started

Bedriox currently runs from a source checkout. It requires 64-bit PHP 8.4 or later in the PHP 8.x line, Composer 2, and the PHP extensions required by Composer, including OpenSSL, cURL, and zlib.

## Install and verify

From the Bedriox server repository:

```shell
composer install
composer check
php bin/bedriox --version
```

The complete gate should pass before starting the server.

## Start the server

Options use exact `--name=value` syntax:

```shell
php bin/bedriox serve --bind=0.0.0.0 --port=19132 --name="Bedriox Server" --max-players=20 --auth=FULL
```

`FULL` is the production-oriented authentication mode. It performs fail-closed Xbox authentication setup before binding the UDP socket and therefore requires outbound HTTPS access to the configured Microsoft discovery endpoint.

For isolated local development only, use:

```shell
php bin/bedriox serve --bind=127.0.0.1 --port=19132 --name="Bedriox Development" --max-players=4 --auth=SELF_SIGNED
```

`SELF_SIGNED` accepts self-signed client identity chains and is not a fallback when `FULL` setup fails. Do not expose it as a production authentication mode.

Stop the foreground server with the platform's normal interrupt signal, such as Ctrl+C. See [configuration](configuration.md) for limits, [compatibility](compatibility.md) before choosing a client, and [troubleshooting](troubleshooting.md) when startup fails.

The first successful start creates `bedriox.settings` in the current working
directory. Stop the server, edit the generated file, and start it again to
apply operator settings. Explicit command options override matching file
values. Bedriox remains beta software; successful startup and terrain streaming
do not establish retail-client support.
