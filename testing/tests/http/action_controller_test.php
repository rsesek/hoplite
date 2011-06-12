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

require_once HOPLITE_ROOT . '/http/action_controller.php';

class TestActionController extends http\ActionController
{
  public $did_action = FALSE;

  public function ActionSomething(http\Request $request, http\Response $response)
  {
    $this->did_action = TRUE;
  }
}

class ActionControllerTest extends \PHPUnit_Framework_TestCase
{
  public function setUp()
  {
    $globals = array();
    $this->fixture = new TestActionController(new http\RootController($globals));
    $this->request = new http\Request();
    $this->response = new http\Response();
  }

  public function testDispatch()
  {
    $this->request->data['action'] = 'something';
    $this->fixture->Invoke($this->request, $this->response);
    $this->assertTrue($this->fixture->did_action);
  }

  public function testFailedDispatch()
  {
    $globals = array();
    $mock = $this->getMock('hoplite\http\RootController', array(), array($globals));
    $this->fixture = new TestActionController($mock);

    $mock->expects($this->once())
         ->method('Stop');

    $this->request->data['action'] = 'nothing';
    $this->fixture->Invoke($this->request, $this->response);
    $this->assertFalse($this->fixture->did_action);
  }
}
