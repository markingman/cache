<?php

namespace MarkIngman\Cache;

use FilesystemIterator;
use InvalidArgumentException;
use JsonException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use const JSON_THROW_ON_ERROR;

class Cache implements CacheInterface
{
	const string SUFFIX = '.cache';

	protected string $dir;
	protected int $prm = 0700;
	protected int $ttl_default = 900;

	public function __construct(string $dir, ?int $ttl_default = null, ?int $prm = null)
	{
		if (!is_null($prm)) {
			if (!in_array($prm, [0700, 0750, 0755, 0770, 0775, 0777, 0711], true)) {
				throw new InvalidArgumentException('Unexpected permission value');
			}

			$this->prm = $prm;
		}

		if (!is_dir($dir)) {
			throw new RuntimeException('Could not find cache dir');
		}

		if ((fileperms($dir) & $this->prm) !== $this->prm) {
			throw new RuntimeException('Unexpected cache dir permissions');
		}

		$this->dir = $dir;

		if (!is_null($ttl_default)) {
			$this->ttl_default = $ttl_default;
		}
	}

	public function put(string $key, mixed $cache, ?int $ttl = null): bool
	{
		if (strpos($key, DIRECTORY_SEPARATOR)) {
			$dir = dirname($key);
			if (!file_exists($this->dir . DIRECTORY_SEPARATOR . $dir)) {
				mkdir(directory: $this->dir . DIRECTORY_SEPARATOR . $dir, recursive: true);
			} elseif (!is_dir($this->dir . DIRECTORY_SEPARATOR . $dir)) {
				throw new RuntimeException('Trying to write cache directory where file exists');
			}
		}

		$tmp = tempnam($this->dir, 'tmp');

		if (file_put_contents($tmp, $this->encode($cache))) {
			touch($tmp, time() + ($ttl ?? $this->ttl_default));

			return rename($tmp, $this->get_path($key));
		} else {
			return false;
		}
	}

	public function get(string $key, ?int $ttl = null): mixed
	{
		if ($this->test($key, $ttl ?? 0)) {
			if ($cache = (string)file_get_contents($this->get_path($key))) {
				return $this->decode($cache);
			}
		}

		return '';
	}

	public function delete(string $key, ?int $max = null): bool
	{
		$max ??= PHP_INT_MAX;
		$n = 0;
		$fp = $this->get_path($key);

		if (is_dir($fp)) {
			foreach (
				new RecursiveIteratorIterator(
					new RecursiveDirectoryIterator($fp, FilesystemIterator::SKIP_DOTS),
					RecursiveIteratorIterator::CHILD_FIRST
				)
				as $f) {
				if ($f instanceof SplFileInfo) {
					if ($f->isDir()) {
						if (!(new FilesystemIterator($f->getPathname()))->valid()) {
							rmdir($f->getPathname());
						}
					} elseif (str_ends_with($f->getPathname(), static::SUFFIX)) {
						unlink($f->getPathname());
						$n++;
					}
				}
				if ($n >= $max) {
					return false;
				}
			}

			if (!(new FilesystemIterator($fp))->valid()) {
				return rmdir($fp);
			}
		} elseif (is_file($fp) and str_ends_with($fp, static::SUFFIX)) {
			return unlink($fp);
		}

		return false;
	}

	public function test(string $key, int $ttl = 0): bool
	{
		$fp = $this->get_path($key);

		return (is_readable($fp) and filemtime($fp) >= time() + $ttl);
	}

	public function gc(?int $ttl = null, ?int $max = null): int
	{
		$max ??= PHP_INT_MAX;
		$n = 0;
		$t = time() + ($ttl ?? 0);

		foreach (
			new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($this->dir, FilesystemIterator::SKIP_DOTS),
				RecursiveIteratorIterator::CHILD_FIRST
			)
			as $f) {
			if ($f instanceof SplFileInfo) {
				if ($f->isDir()) {
					if (!(new FilesystemIterator($f->getPathname()))->valid()) {
						rmdir($f->getPathname());
					}
				} else {
					if ($f->getMtime() < $t and str_ends_with($f->getPathname(), static::SUFFIX)) {
						unlink($f->getPathname());
						$n++;
					}
				}
			}
			if ($n >= $max) {
				break;
			}
		}

		return $n;
	}

	function decode(string $value): mixed
	{
		try {
			return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
		} catch (JsonException $e) {
			throw new RuntimeException(message: 'JSON decode failed: ' . $e->getMessage(), previous: $e);
		}
	}

	protected function get_path(string $key): string
	{
		return $this->dir . DIRECTORY_SEPARATOR . $key . static::SUFFIX;
	}

	protected function encode(mixed $value): string
	{
		try {
			return json_encode($value, JSON_THROW_ON_ERROR);
		} catch (JsonException $e) {
			throw new RuntimeException(message: 'JSON encode failed: ' . $e->getMessage(), previous: $e);
		}
	}
}
