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

require_once HOPLITE_ROOT . '/views/cache_backend.php';
require_once HOPLITE_ROOT . '/views/template_loader.php';

class TemplateLoaderTest extends \PHPUnit_Framework_TestCase
{
  public function setUp()
  {
    $this->fixture = new views\TemplateLoader();
    $this->fixture->set_template_path(dirname(__FILE__) . '/%s.tpl');

    $this->cache = $this->getMock('\\hoplite\\views\\CacheBackend');
    $this->fixture->set_cache_backend($this->cache);
  }

  public function testTemplatePath()
  {
    $path = '/webapp/views/%s.tpl';
    $this->fixture->set_template_path($path);
    $this->assertEquals($path, $this->fixture->template_path());
  }

  public function testCacheMiss()
  {
    $this->cache->expects($this->once())
                ->method('GetTemplateDataForName')
                ->with($this->equalTo('cache_test'))
                ->will($this->returnValue(NULL));

    $expected = file_get_contents(sprintf($this->fixture->template_path(), 'cache_test'));

    $this->cache->expects($this->once())
                ->method('StoreCompiledTemplate')
                ->with($this->equalTo('cache_test'),
                       $this->greaterThan(0),
                       $this->equalTo($expected));

    $this->assertEquals($expected, $this->fixture->Load('cache_test')->template());
    $this->assertEquals($expected, $this->fixture->Load('cache_test')->template());
  }

  public function testCacheHit()
  {
    $expected = 'Cache hit!';
    $this->cache->expects($this->once())
                ->method('GetTemplateDataForName')
                ->with($this->equalTo('cache_test'))
                ->will($this->returnValue($expected));

    // The cache backend is only consulted once.
    $this->assertEquals($expected, $this->fixture->Load('cache_test')->template());
    $this->assertEquals($expected, $this->fixture->Load('cache_test')->template());
    $this->assertEquals($expected, $this->fixture->Load('cache_test')->template());
    $this->assertEquals($expected, $this->fixture->Load('cache_test')->template());
  }

  public function testSingleton()
  {
    $fixture = views\TemplateLoader::GetInstance();
    $this->assertNotSame($fixture, $this->fixture);

    views\TemplateLoader::SetInstance($this->fixture);
    $this->assertSame(views\TemplateLoader::GetInstance(), $this->fixture);

    $template = views\TemplateLoader::Fetch('cache_test');
    $this->assertEquals('This file should be cached.', $template->template());
  }
}
