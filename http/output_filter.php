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

require_once HOPLITE_ROOT . '/base/weak_interface.php';
require_once HOPLITE_ROOT . '/http/response_code.php';
require_once HOPLITE_ROOT . '/views/template_loader.php';

/*!
  The OutputFilter is executed after all Actions have been processed. The
  primary function is to generate the actual HTTP response body from the model
  data contained within the http\Response. Depending on how the Request was
  sent, this class will encode the output properly and perform any necessary
  processing (e.g. templates).
*/
class OutputFilter
{
  /*! @var RootController */
  private $controller;

  /*! @var WeakInterface<OutputFilterDelegate> */
  private $delegate;

  /*! @const The key in Response#context that indicates which type of output to
             produce, regardless of the request type.
  */
  const RESPONSE_TYPE = 'response_type';

  /*! @const The key in a Response#context that is set by the ::FilterOutput. It
             is derived from {@see RESPONSE_TYPE} and controls the actual output
             type.
  */
  const OUTPUT_FILTER_TYPE = '_output_filter_type';

  /*! @const A key in Response#context to render a template with the
             Response#data when creating a HTML body.
  */
  const RENDER_TEMPLATE = 'template';

  /*!
    Constructor that takes a reference to the RootController.
  */
  public function __construct(RootController $controller)
  {
    $this->controller = $controller;
    $this->delegate = new \hoplite\base\WeakInterface('hoplite\http\OutputFilterDelegate');
  }

  /*! Accessor for the RootController. */
  public function controller() { return $this->controller; }

  /*! Accessors for the delegate. */
  public function set_delegate($delegate)
  {
    $this->delegate->Bind($delegate);
  }
  public function delegate() { return $this->delegate->Get(); }

  /*! @brief Main entry point for output filtering
    This is called from the RootController to begin processing the output and
    generating the response.
  */
  public function FilterOutput(Request $request, Response $response)
  {
    $response->context[self::OUTPUT_FILTER_TYPE] = $this->_GetResponseType($request, $response);

    // If there was an error during the processing of an action, allow hooking
    // custom logic.
    if ($response->response_code != ResponseCode::OK)
      if ($this->delegate->OverrideOutputFiltering($request, $response))
        return;

    // If there's already raw data for the body, just output that. Otherwise,
    // construct the body based on how the Request was received and any other
    //  information in the response.
    if (!$response->body)
      $this->_CreateBodyForResponse($request, $response);

    // Now just output the response.
    header("Status: {$response->response_code}", true, $response->response_code);
    foreach ($response->headers as $header => $value)
      header("$header: $value");
    print $response->body;
  }

  /*!
    Fills out the Response#data field. This could be an evaluated HTML template,
    a JSON payload, XML, or any other type of response for the client.
  */
  private function _CreateBodyForResponse(Request $request,
                                          Response $response)
  {
    $type = $response->context[self::OUTPUT_FILTER_TYPE];
    if ($type == 'json') {
      $response->headers['Content-Type'] = 'application/json';
      $response->body = json_encode($response->data, JSON_NUMERIC_CHECK);
    } else if ($type == 'xml') {
      $response->headers['Content-Type'] = 'application/xml';
      $response->body = $this->_EncodeXML($response->data);
    } else if ($type == 'html') {
      $response->headers['Content-Type'] = 'text/html';
      if (isset($response->context[self::RENDER_TEMPLATE])) {
        $template = \hoplite\views\TemplateLoader::Fetch($response->context[self::RENDER_TEMPLATE]);
        $response->body = $template->Render($response->data);
      }
    }
  }

  /*!
    Determines based on the Request what format the response should be in.
  */
  private function _GetResponseType(Request $request, Response $response)
  {
    // Check if an Action specified an overriding response type.
    if (isset($response->context[self::RESPONSE_TYPE]))
      return $response->context[self::RESPONSE_TYPE];

    // See if the HTTP request contains the desired output format.
    if (isset($request->data['format'])) {
      if ($request->data['format'] == 'xml')
        return 'xml';
      else if ($request->data['format'] == 'json')
        return 'json';
    }

    // If the request didn't specify a type, try and figure it out using
    // heuristics.

    // If this was from an XHR, assume JSON.
    if (isset($request->data['_SERVER']['HTTP_X_REQUESTED_WITH']))
      return 'json';

    // If no type has been determined, just assume HTML.
    return 'html';
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

/*!
  Delegate interface for the OutputFilter. Called via a WeakInterface, and all
  methods are optional.
*/
interface OutputFilterDelegate
{
  /*!
    The delegate can abort output filtering of the request and execute custom
    logic by returning TRUE from this function.

    If the request did not generate an 200 response code, the filter gives the
    client an opportunity to override the normal output control flow and perform
    some other task. If you want the control flow to continue executing as
    normal, return FALSE; otherwise, return TRUE to exit from ::FilterOutput().

    @return bool TRUE if the OutputFilter should stop processing the response.
                 FALSE for default output filtering behavior.
  */
  public function OverrideOutputFiltering(Request $request, Response $response);
}
