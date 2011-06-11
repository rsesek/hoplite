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
abstract class Action
{
  /*! \var RootController */
  private $controller;

  /*!
    Creates a new action with a reference to the RootController.
    @param RootController
  */
  public function __construct($controller)
  {
    $this->controller = $controller;
  }

  /*! Accesses the RootController */
  public function controller() { return $this->controller; }

  /*!
    Called before the Action is Invoked().
  */
  public function FilterRequest(Request $request, Response $response) {}

  /*!
    Performs the action and fills out the response's data model.
  */
  public function Invoke(Request $request, Response $response);

  /*!
    Called after this has been Invoked().
  */
  public function FilterResponse(Request $request, Response $response) {}
}
