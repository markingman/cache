<?php

namespace MarkIngman\Cache;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use InvalidArgumentException;
use RuntimeException;
use SplFileInfo;
use stdClass;

class CacheTest extends TestCase
{
	public int $perm;
	public int $ttl;
	protected string $tmpdir;
	protected CacheInterface $Cache;

	public function setUp(): void
	{
		$this->tmpdir_make();
		$this->perm = 0700;
		$this->ttl = 900;
		$this->Cache = new Cache($this->tmpdir, $this->ttl);
	}

	public function tearDown(): void
	{
		$this->tmpdir_remove();
	}

	public function testCreate(): void
	{
		$this->assertInstanceOf(CacheInterface::class, $this->Cache);
	}

	public function testCreateFailPermission(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Unexpected permission value');

		new Cache('x', prm: 0);
	}

	public function testCreateFailDir(): void
	{
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Could not find cache dir');

		new Cache('-');
	}

	public function testCreateFailPermissionMatch(): void
	{
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Unexpected cache dir permissions');
// TODO: fix
		new Cache($this->tmpdir, prm: 0777);
	}

	public function testPutString(): void
	{
		$key = 'put/test';

		$res = $this->Cache->put($key, 'data');
		$this->assertTrue($res);

		$res = file_get_contents($this->tmpdir . '/' . $key . Cache::SUFFIX);
		$this->assertEquals("\"data\"", $res);
	}

	public function testPutFail(): void
	{
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('JSON encode failed: ');

		$this->Cache->put('path', tmpfile());
	}

	public function testPutFailDir(): void
	{
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Trying to write cache directory where file exists');

		$this->Cache->put('path', 'data');
		$this->Cache->put('path' . Cache::SUFFIX . '/file', 'data');
	}

	public function testPutBoolean(): void
	{
		$this->assertTrue(
			$this->Cache->put('put/test_bool', cache: false)
		);
	}

	public function testPutArray(): void
	{
		$this->assertTrue(
			$this->Cache->put('put/test_array', [1, 2, 'key' => 'value'])
		);
	}

	public function testPutObject(): void
	{
		$data = new stdClass();
		$data->a = 'A';
		$data->b = ['key' => 'value'];

		$this->assertTrue(
			$this->Cache->put('put/test_object', $data)
		);
	}

	public function testGetFail(): void
	{
		$key = 'getfail';
		file_put_contents($this->tmpdir . '/' . $key . Cache::SUFFIX, '/');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('JSON decode failed: ');

		$this->Cache->get($key);
	}

	public function testGetString(): void
	{
		$key = 'get/string';
		$data = 'data';

		$this->Cache->put($key, $data);

		$this->assertEquals($data, $this->Cache->get($key));
	}

	public function testGetBoolean(): void
	{
		$key = 'get/boolean';

		$this->Cache->put($key, false);

		$this->assertFalse($this->Cache->get($key));
	}

// 	public function testPermission(): void
// 	{
// 		$path = 'permission/test';
// 		$data = 'data';
// 
// 		$this->Cache->put($path, $data);
// 		$dir_permission = fileperms($this->tmpdir . '/' . dirname($path));
// 
// 		$dir_permission_str = substr(sprintf('%o', $dir_permission), -4);
// 		$permission_str = substr('0' . sprintf('%o', self::$perm), -4);
// 
// 		$this->assertEquals($permission_str, $dir_permission_str);
// 	}

	public function testGetArray(): void
	{
		$key = 'get/array';
		$data = [1, 2, 'key' => 'value'];

		$this->Cache->put($key, $data);
		$res = $this->Cache->get($key);

		$this->assertEquals($data, $res);
	}

	public function testGetObject(): void
	{
		$key = 'get/object';
		$data = new stdClass();
		$data->a = 'A';
		$data->b = ['key' => 'value'];

		$this->Cache->put($key, serialize($data));
		$res = $this->Cache->get($key);

		$this->assertEquals($data, is_string($res) ? unserialize($res) : '');
	}

	public function testGetWithTTL(): void
	{
		$key = 'get/ttl';
		$data = 'data';

		$this->Cache->put($key, $data);
		touch($this->tmpdir . '/' . $key . Cache::SUFFIX, time() - 1);
		clearstatcache();

		$res = $this->Cache->get($key);
		$this->assertEquals('', $res);

		$this->Cache->put($key, $data, 100);
		touch($this->tmpdir . '/' . $key . Cache::SUFFIX, time() - 101);
		clearstatcache();

		$res = $this->Cache->get($key);
		$this->assertEquals('', $res);

		$this->Cache->put($key, $data, 1000);
		$res = $this->Cache->get($key, 1001);
		$this->assertEquals('', $res);

		$res = $this->Cache->get($key, 500);
		$this->assertEquals($data, $res);

		$this->Cache->put($key, $data, 100);
		$res = $this->Cache->get($key);
		$this->assertEquals($data, $res);

		touch($this->tmpdir . '/' . $key . Cache::SUFFIX, time() - 1);
		clearstatcache();

		$res = $this->Cache->get($key);
		$this->assertEquals('', $res);
	}

	public function testDelete(): void
	{
		$key = 'get/string';
		$data = 'data';

		$this->Cache->put($key, $data);
		$this->assertTrue(file_exists($this->tmpdir . '/' . $key . Cache::SUFFIX));

		$this->assertEquals($data, $this->Cache->get($key));

		$this->Cache->delete($key);
		$this->assertFalse(file_exists($this->tmpdir . '/' . $key . Cache::SUFFIX));

		$this->assertEquals('', $this->Cache->get($key));
	}

	public function testTest(): void
	{
		$key = 'test';
		$data = 'data';

		$this->Cache->put($key, $data, 300);

		$res = $this->Cache->test($key);
		$this->assertTrue($res);

		$res = $this->Cache->test($key, 200);
		$this->assertTrue($res);

		$res = $this->Cache->test($key, 400);
		$this->assertFalse($res);

		touch($this->tmpdir . '/' . $key . Cache::SUFFIX, time() + 50);
		clearstatcache();
		$res = $this->Cache->test($key, 100);
		$this->assertFalse($res);
	}

	public function testGC(): void
	{
		$this->Cache->gc(10000);
		$this->assertEquals([], $this->listFiles($this->tmpdir));

		$this->Cache->put('test1-file', 'data', 100);// 3
		$this->Cache->put('test2/file', 'data', 100);// 1
		$this->Cache->put('test3-file', 'data', 100);// 2
		$this->Cache->put('test4/file', 'data', 200);// 4

		$this->Cache->gc(101, 2);
		$this->assertEquals([
			$this->tmpdir . '/' . 'test1-file' . Cache::SUFFIX,
			$this->tmpdir . '/' . 'test4/file' . Cache::SUFFIX,
		], $this->listFiles($this->tmpdir));
	}

	protected function tmpdir_make(): bool
	{
		$this->tmpdir = rtrim(sys_get_temp_dir(), '/') . '/' . bin2hex(random_bytes(4));

		mkdir($this->tmpdir);

		return file_exists($this->tmpdir);
	}

	protected function tmpdir_remove(): void
	{
		if (is_dir($this->tmpdir)) {
			foreach (
				new RecursiveIteratorIterator(
					new RecursiveDirectoryIterator($this->tmpdir, FilesystemIterator::SKIP_DOTS),
					RecursiveIteratorIterator::CHILD_FIRST
				)
				as $f) {
				if ($f instanceof SplFileInfo) {
					if ($f->isDir()) {
						rmdir($f->getPathname());
					} else {
						unlink($f->getPathname());
					}
				}
			}

			rmdir($this->tmpdir);
		}
	}

	/** @return string[] */
	protected function listFiles(string $dir): array
	{
		$files = [];

		foreach (
			new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
			)
			as $f) {
			if ($f instanceof SplFileInfo and !$f->isDir()) {
				$files[] = $f->getPathname();
			}
		}

		return $files;
	}

}
