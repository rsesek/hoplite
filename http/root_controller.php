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

namespace hoplite\http;

require_once HOPLITE_ROOT . '/http/request.php';
require_once HOPLITE_ROOT . '/http/response.php';
require_once HOPLITE_ROOT . '/http/response_code.php';

/*!
  The RootController is meant to be invoked from the index.php of the
  application.
*/
class RootController
{
  /*! @var Request */
  private $request = NULL;

  /*! @var Response */
  private $response = NULL;

  /*! @var UrlMap */
  private $url_map = NULL;

  /*! @var OutputFilter */
  private $output_filter = NULL;

  /*!
    Creates the controller with the request context information, typicallhy
    from the global scope ($GLOBALS), but can be injected for testing.
    @param UrlMap The routing map
    @param OutputFilter The object responsible for decorating output.
    @param array& PHP globals array
  */
  public function __construct(array $globals)
  {
    $this->response = new Response();
    $this->request = new Request();
    $this->request->data = array(
      '_GET'    => &$globals['_GET'],
      '_POST'   => &$globals['_POST'],
      '_COOKIE' => &$globals['_COOKIE'],
      '_SERVER' => &$globals['_SERVER']
    );
  }

  /*! Accessors */
  public function request() { return $this->request; }
  public function response() { return $this->response; }

  /*! Sets the UrlMap. */
  public function set_urL_map(UrlMap $url_map) { $this->url_map = $url_map; }

  /*! Sest the Output Filter. */
  public function set_output_filter(OutputFilter $output_filter)
  {
    $this->output_filter = $output_filter;
  }

  /*!
    Createst the Request and Response that are used throughout the duration of
    the execution.
  */
  public function Run()
  {
    // The query rewriter module of the webserver rewrites a request from:
    //    http://example.com/webapp/user/view/42
    // to:
    //    http://example.com/webapp/index.php/user/view/42
    // ... which then becomes accessible from PATH_INFO.
    $url = $this->request->data['_SERVER']['PATH_INFO'];
    if ($url[0] == '/')
      $url = substr($url, 1);

    // Set the final pieces of the request.
    $this->request->url = $url;
    $this->request->http_method = $this->request->data['_SERVER']['REQUEST_METHOD'];

    // Dispatch the request to an Action.
    $this->RouteRequest($this->request);

    // When control returns here, all actions have been invoked and it's time
    // to start the output filter and exit.
    $this->Stop();
  }

  /*!
    Prevents any other Actions from executing. This starts the OutputFilter and
    then exits.
  */
  public function Stop()
  {
    $this->output_filter->FilterOutput($this->request, $this->response);
    $this->_Exit();
  }

  /*!
    Wrapper around PHP exit().
  */
  protected function _Exit()
  {
    exit;
  }

  /*!
    Invoked by Run() and can be invoked by others to evaluate and perform the
    lookup in the UrlMap. This then calls InvokeAction().
    @param Request The Request whose URL will be routed
  */
  public function RouteRequest(Request $request)
  {
    $url_map_value = $this->url_map->Evaluate($request);

    $action = NULL;
    if ($url_map_value)
      $action = $this->url_map->LookupAction($url_map_value);

    if (!$action) {
      $this->response->response_code = ResponseCode::NOT_FOUND;
      $this->Stop();
      return;
    }

    $this->InvokeAction($action);
  }

  /*!
    Used to run an Action and drive it through its states.
    @param Action
  */
  public function InvokeAction(Action $action)
  {
    $action->FilterRequest($this->request, $this->response);
    $action->Invoke($this->request, $this->response);
    $action->FilterResponse($this->request, $this->response);
  }

  /*!
    Performs a reverse-lookup in the UrlMap for the pattern/fragment for the
    name of a given Action class.
    @param string Class name.
  */
  public function LookupAction($class)
  {
    $map = $this->url_map->map();
    foreach ($map as $pattern => $action) {
      if ($action == $class)
        return $pattern;
    }
  }
}
