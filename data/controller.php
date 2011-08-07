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

namespace hoplite\data;
use \hoplite\http as http;

require_once HOPLITE_ROOT . '/http/response_code.php';
require_once HOPLITE_ROOT . '/http/rest_action.php';

/*!
  A Controller is a RESTful http\Action that is used to bind a data\Model to a
  web interface. Subclass this in order to perform business logic such as
  validation and authentication.

  This class is semi-abstract in that it cannot be used directly. At minimum,
  FilterRequest() needs to be overridden to select the Model to use.
*/
class Controller extends http\RestAction
{
  /*! @var hoplite\data\Model The object that will be operated on. */
  protected $model = NULL;

  /*! Selects the Model object. */
  public function FilterRequest(http\Request $request, http\Response $response)
  {
    // Example:
    // $this->model = new webapp\models\User();
    throw new ControllerException('Model not selected');
  }

  /*! Gets the data from the model. */
  public function DoGet(http\Request $request, http\Response $response)
  {
    $this->model->SetFrom($request->data);
    try {
      $response->data = $this->model->Fetch();
    } catch (ModelException $e) {
      $response->body = $e->GetMessage();
      $response->response_code = http\ResponseCode::NOT_FOUND;
    } catch (\PDOException $e) {
      $response->body = $e->GetMessage();
      $response->response_code = http\ResponseCode::INTERNAL_SERVER_ERROR;
    }
  }

  /*! Updates an object in the store. */
  public function DoPost(http\Request $request, http\Response $response)
  {
    $this->model->SetFrom($request->data);
    try {
      $this->model->Update();
      $response->data = $this->model->Fetch();
    } catch (ModelException $e) {
      $response->body = $e->GetMessage();
      $response->response_code = http\ResponseCode::NOT_FOUND;
    } catch (\PDOException $e) {
      $response->body = $e->GetMessage();
      $response->response_code = http\ResponseCode::INTERNAL_SERVER_ERROR;
    }
  }

  /*! Deletes the object from the store. */
  public function DoDelete(http\Request $request, http\Response $response)
  {
    $this->model->SetFrom($request->data);
    try {
      $this->model->Delete();
    } catch (ModelException $e) {
      $response->body = $e->GetMessage();
      $response->response_code = http\ResponseCode::BAD_REQUEST;
    } catch (\PDOException $e) {
      $response->body = $e->GetMessage();
      $response->response_code = http\ResponseCode::INTERNAL_SERVER_ERROR;
    }
  }

  /*! Updates an object in the store. */
  public function DoPut(http\Request $request, http\Response $response)
  {
    $this->model->SetFrom($request->data);
    try {
      $this->model->Insert();
      $response->data = $this->model->Fetch();
    } catch (ModelException $e) {
      $response->body = $e->GetMessage();
      $response->response_code = http\ResponseCode::BAD_REQUEST;
    } catch (\PDOException $e) {
      $response->body = $e->GetMessage();
      $response->response_code = http\ResponseCode::INTERNAL_SERVER_ERROR;
    }
  }
}

class ControllerException extends \Exception {}
