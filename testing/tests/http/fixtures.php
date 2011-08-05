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

namespace hoplite\test;
use \hoplite\http as http;

class TestRestAction extends \hoplite\http\RestAction
{
  public $did_get = FALSE;
  public $did_post = FALSE;
  public $did_delete = FALSE;
  public $did_put = FALSE;

  public function DoGet(http\Request $request, http\Response $response)
  {
    parent::DoGet($request, $response);
    $this->did_get = TRUE;
  }
  public function DoPost(http\Request $request, http\Response $response)
  {
    parent::DoPost($request, $response);
    $this->did_post = TRUE;
  }
  public function DoDelete(http\Request $request, http\Response $response)
  {
    parent::DoDelete($request, $response);
    $this->did_delete = TRUE;
  }
  public function DoPut(http\Request $request, http\Response $response)
  {
    parent::DoPut($request, $response);
    $this->did_put = TRUE;
  }
}
