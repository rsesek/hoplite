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
require_once HOPLITE_ROOT . '/http/root_controller.php';

class TestRestAction extends http\RestAction
{
  public $did_get = FALSE;
  public $did_post = FALSE;
  public $did_delete = FALSE;
  public $did_put = FALSE;

  public function DoGet(http\Request $request, http\Response $response)
  {
    parent::DoGet($request, $response);
    $this->did_get = TRUE;
  }
  public function DoPost(http\Request $request, http\Response $response)
  {
    parent::DoPost($request, $response);
    $this->did_post = TRUE;
  }
  public function DoDelete(http\Request $request, http\Response $response)
  {
    parent::DoDelete($request, $response);
    $this->did_delete = TRUE;
  }
  public function DoPut(http\Request $request, http\Response $response)
  {
    parent::DoPut($request, $response);
    $this->did_put = TRUE;
  }
}

class RestActionTest extends \PHPUnit_Framework_TestCase
{
  public function setUp()
  {
    $globals = array();
    $this->fixture = new TestRestAction(new http\RootController($globals));
    $this->request = new http\Request();
    $this->response = new http\Response();
  }

  public function RestExpectSingleTrue($true_var)
  {
    $vars = array('did_get', 'did_post', 'did_delete', 'did_put');
    foreach ($vars as $var)
      if ($var == $true_var)
        $this->assertTrue($this->fixture->$var);
      else
        $this->assertFalse($this->fixture->$var);
  }

  public function testGet()
  {
    $this->request->http_method = 'GET';
    $this->fixture->Invoke($this->request, $this->response);
    $this->RestExpectSingleTrue('did_get');
  }

  public function testPost()
  {
    $this->request->http_method = 'POST';
    $this->fixture->Invoke($this->request, $this->response);
    $this->RestExpectSingleTrue('did_post');
  }

  public function testDelete()
  {
    $this->request->http_method = 'DELETE';
    $this->fixture->Invoke($this->request, $this->response);
    $this->RestExpectSingleTrue('did_delete');
  }

  public function testPut()
  {
    $this->request->http_method = 'PUT';
    $this->fixture->Invoke($this->request, $this->response);
    $this->RestExpectSingleTrue('did_put');
  }

  public function testInvalid()
  {
    $globals = array();
    $mock = $this->getMock('hoplite\http\RootController', array('Stop'), array($globals));

    $this->fixture = new TestRestAction($mock);

    $mock->expects($this->once())
         ->method('Stop');

    $this->request->http_method = 'HEAD';
    $this->fixture->Invoke($this->request, $this->response);
    $this->RestExpectSingleTrue('___none___');

    $this->assertEquals(http\ResponseCode::METHOD_NOT_ALLOWED, $this->response->response_code);
  }
}
