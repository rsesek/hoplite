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

/*!
  An enumeration of all the HTTP status codes as constants. This is the complete
  list of codes. Not all will be usable by an application.
  @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
*/
class ResponseCode
{
  const 100 = CONTINUE;
  const 101 = SWITCHING_PROTOCOLS;
  const 200 = OK;
  const 201 = CREATED;
  const 202 = ACCEPTED;
  const 204 = NO_CONTENT;
  const 205 = RESET_CONTENT;
  const 206 = PARTIAL_CONTENT;
  const 300 = MULTIPLE_CHOICES;
  const 301 = MOVED_PERMANENTLY;
  const 302 = FOUND;
  const 303 = SEE_OTHER;
  const 304 = NOT_MODIFIED;
  const 305 = USE_PROXY;
  const 307 = TEMPORARY_REDIRECT;
  const 400 = BAD_REQUEST;
  const 401 = UNAUTHORIZED;
  const 402 = PAYMENT_REQUIRED;
  const 403 = FORBIDDEN;
  const 404 = NOT_FOUND;
  const 405 = METHOD_NOT_ALLOWED;
  const 406 = NOT_ACCEPTABLE;
  const 407 = PROXY_AUTHENTICATION_REQUIRED;
  const 408 = REQUEST_TIMEOUT;
  const 409 = CONFLICT;
  const 410 = GONE;
  const 411 = LENGTH_REQUIRED;
  const 412 = PRECONDITION_FAILED;
  const 413 = REQUEST_ENTITY_TOO_LARGE;
  const 415 = UNSUPPORTED_MEDIA_TYPE;
  const 416 = REQUESTED_RANGE_NOT_SATISFIABLE;
  const 417 = EXPECTATION_FAILED;
  const 500 = INTERNAL_SERVER_ERROR;
  const 501 = NOT_IMPLEMENTED;
  const 502 = BAD_GATEWAY;
  const 503 = SERVICE_UNAVAILABLE;
  const 504 = GATEWAY_TIMEOUT;
  const 505 = HTTP_VERSION_NOT_SUPPORTED;
}
