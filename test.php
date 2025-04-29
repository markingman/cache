<?php

$f = new FilesystemIterator(__DIR__ . '/src');

var_export($f->valid());
echo PHP_EOL;

foreach ($f as $v) {
	echo $v, PHP_EOL;
}

