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
  An enumeration of all the HTTP status codes as constants. This is the complete
  list of codes. Not all will be usable by an application.
  @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
*/
class ResponseCode
{
  const HTTP_CONTINUE                    = 100;  // CONTINUE is a keyword.
  const SWITCHING_PROTOCOLS              = 101;
  const OK                               = 200;
  const CREATED                          = 201;
  const ACCEPTED                         = 202;
  const NO_CONTENT                       = 204;
  const RESET_CONTENT                    = 205;
  const PARTIAL_CONTENT                  = 206;
  const MULTIPLE_CHOICES                 = 300;
  const MOVED_PERMANENTLY                = 301;
  const FOUND                            = 302;
  const SEE_OTHER                        = 303;
  const NOT_MODIFIED                     = 304;
  const USE_PROXY                        = 305;
  const TEMPORARY_REDIRECT               = 307;
  const BAD_REQUEST                      = 400;
  const UNAUTHORIZED                     = 401;
  const PAYMENT_REQUIRED                 = 402;
  const FORBIDDEN                        = 403;
  const NOT_FOUND                        = 404;
  const METHOD_NOT_ALLOWED               = 405;
  const NOT_ACCEPTABLE                   = 406;
  const PROXY_AUTHENTICATION_REQUIRED    = 407;
  const REQUEST_TIMEOUT                  = 408;
  const CONFLICT                         = 409;
  const GONE                             = 410;
  const LENGTH_REQUIRED                  = 411;
  const PRECONDITION_FAILED              = 412;
  const REQUEST_ENTITY_TOO_LARGE         = 413;
  const UNSUPPORTED_MEDIA_TYPE           = 415;
  const REQUESTED_RANGE_NOT_SATISFIABLE  = 416;
  const EXPECTATION_FAILED               = 417;
  const INTERNAL_SERVER_ERROR            = 500;
  const NOT_IMPLEMENTED                  = 501;
  const BAD_GATEWAY                      = 502;
  const SERVICE_UNAVAILABLE              = 503;
  const GATEWAY_TIMEOUT                  = 504;
  const HTTP_VERSION_NOT_SUPPORTED       = 505;
}
