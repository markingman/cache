<?php

namespace MarkIngman\Cache;

interface CacheInterface
{
	public function put(string $key, mixed $cache, ?int $ttl = null): bool;

	public function get(string $key, ?int $ttl = null): mixed;

	public function delete(string $key): bool;

	public function test(string $key, int $ttl = 0): bool;

	public function gc(?int $ttl = null, ?int $max = null): int;
}
