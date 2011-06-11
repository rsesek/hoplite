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

require_once HOPLITE_ROOT . '/http/action.php';
require_once HOPLITE_ROOT . '/http/response_code.php';

/*!
  An ActionController is an Action that operates like a typical MVC Controller.
  It will look at the Request's data for a key named 'action', which should
  correspond to a method named 'Action$action'.
*/
class ActionController extends Action
{
  /*!
    Forwards the request/response pair to an internal method based on a key in
    the Request object.
  */
  public function Invoke(Request $request, Response $response)
  {
    $method = $this->_GetActionMethod($request);
    if (!method_exists($this, $method)) {
      $response->response_code = ResponseCode::NOT_FOUND;
      $this->controller()->Stop();
      return;
    }

    $this->$method($request, $response);
  }

  /*!
    Returns the method name to invoke based on the Request.
    @return string|NULL
  */
  protected function _GetActionMethod(Request $request)
  {
    if (!isset($request->data['action']))
      return NULL;
    return 'Action' . $request->data['action'];
  }
}
