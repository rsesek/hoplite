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

require_once HOPLITE_ROOT . '/base/strict_object.php';

/*!
  A Request represents a HTTP request and holds the data and contexte associated
  with it.
*/
class Request extends \hoplite\base\StrictObject
{
  /*! @var string The request method (upper case). */
  public $http_method = NULL;

  /*! @var string The URL, relataive to the RootController. */
  public $url = '';

  /*! @var array HTTP request data. */
  public $data = array();

  /*!
    Constructor. Takes an optional URL.
  */
  public function __construct($url = '')
  {
    $this->url = $url;
  }
}
