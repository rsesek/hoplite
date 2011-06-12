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

require_once HOPLITE_ROOT . '/http/action.php';
require_once HOPLITE_ROOT . '/http/output_filter.php';
require_once HOPLITE_ROOT . '/http/root_controller.php';
require_once HOPLITE_ROOT . '/http/url_map.php';

class ActionReporter extends http\Action
{
  public $did_filter_request = FALSE;
  public $did_invoke = FALSE;
  public $did_filter_response = FALSE;

  public function FilterRequest(http\Request $q, http\Response $s)
  {
    $this->did_filter_request = TRUE;
  }

  public function Invoke(http\Request $q, http\Response $s)
  {
    $this->did_invoke = TRUE;
  }

  public function FilterResponse(http\Request $q, http\Response $s)
  {
    $this->did_filter_response = TRUE;
  }
}

class RootControllerTest extends \PHPUnit_Framework_TestCase
{
  /*!
    Configures a mock RootControler.
    @param array|NULL Array of methods to mock
    @param varargs Constructor parameters.
    @return Mock RootControler
  */
  public function ConfigureMock()
  {
    $args = func_get_args();
    return $this->getMock('hoplite\http\RootController', $args[0], array_slice($args, 1));
  }

  public function testRun()
  {
    $globals = array('_SERVER' => array(
        'REQUEST_METHOD' => 'GET',
        'PATH_INFO' => '/some/action/42'
    ));
    $mock = $this->ConfigureMock(array('RouteRequest', 'Stop'), $globals);

    $mock->expects($this->once())
         ->method('RouteRequest')
         ->with($this->equalTo('some/action/42'));

    $mock->expects($this->once())
         ->method('Stop');

    $mock->Run();
  }

  public function testInvokeAction()
  {
    $globals = array();
    $fixture = new http\RootController($globals);

    $action = new ActionReporter($fixture);

    $this->assertFalse($action->did_filter_request);
    $this->assertFalse($action->did_invoke);
    $this->assertFalse($action->did_filter_response);

    $fixture->InvokeAction($action);

    $this->assertTrue($action->did_filter_request);
    $this->assertTrue($action->did_invoke);
    $this->assertTrue($action->did_filter_response);
  }

  public function testRouteRequest()
  {
    $globals = array();
    $mock = $this->ConfigureMock(array('Stop', 'InvokeAction'), $globals);

    $fragment = 'some/action/42';
    $map_value = 'ActionReporter';
    $action = new ActionReporter($mock);

    $mock->expects($this->once())
         ->method('InvokeAction')
         ->with($this->isInstanceOf('hoplite\test\ActionReporter'));

    $url_map = $this->getMock('hoplite\http\UrlMap', array(), array($mock));
    $url_map->expects($this->once())
            ->method('Evaluate')
            ->with($this->equalTo($fragment))
            ->will($this->returnValue($map_value));
    $url_map->expects($this->once())
            ->method('LookupAction')
            ->with($this->equalTo($map_value))
            ->will($this->returnValue($action));

    $mock->set_url_map($url_map);
    $mock->RouteRequest($fragment);
  }

  public function testRouteRequestInvalid()
  {
    $globals = array();
    $mock = $this->ConfigureMock(array('Stop'), $globals);

    $fragment = 'another/action';
    
    $mock->expects($this->once())
         ->method('Stop');

    $url_map = $this->getMock('hoplite\http\UrlMap', array(), array($mock));
    $url_map->expects($this->once())
            ->method('Evaluate')
            ->with($this->equalTo($fragment));

    $mock->set_url_map($url_map);
    $mock->RouteRequest($fragment);
    $this->assertEquals(http\ResponseCode::NOT_FOUND, $mock->response()->response_code);
  }

  public function testStop()
  {
    $globals = array();
    $mock = $this->ConfigureMock(array('_Exit'), $globals);

    $mock->expects($this->once())
         ->method('_Exit');

    $output_filter = $this->getMock('hoplite\http\OutputFilter', array(), array($mock));
    $output_filter->expects($this->once())
                  ->method('FilterOutput')
                  ->with($this->isInstanceOf('hoplite\http\Request'),
                         $this->isInstanceOf('hoplite\http\Response'));

    $mock->set_output_filter($output_filter);
    $mock->Stop();
  }
}
