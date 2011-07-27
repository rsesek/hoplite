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
/*
namespace hoplite\test;
use hoplite\views\Template;

require_once TEST_ROOT . '/tests/views.php';

class ViewTest extends \PHPUnit_Framework_TestCase
{
  public $saved_singleton = array();

  public function setUp()
  {
    $this->saved_singleton['template_path'] = View::template_path();
    $this->saved_singleton['cache_path']  = View::cache_path();

    $path  = dirname(__FILE__) . '/data/cache/';
    $files = scandir($path);
    foreach ($files as $file)
      if ($file[0] != '.')
        unlink($path . $file);
  }

  public function tearDown()
  {
    View::set_template_path($this->saved_singleton['template_path']);
    View::set_cache_path($this->saved_singleton['cache_path']);
  }

  public function testTemplatePath()
  {
    $this->assertEquals('%s.tpl', View::template_path());

    $path = '/webapp/views/%s.tpl';
    View::set_template_path($path);
    $this->assertEquals($path, View::template_path());
  }

  public function testSetCachePath()
  {
    $this->assertEquals('/tmp/phalanx_views', View::cache_path());

    $path = '/cache/path';
    View::set_cache_path($path);
    $this->assertEquals($path, View::cache_path());
  }

  public function testCtorAndTemplateName()
  {
    $view = $this->getMock('phalanx\views\View', NULL, array('test_tpl'));
    $this->assertEquals('test_tpl', $view->template_name());

    $this->assertType('phalanx\base\Dictionary', $view->vars());
    $this->assertEquals(0, $view->vars()->Count());
  }

  public function testProcessTemplateEntities()
  {
    $view = new TestView('test');
    $data = '<strong>Some day, is it not, <'.'?php echo "Rob" ?'.'>?</strong>';
    $this->assertEquals($data, $view->T_ProcessTemplate($data));
  }

  public function testProcessTemplateMacro()
  {
    $view = new TestView('test');
    $in   = 'foo $[some.value] bar';
    $out  = 'foo <?php echo $view->GetHTML("some.value") ?> bar';
    $this->assertEquals($out, $view->T_ProcessTemplate($in));
  }

  public function testProcessTemplateShortTags()
  {
    $view = new TestView('test');
    $in   = 'foo <?php echo "Not this one"; ?> bar <? echo "But this one!" ?> moo';
    $out  = 'foo <?php echo "Not this one"; ?> bar <?php echo "But this one!" ?> moo';
    $this->assertEquals($out, $view->T_ProcessTemplate($in));
  }

  public function testMagicGetterSetter()
  {
    $view = new View('test');
    $view->foo = 'abc';
    $this->assertEquals('abc', $view->foo);
    $this->assertEquals('abc', $view->vars()->Get('foo'));

    $view->foo = array();
    $view->{"foo.bar"} = '123';
    $this->assertEquals('123', $view->{"foo.bar"});
    $this->assertEquals('123', $view->vars()->Get('foo.bar'));
  }

  public function testCachePath()
  {
    View::set_cache_path('/test/value');
    $view = new TestView('name');
    $this->assertEquals('/test/value/name.phpi', $view->T_CachePath($view->template_name()));
  }

  public function testCacheMiss()
  {
    TestView::SetupPaths();
    $view = new TestView('cache_test');

    $files = scandir(View::cache_path());
    $this->assertEquals(2, count($files));  // Only dotfiles.

    $view->T_Cache();

    $files = scandir(View::cache_path());
    $this->assertEquals(3, count($files));

    $expected = file_get_contents(sprintf(View::template_path(), 'cache_test'));
    $actual   = file_get_contents(View::cache_path() . '/cache_test.phpi');
    $this->assertEquals($expected, $actual);
  }

  public function testCacheHit()
  {
    $expected = 'Cache hit!';
    TestView::SetupPaths();
    file_put_contents(View::cache_path() . '/cache_test.phpi', $expected);
    $view = new TestView('cache_test');

    $files = scandir(View::cache_path());
    $this->assertEquals(3, count($files));

    $view->T_Cache();

    $files = scandir(View::cache_path());
    $this->assertEquals(3, count($files));

    $actual = file_get_contents(View::cache_path() . '/cache_test.phpi');
    $this->assertEquals($expected, $actual);
  }

  public function testCacheInvalidate()
  {
    TestView::SetupPaths();
    file_put_contents(View::cache_path() . '/cache_test.phpi', 'Invalid template data');
    $view = new TestView('cache_test');

    // Need to wait for the mtime to make a difference.
    sleep(1);
    clearstatcache();

    // Touch the template to update its mtime.
    touch(sprintf(View::template_path(), 'cache_test'));

    $files = scandir(View::cache_path());
    $this->assertEquals(3, count($files));

    $view->T_Cache();

    $files = scandir(View::cache_path());
    $this->assertEquals(3, count($files));

    $expected = file_get_contents(sprintf(View::template_path(), 'cache_test'));
    $actual   = file_get_contents(View::cache_path() . '/cache_test.phpi');
    $this->assertEquals($expected, $actual);
  }

  public function testRender()
  {
    TestView::SetupPaths();

    $view = new TestView('render_test');
    $view->name = 'Rob';

    ob_start();
    $view->Render();
    $actual = ob_get_contents();
    ob_end_clean();

    $this->assertEquals('Hi, my name is Rob. This is undefined: .', $actual);
  }
}
*/