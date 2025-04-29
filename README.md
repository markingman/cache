# Cache Handler

Simple PHP file cache

## Installation

To use, require in `composer.json`, e.g:

```
composer require markingman/cache
```

## Usage

Example:  
`$Cache = new MarkIngman\Cache\Cache('/path/to/cache/store');  

$Cache->put('index-name', 'cache-value', 900);

if ($value = $Cache->get('index-name')) {
	echo $value;
}`