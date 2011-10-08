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

require_once HOPLITE_ROOT . '/base/weak_interface.php';
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

  /*! @var WeakInterface<RootControllerDelegate> */
  private $delegate = NULL;

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
    $this->delegate = new \hoplite\base\WeakInterface('hoplite\http\RootControllerDelegate');
  }

  /*! Accessors */
  public function request() { return $this->request; }
  public function response() { return $this->response; }

  /*! Sets the UrlMap. */
  public function set_url_map(UrlMap $url_map) { $this->url_map = $url_map; }

  /*! Sest the Output Filter. */
  public function set_output_filter(OutputFilter $output_filter)
  {
    $this->output_filter = $output_filter;
  }

  /*! Sets the delegate. */
  public function set_delegate($delegate)
  {
    $this->delegate->Bind($delegate);
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
    $data = $this->request->data['_SERVER'];
    if (isset($data['PATH_INFO']))
      $url = $data['PATH_INFO'];
    else
      $url = '/';
    if ($url[0] == '/')
      $url = substr($url, 1);

    // Set the final pieces of the request.
    $this->request->url = $url;
    $this->request->http_method = $this->request->data['_SERVER']['REQUEST_METHOD'];

    // Extract any PUT data as POST params.
    if ($this->request->http_method == 'PUT')
      parse_str(file_get_contents('php://input'), $this->request->data['_POST']);

    // Register self as the active instance.
    $GLOBALS[__CLASS__] = $this;

    $this->delegate->OnInitialRequest($this->request, $this->response);

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
    $this->delegate->WillStop($this->request, $this->response);
    $this->output_filter->FilterOutput($this->request, $this->response);
    $this->_Exit();
  }

  /*!
    Sets the response code and stops the controller. Returns void.
  */
  public function StopWithCode($code)
  {
    $this->response->response_code = $code;
    $this->Stop();
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
    $this->delegate->WillRouteRequest($request, $this->response);

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
    $this->delegate->WillInvokeAction($action, $this->request, $this->response);

    $action->FilterRequest($this->request, $this->response);
    $action->Invoke($this->request, $this->response);
    $action->FilterResponse($this->request, $this->response);

    $this->delegate->DidInvokeAction($action, $this->request, $this->response);
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

  /*!
    Given a relative path, return an absolute path from the root controller.
    @param string The relative path for which a URL will be created
    @param bool Include the HTTP scheme and host to create an RFC URL if true;
                if false an absolute path will be returned.
  */
  public function MakeURL($new_path, $url = FALSE)
  {
    // Detect the common paths between the REQUEST_URI and the PATH_INFO. That
    // common piece will be the path to the root controller.
    $request_uri = $this->request()->data['_SERVER']['REQUEST_URI'];
    $path_info = $this->request()->data['_SERVER']['PATH_INFO'];
    if ($path_info === NULL)
      $common_uri = substr($request_uri, 0, -1);
    else
      $common_uri = strstr($request_uri, $path_info, TRUE);

    // If just constructing an absolute path, return that now.
    if (!$url)
      return $common_uri . $new_path;

    // Otherwise, build the host part.
    $url = 'http';
    if (isset($this->request()->data['_SERVER']['HTTPS']) &&
        $this->request()->data['_SERVER']['HTTPS'] == 'on') {
      $url .= 's';
    }
    $url .= '://' . $this->request()->data['_SERVER']['HTTP_HOST'];

    $port = $this->request()->data['_SERVER']['SERVER_PORT'];
    if ($port != 80 && $port != 443)
      $url .= ':' . $port;

    $url .= $common_uri;
    return $url . $new_path;
  }
}

/*!
  Delegate for the root controller. The controller uses WeakInterface to call
  these methods, so they're all optional.
*/
interface RootControllerDelegate
{
  public function OnInitialRequest(Request $request, Response $response);

  public function WillRouteRequest(Request $request, Response $response);

  public function WillInvokeAction(Action $action, Request $request, Response $response);

  public function DidInvokeAction(Action $action, Request $request, Response $response);

  public function WillStop(Request $request, Response $response);
}
