<?php
// Hoplite
// Copyright (c) 2011 Blue Static
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

require_once HOPLITE_ROOT . '/views/template_loader.php';

class TestTemplateLoader extends views\TemplateLoader
{
  public function T_CachePath($path)
  {
    return $this->_CachePath($path);
  }

  public function T_LoadIfCached($name)
  {
      return $this->_LoadIfCached($name);
  }
}

class TemplateLoaderTest extends \PHPUnit_Framework_TestCase
{
  public function setUp()
  {
    $this->fixture = new TestTemplateLoader();

    $path  = dirname(__FILE__) . '/cache/';
    $files = scandir($path);
    foreach ($files as $file)
      if ($file[0] != '.')
        unlink($path . $file);
  }

  protected function _SetUpPaths()
  {
    $this->fixture->set_template_path(dirname(__FILE__) . '/');
    $this->fixture->set_cache_path($this->fixture->template_path() . 'cache/');
  }

  public function testTemplatePath()
  {
    $this->assertEquals('%s.tpl', $this->fixture->template_path());

    $path = '/webapp/views/%s.tpl';
    $this->fixture->set_template_path($path);
    $this->assertEquals($path, $this->fixture->template_path());
  }

  public function testSetCachePath()
  {
    $this->assertEquals('/tmp/phalanx_views', $this->fixture->cache_path());

    $path = '/cache/path';
    $this->fixture->set_cache_path($path);
    $this->assertEquals($path, $this->fixture->cache_path());
  }

  public function testCachePath()
  {
    $this->fixture->set_cache_path('/test/value/');
    $this->assertEquals('/test/value/name.phpi', $this->fixture->T_CachePath('name'));
  }

  public function testCacheMiss()
  {
    $this->_SetUpPaths();

    $files = scandir($this->fixture->cache_path());
    $this->assertEquals(2, count($files));  // Only dotfiles.

    $this->fixture->Load('cache_test');

    $files = scandir($this->fixture->cache_path());
    $this->assertEquals(3, count($files));

    $expected = file_get_contents(sprintf($this->fixture->template_path(), 'cache_test'));
    $actual   = file_get_contents($this->fixture->cache_path() . '/cache_test.phpi');
    $this->assertEquals($expected, $actual);
  }

  public function testCacheHit()
  {
    $this->_SetUpPaths();

    $expected = 'Cache hit!';
    file_put_contents($this->fixture->cache_path() . '/cache_test.phpi', $expected);

    $files = scandir($this->fixture->cache_path());
    $this->assertEquals(3, count($files));

    $this->fixture->Load('cache_test');

    $files = scandir($this->fixture->cache_path());
    $this->assertEquals(3, count($files));

    $actual = file_get_contents($this->fixture->cache_path() . '/cache_test.phpi');
    $this->assertEquals($expected, $actual);
  }

  public function testCacheInvalidate()
  {
    $this->_SetUpPaths();
    file_put_contents($this->fixture->cache_path() . '/cache_test.phpi', 'Invalid template data');

    // Need to wait for the mtime to make a difference.
    sleep(1);
    clearstatcache();

    // Touch the template to update its mtime.
    touch(sprintf($this->fixture->template_path(), 'cache_test'));

    $files = scandir($this->fixture->cache_path());
    $this->assertEquals(3, count($files));

    $this->fixture->Load('cache_test');

    $files = scandir($this->fixture->cache_path());
    $this->assertEquals(3, count($files));

    $expected = file_get_contents(sprintf($this->fixture->template_path(), 'cache_test'));
    $actual   = file_get_contents($this->fixture->cache_path() . '/cache_test.phpi');
    $this->assertEquals($expected, $actual);
  }
}
