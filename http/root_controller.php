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
  The RootController is meant to be invoked from the index.php of the
  application.
*/
class RootController
{
  /*!
    Creates the controller with the request context information, typicallhy
    from the global scope ($GLOBALS), but can be injected for testing.
    @param globals
  */
  public function __construct($globals)
  {}

  /*!
    Createst the Request and Response that are used throughout the duration of
    the execution.
  */
  public function Run()
  {}

  /*!
    Prevents any other Actions from executing. This starts the OutputFilter and
    then exits.
  */
  public function Stop()
  {}

  /*!
    Invoked by Run() and can be invoked by others to evaluate and perform the
    lookup in the UrlMap. This then calls InvokeAction().
    @param string The URL fragment to look up in the
  */
  public function RouteRequest($url_fragment)
  {}

  /*!
    Used to run an Action and drive it through its states.
    @param Action
  */
  public function InvokeAction(Action $action)
  {}

  /*!
    Performs a reverse-lookup in the UrlMap for the pattern/fragment for the
    name of a given Action class.
    @param string Class name.
  */
  public function LookupAction($class)
  {}
}
