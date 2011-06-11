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

require_once HOPLITE_ROOT . 'base/strict_object.php';
require_once HOPLITE_ROOT . 'http/response_code.php';

/*!
  A Response holds data processed by Action objects. When the RootController is
  Run(), a Response object is created. This response is used for any subsequent
  chained Actions. After processing, the OutputFilter will take the data and
  formulate the actual HTTP response body.
*/
class Response extends \hoplite\base\StrictObject
{
  /*! @var integer The HTTP response code to return. */
  public $response_code = ResponseCode::OK;

  /*! @var array A map of headers to values to be sent with the response. */
  public $headers = array();

  /*! @var string Raw HTTP response body. */
  public $body = '';

  /*! @var array Context data that is not sent to the output filter but is used
                 to store application-specific information between Actions.
  */
  public $context = array();

  /*! @var array Model data. */
  public $data = array();
}
