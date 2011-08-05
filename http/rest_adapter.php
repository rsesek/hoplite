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

require_once HOPLITE_ROOT . '/http/action_controller.php';
require_once HOPLITE_ROOT . '/http/response_code.php';
require_once HOPLITE_ROOT . '/http/rest_action.php';

/*!
  This abstract class adapts a RESTful interface into a web frontend that only
  supports GET and POST. The |action| parameter will control what is performed.
*/
abstract class RestAdapter extends ActionController
{
  /*! @var RestAction The RESTful interface which will be adapted. */
  protected $action = NULL;

  public function FilterRequest(Request $request, Response $response)
  {
    $this->action = $this->_GetRestAction();
  }

  /*! Gets the RestAction that will be adapted. */
  protected abstract function _GetRestAction();

  public function ActionFetch(Request $request, Response $response)
  {
    if ($request->http_method != 'GET' && $request->http_method != 'POST') {
      $response->response_code = ResponseCode::METHOD_NOT_ALLOWED;
      return;
    }
    $this->action->DoGet($request, $response);
  }

  public function ActionInsert(Request $request, Response $response)
  {
    if ($request->http_method != 'POST') {
      $response->response_code = ResponseCode::METHOD_NOT_ALLOWED;
      return;
    }
    $this->action->DoPut($request, $response);
  }

  public function ActionUpdate(Request $request, Response $response)
  {
    if ($request->http_method != 'POST') {
      $response->response_code = ResponseCode::METHOD_NOT_ALLOWED;
      return;
    }
    $this->action->DoPost($request, $response);
  }

  public function ActionDelete(Request $request, Response $response)
  {
    if ($request->http_method != 'POST') {
      $response->response_code = ResponseCode::METHOD_NOT_ALLOWED;
      return;
    }
    $this->action->DoDelete($request, $response);
  }
}
