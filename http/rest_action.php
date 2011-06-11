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

/*!
  This abstract class is the base for handling all requests and filling out
  response objects.
*/
class RestAction extends Action
{
  /*!
    Performs the action and fills out the response's data model.
  */
  public function Invoke(Request $request, Response $response)
  {
    $valid_methods = array('get', 'post', 'delete', 'put');
    $method = strtolower($request->http_method);
    if (!in_array($method, $valid_methods)) {
      $response->http_code = 405 /* METHOD_NOT_ALLOWED */;
      $this->controller()->Stop();
      return;
    }

    $invoke = 'Do' . ucwords($method);
    $this->$invoke();
  }

  /*! Methods for each of the different HTTP methods. */
  protected function _DoGet(Request $request, Response $response) {}
  protected function _DoPost(Request $request, Response $response) {}
  protected function _DoDelete(Request $request, Response $response) {}
  protected function _DoPut(Request $request, Response $response) {}
}
