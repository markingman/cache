# Cache Handler

Simple PHP file cache

## Installation

To use, require in `composer.json`, e.g:

```
composer require markingman/cache
```

## Usage

Example:  
```php
<?php

use MarkIngman\Cache\Cache;

$Cache = Cache('/path/to/cache/store');  

$Cache->put('index-name', 'cache-value', 900);

if ($value = $Cache->get('index-name')) {
	echo $value;
}
```