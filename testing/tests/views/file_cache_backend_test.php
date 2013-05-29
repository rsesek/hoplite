<?php
// Hoplite
// Copyright (c) 2013 Blue Static
//
// This program is free software: you can redistribute it and/or modify it
// under the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or any later version.
//
// This program is distributed in the hope that it will be useful, but WITHOUT
// ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
// FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
// more details.
//
// You should have received a copy of the GNU General Public License along with
// this program.  If not, see <http://www.gnu.org/licenses/>.

namespace hoplite\test;
use hoplite\views as views;

require_once HOPLITE_ROOT . '/views/file_cache_backend.php';

class TestFileCacheBackend extends views\FileCacheBackend
{
  public function T_CachePath($path)
  {
    return $this->_CachePath($path);
  }
}

class FileCacheBackendTest extends \PHPUnit_Framework_TestCase
{
  public function setUp()
  {
    $path = dirname(__FILE__) . '/cache/';
    $this->fixture = new TestFileCacheBackend($path);

    $files = scandir($path);
    foreach ($files as $file)
      if ($file[0] != '.')
        unlink($path . $file);
  }

  public function testCacheCompiled()
  {
    $files = scandir($this->fixture->cache_path());
    $this->assertEquals(2, count($files));  // Only dotfiles.

    $this->assertNull($this->fixture->GetTemplateDataForName('cache_test', time()));

    $string = 'This is a test.';

    $this->fixture->StoreCompiledTemplate('cache_test', time(), $string);
    $files = scandir($this->fixture->cache_path());
    $this->assertEquals(3, count($files));

    $actual = file_get_contents($this->fixture->cache_path() . '/cache_test.phpi');
    $this->assertEquals($string, $actual);
  }

  public function testCacheHit()
  {
    $path = $this->fixture->cache_path() . '/cache_test.phpi';
    $expected = 'Cache hit!';
    file_put_contents($path, $expected);

    $files = scandir($this->fixture->cache_path());
    $this->assertEquals(3, count($files));

    $actual = $this->fixture->GetTemplateDataForName('cache_test', filemtime($path));
    $this->assertEquals($expected, $actual);
  }

  public function testCacheMiss()
  {
    file_put_contents($this->fixture->cache_path() . '/cache_test.phpi', 'Invalid template data');
    $files = scandir($this->fixture->cache_path());
    $this->assertEquals(3, count($files));

    $actual = $this->fixture->GetTemplateDataForName('cache_test', time() + 60);
    $this->assertNull($actual);
  }
}
