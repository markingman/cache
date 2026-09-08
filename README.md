# Cache Handler

A simple file-based cache for PHP applications.

## Status

This is a small utility library shared for convenience.  
Maintenance is best-effort and may be minimal.

## Installation

Install via Composer:

```bash
composer require markingman/cache
```

## Usage Overview

Instantiate with path to cache directory default TTL.

```php
$cache = new Cache('/path/to/cache/store', 3600);
```

Put values by index name and optional TTL override.

```php
$cache->put('index-name', 'cache-value', 900);
```

Get values by index name.

```php
$value = $cache->get('index-name');

if (!empty($value) and is_string($value)) {
    echo $value;
}
```

Forward slash indexes create sub directories.

```php
$value = $cache->get('sub/path/index-name');
```

Values are JSON encoded.

```php
$value1 = $cache->put('index1', true);
$value2 = $cache->put('index2', 123);
$value3 = $cache->put('index3', (object)['one' => 'test']);
$value4 = $cache->put('index4', $complexObject->custom_serialize());
```

Call clean up periodically.
```php
$cache->gc(max: 1000);
```

## Notes

- Cache is file-based (no external dependencies)
- Ensure the cache directory is writable
- Can use interface to evolve to other storage (e.g. Redis, SQL etc)

## License

This project is licensed under the MIT License.