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

require_once HOPLITE_ROOT . '/http/response_code.php';

/*!
  The OutputFilter is executed after all Actions have been processed. The
  primary function is to generate the actual HTTP response body from the model
  data contained within the http\Response. Depending on how the Request was
  sent, this
*/
class OutputFilter
{
  /*! @var RootController */
  private $controller;

  /*! @const The key in Response#context that indicates which type of output to
             produce, regardless of the request type.
  */
  const RESPONSE_TYPE = 'response_type';

  /*!
    Constructor that takes a reference to the RootController.
  */
  public function __construct(RootController $controller)
  {
    $this->controller = $controller;
  }

  /*! Accessor for the RootController. */
  public function controller() { return $this->controller; }

  /*! @brief Main entry point for output filtering
    This is called from the RootController to begin processing the output and
    generating the response.
  */
  public function FilterOutput(Request $request, Response $response)
  {
    // If there was an error during the processing of an action, allow hooking
    // custom logic.
    if ($response->response_code != ResponseCode::OK)
      if (!$this->_ContinueHandlingResponseForCode($request, $response))
        return;

    // If there's already raw data for the body, just output that.
    if ($response->body)
      return;

    // Otherwise, construct the body based on how the Request was received and
    // any other information in the response.
    $this->_CreateBodyForResponse($request, $response);

    // Now just output the response.
    header("Status: {$response->response_code}", true, $response->response_code);
    foreach ($response->headers as $header => $value)
      header("$header: $value");
    print $response->body;
  }

  /*!
    If the request did not generate an 200 response code, the filter gives the
    client an opportunity to override the normal output control flow and perform
    some other task. If you want the control flow to continue executing as
    normal, return TRUE; otherwise, return FALSE to exit from ::FilterOutput().
    @return boolean
  */
  protected function _ContinueHandlingResponseForCode(Request $request,
                                                      Response $response)
  {
    return TRUE;
  }

  /*!
    Fills out the Response#data field. This could be an evaluated HTML template,
    a JSON payload, XML, or any other type of response for the client.
  */
  protected function _CreateBodyForResponse(Request $request,
                                            Response $response)
  {
    $type = NULL;

    // See if the HTTP request contains the desired output format.
    if (isset($request->data['format'])) {
      if ($request->data['format'] == 'xml')
        $type = 'xml';
      else if ($request->data['format'] == 'json')
        $type = 'json';
    }

    // If the request didn't specify a type, try and figure it out using
    // heuristics.

    // If this was from an XHR, assume JSON.
    if (!$type && isset($request->data['_SERVER']['X_REQUESTED_WITH']))
      $type = 'json';

    // Check if an Action specified an overriding response type.
    if (isset($response->context[self::RESPONSE_TYPE]))
      $type = $response->context[self::RESPONSE_TYPE];

    // If no type has been determined, just assume HTML.
    if (!$type)
      $type = 'html';

    if ($type == 'json') {
      $response->headers['Content-Type'] = 'application/json';
      $response->body = json_encode($response->data);
    } else if ($type == 'xml') {
      $response->headers['Content-Type'] = 'application/xml';
      $response->body = $this->_EncodeXML($response->data);
    } else if ($type == 'html') {
      $response->headers['Content-Type'] = 'text/html';
    }
  }

  /*!
    Creates an XML tree from an array. Equivalent to json_encode.
  */
  protected function _EncodeXML($data)
  {
    $response = new \SimpleXMLElement('<response/>');

    $writer = function($elm, $parent) use (&$writer) {
      foreach ($elm as $key => $value) {
        if (is_scalar($value)) {
          $parent->AddChild($key, $value);
        } else {
          $new_parent = $parent->AddChild($key);
          $writer($value, $new_parent);
        }
      }
    };

    $writer($data, $response);
    return $response->AsXML();
  }
}
