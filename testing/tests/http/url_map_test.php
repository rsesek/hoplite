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
use hoplite\http as http;

require_once HOPLITE_ROOT . '/http/url_map.php';

class UrlMapTest extends \PHPUnit_Framework_TestCase
{
  public function setUp()
  {
    $globals = array();
    $this->fixture = new http\UrlMap(new http\RootController($globals));
  }

  public function testSimpleEvaluate()
  {
    $map = array(
      'action/two' => 'Invalid',
      'some/long/action' => 'Valid'
    );
    $this->fixture->set_map($map);

    $request = new http\Request('some/long/action');
    $this->assertEquals('Valid', $this->fixture->Evaluate($request));

    $request = new http\Request('another/action');
    $this->assertNull($this->fixture->Evaluate($request));
  }

  public function testSimpleExtendedEvaluate()
  {
    $map = array(
      'one/two/three' => 'Invald',
      'another/action' => 'Valid',
      'four/five/six' => 'Invalid2'
    );
    $this->fixture->set_map($map);

    $request = new http\Request('another/action/thing');
    $this->assertEquals('Valid', $this->fixture->Evaluate($request));
  }

  public function testSimplePrecedenceEvaluate()
  {
    $map = array(
      'some/action/first' => 'First',
      'some/action' => 'Second',
      'some/action/second' => 'Third'
    );
    $this->fixture->set_map($map);

    $request = new http\Request('some/action/first');
    $this->assertEquals('First', $this->fixture->Evaluate($request));

    $request = new http\Request('some/action/second');
    $this->assertEquals('Second', $this->fixture->Evaluate($request));

    $request = new http\Request('some/action/third');
    $this->assertEquals('Second', $this->fixture->Evaluate($request));
  }

  public function testEmptyRule()
  {
    $map = array(
      'some/first' => 'First',
      '' => 'Index',
      'some/second' => 'Second'
    );
    $this->fixture->set_map($map);

    $request = new http\Request('some/first');
    $this->assertEquals('First', $this->fixture->Evaluate($request));

    $request = new http\Request('');
    $this->assertEquals('Index', $this->fixture->Evaluate($request));

    $request = new http\Request('some/second');
    $this->assertEquals('Second', $this->fixture->Evaluate($request));
  }

  public function testExtractSingle()
  {
    $map = array(
      'action/one' => 'First',
      'user/view/{id}' => 'Second',
      'user/add' => 'Third'
    );
    $this->fixture->set_map($map);

    $request = new http\Request('user/view/42');
    $this->assertEquals('Second', $this->fixture->Evaluate($request));
    $this->assertEquals('42', $request->data['id']);

    $request = new http\Request('user/add');
    $this->assertEquals('Third', $this->fixture->Evaluate($request));
  }

  public function testExtractDouble()
  {
    $map = array(
      'action/unused' => 'Invalid',
      'document/{action}/{key}' => 'DocumentController'
    );
    $this->fixture->set_map($map);

    $request = new http\Request('document/edit/42');
    $this->assertEquals('DocumentController', $this->fixture->Evaluate($request));
    $this->assertEquals('edit', $request->data['action']);
    $this->assertEquals('42', $request->data['key']);
  }

  public function testExactMatch()
  {
    $map = array(
      'action/one' => 'First',
      'action/two//' => 'Second',
      'action/two/alpha' => 'Third'
    );
    $this->fixture->set_map($map);

    $request = new http\Request('action/one');
    $this->assertEquals('First', $this->fixture->Evaluate($request));

    $request = new http\Request('action/two');
    $this->assertEquals('Second', $this->fixture->Evaluate($request));

    $request = new http\Request('action/two/alpha');
    $this->assertEquals('Third', $this->fixture->Evaluate($request));
  }

  public function testRegEx()
  {
    $map = array(
      'user/test' => 'First',
      '/user\/([a-z]+)(\/([0-9]+))?/' => 'Second',
      'user/TEST' => 'Third'
    );
    $this->fixture->set_map($map);

    $request = new http\Request('user/test');
    $this->assertEquals('First', $this->fixture->Evaluate($request));

    $request = new http\Request('user/add');
    $this->assertEquals('Second', $this->fixture->Evaluate($request));
    $this->assertEquals('add', $request->data['url_pattern'][1]);
    $this->assertTrue(!isset($request->data['url_pattern'][2]));

    $request = new http\Request('user/view/14');
    $this->assertEquals('Second', $this->fixture->Evaluate($request));
    $this->assertEquals('view', $request->data['url_pattern'][1]);
    $this->assertEquals('14', $request->data['url_pattern'][3]);

    $request = new http\Request('user/TEST');
    $this->assertEquals('Third', $this->fixture->Evaluate($request));
  }

  public function testLookupActionClass()
  {
    $test_class = '\hoplite\test\TestAction';
    $this->assertEquals($test_class, $this->fixture->LookupAction($test_class));

    $test_class = 'TestAction';
    $this->assertEquals($test_class, $this->fixture->LookupAction($test_class));
  }

  public function testLookupActionFile()
  {
    $this->assertEquals('TestAction', $this->fixture->LookupAction('actions/test_action'));
    $this->assertEquals('TestAction', $this->fixture->LookupAction('actions/test_action.php'));
  }
}
