# Cache Handler

A simple file-based cache for PHP.

## Status

This is a small utility library shared for convenience.  
Maintenance is best-effort and may be minimal.

## Installation

Install via Composer:

```bash
composer require markingman/cache
```

## Usage

```php
<?php

use MarkIngman\Cache\Cache;

$cache = new Cache('/path/to/cache/store');

$cache->put('index-name', 'cache-value', 900);

if ($value = $cache->get('index-name')) {
    echo $value;
}
```

## API

### `__construct(string $path)`
Create a new cache instance.

- `$path` — Directory where cache files are stored

### `put(string $key, mixed $value, ?int $ttl = null)`
Store a value in the cache.

- `$key` — Cache key  
- `$value` — Value to store  
- `$ttl` — Time to live (seconds)

### `get(string $key): mixed|null`
Retrieve a value from the cache.

- Returns cached value or `null` if expired/not found

## Notes

- Cache is file-based (no external dependencies)
- Ensure the cache directory is writable
- Can use interface to evolve to other storage (e.g. Redis, SQL etc)

## License

This project is licensed under the MIT License.