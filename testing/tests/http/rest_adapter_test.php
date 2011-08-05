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

require_once HOPLITE_ROOT . '/http/rest_action.php';
require_once HOPLITE_ROOT . '/http/rest_adapter.php';
require_once TEST_ROOT . '/tests/http/fixtures.php';

class TestRestAdapter extends http\RestAdapter
{
  protected function _GetRestAction()
  {
    return new TestRestAction($this->controller());
  }

  public function action()
  {
    return $this->action;
  }
}

class RestAdapterTest extends \PHPUnit_Framework_TestCase
{
  public function setUp()
  {
    $globals = array();
    $this->controller = new http\RootController($globals);
    $this->fixture = new TestRestAdapter($this->controller);
    $this->request = $this->controller->request();
    $this->response = $this->controller->response();
  }

  public function RestExpectSingleTrue($true_var)
  {
    $vars = array('did_get', 'did_post', 'did_delete', 'did_put');
    foreach ($vars as $var)
      if ($var == $true_var)
        $this->assertTrue($this->fixture->action()->$var);
      else
        $this->assertFalse($this->fixture->action()->$var);
  }

  public function testFetchGet()
  {
    $this->request->http_method = 'GET';
    $this->request->data['action'] = 'fetch';
    $this->controller->InvokeAction($this->fixture);
    $this->RestExpectSingleTrue('did_get');
  }

  public function testFetchPost()
  {
    $this->request->http_method = 'POST';
    $this->request->data['action'] = 'fetch';
    $this->controller->InvokeAction($this->fixture);
    $this->RestExpectSingleTrue('did_get');
  }

  public function testFetchInvalid()
  {
    $this->request->http_method = 'PUT';
    $this->request->data['action'] = 'fetch';
    $this->controller->InvokeAction($this->fixture);
    $this->RestExpectSingleTrue(NULL);
    $this->assertEquals(http\ResponseCode::METHOD_NOT_ALLOWED, $this->response->response_code);
  }

  public function testUpdate()
  {
    $this->request->http_method = 'POST';
    $this->request->data['action'] = 'update';
    $this->controller->InvokeAction($this->fixture);
    $this->RestExpectSingleTrue('did_post');
  }

  public function testUpdateInvalid()
  {
    $this->request->http_method = 'GET';
    $this->request->data['action'] = 'update';
    $this->controller->InvokeAction($this->fixture);
    $this->RestExpectSingleTrue(NULL);
    $this->assertEquals(http\ResponseCode::METHOD_NOT_ALLOWED, $this->response->response_code);
  }

  public function testDelete()
  {
    $this->request->http_method = 'POST';
    $this->request->data['action'] = 'delete';
    $this->controller->InvokeAction($this->fixture);
    $this->RestExpectSingleTrue('did_delete');
  }

  public function testDeleteInvalid()
  {
    $this->request->http_method = 'GET';
    $this->request->data['action'] = 'delete';
    $this->controller->InvokeAction($this->fixture);
    $this->RestExpectSingleTrue(NULL);
    $this->assertEquals(http\ResponseCode::METHOD_NOT_ALLOWED, $this->response->response_code);
  }

  public function testPut()
  {
    $this->request->http_method = 'POST';
    $this->request->data['action'] = 'insert';
    $this->controller->InvokeAction($this->fixture);
    $this->RestExpectSingleTrue('did_put');
  }

  public function testInsertInvalid()
  {
    $this->request->http_method = 'GET';
    $this->request->data['action'] = 'insert';
    $this->controller->InvokeAction($this->fixture);
    $this->RestExpectSingleTrue(NULL);
    $this->assertEquals(http\ResponseCode::METHOD_NOT_ALLOWED, $this->response->response_code);
  }

  public function testInvalid()
  {
    $globals = array();
    $mock = $this->getMock('hoplite\http\RootController', array('Stop'), array($globals));

    $this->fixture = new TestRestAdapter($mock);

    $mock->expects($this->once())
         ->method('Stop');

    $this->request->http_method = 'HEAD';
    $this->controller->InvokeAction($this->fixture);
    $this->RestExpectSingleTrue('___none___');

    $this->assertEquals(http\ResponseCode::NOT_FOUND, $this->response->response_code);
  }
}
