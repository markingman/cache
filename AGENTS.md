# Repository guidance

## Project

`markingman/cache` is a lightweight PHP file cache library requiring PHP 8.3 or newer. Its namespace is `MarkIngman\Cache`.

- `src/Cache.php`: filesystem storage, JSON serialization, TTL checks, deletion, and garbage collection.
- `src/CacheInterface.php`: public cache contract.
- `tests/CacheTest.php`: PHPUnit tests using temporary cache directories.
- `tests/bootstrap.php`: loads Composer's autoloader.

## Development and validation

Install dependencies with `composer install`. Dependencies and `composer.lock` are ignored by Git.

The repository provides Docker development commands:

- `make build8.3`, `make build8.4`, or `make build8.5`: build the `cache-test` image and install dependencies.
- `make test`: run PHPUnit with Xdebug coverage using the built image.
- `make analyse`: run PHPStan at level 10 against `src` and `tests`.

With dependencies and a compatible local PHP runtime, use `vendor/bin/phpunit` and `vendor/bin/phpstan analyse -c phpstan.neon`. CI builds and checks PHP 8.3, 8.4, and 8.5. Check development dependencies' runtime requirements when selecting a PHP version; the library's minimum may differ from the tooling's minimum.

## Editing conventions

Follow the existing PHP style: tab indentation, braces on separate lines for classes and methods, typed parameters and return values, and existing naming conventions. Keep changes focused and preserve public API behavior unless the task explicitly calls for a change.

Cache values are JSON encoded, cache files use the `.cache` suffix, and file modification times represent expiry. Account for nested keys, permissions, expiry boundaries, and cleanup behavior when changing filesystem operations. Add or adjust meaningful tests for behavioral changes.

Do not edit generated `vendor/` or `phpunit-coverage/` artifacts. Preserve existing uncommitted user changes. The README states that external contributions are not currently accepted; do not assume a contribution or publishing workflow is required.
