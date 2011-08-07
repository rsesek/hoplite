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

require_once HOPLITE_ROOT . '/base/functions.php';

/*!
  A UrlMap will translate a raw HTTP request into a http\Request object. It does
  so by matching the incoming URL to a pattern. For information on the format of
  patterns @see ::set_map().
*/
class UrlMap
{
  /*! @var RootController */
  private $controller;

  /*! @var array The map of URLs to actions. */
  private $map = array();

  /*! @var Closure(string) Loads the file for the appropriate action class name. */
  private $file_loader = NULL;

  /*!
    Constructs the object with a reference to the RootController.
    @param RootController
  */
  public function __construct(RootController $controller)
  {
    $this->controller = $controller;
  }

  /*! Accessor for the RootController */
  public function controller() { return $this->controller; }

  /*! Gets the URL map */
  public function map() { return $this->map; }
  /*! @brief Sets the URL map.
    The URL map is an associative array of URL prefix patterns to Actions or
    file paths containing actions.

    The keys can be either a URL prefix or a regular expression.

    For URL prefixes, the pattern is matched relative to the root of the entry-
    point as specified by the RootController. Patterns should not begin with a
    slash. Path fragment parameter extraction can be performed as well. For
    example, the pattern 'user/view/{id}' will match a URL like
      http://example.com/webapp/user/view/42
    And the http\Request's data will have a member called 'id' with value 42.

    Typically prefix matching will be done for the above patterns. This may be
    unwanted if a more general path needs to execute before a more descendent
    one. Using strict matching, invoked by ending a pattern with two slashes,
    pattern matching becomes exact in that all path components must match.

    Regular expression matching is not limited to prefix patterns and can match
    any part of the URL (though prefix matching can be enforced using the
    the standard regex '^' character). Regex patterns must begin and end with
    '/' characters. During evaluation, if any pattern groups are matched, the
    resulting matches will be placed in Request data via the 'url_pattern' key.
    The following will match the same URL as above:
      '/^user\/view\/([0-9]+)/'

    Values can be a class name or a relative path to a file that contains an
    Action class by the same name, or just the name of an Action class. The
    conventions for each are governed by ::LookupAction().

    @see ::Evaluate()
    @see ::LookupAction()
  */
  public function set_map(array $map) { $this->map = $map; }

  /*! @brief The file loading helper function.
    This function is called in ::LookupAction() and will load the necessary file
    for this class so that it can be instantiated. Should return the class name
    to instantiate. This can either be the first argument passed to it
    unaltered, or modified for example with a namespace.
    Closure(string $class_name, string $map_value) -> string $new_class_name
  */
  public function set_file_loader(\Closure $fn) { $this->file_loader = $fn; }

  /*! @brief Evalutes the URL map and finds a match.
    This will take the incoming URL from the request and will match it against
    the patterns in the internal map.

    Matching occurs in a linear scan of the URL map, so precedence can be
    enforced by ordering rules appropriately. Regular expressions are matched
    and URL parameter extraction occurs each time a different rule is evaluated.

    This may mutate the request with extracted data if a match is made and
    returned.
    @see ::set_map() for more information.

    If a match is made, this will return the corresponding value for the matched
    key in the map. To get an Action object from this, use ::LookupAction().

    @return string|NULL A matched value in the ::map() or NULL if no match.
  */
  public function Evaluate(Request $request)
  {
    $fragments = explode('/', $request->url);
    $path_length = strlen($request->url);

    foreach ($this->map as $rule => $action) {
      // First check if this is the empty rule.
      if (empty($rule)) {
        if (empty($request->url))
          return $action;
        else
          continue;
      }

      // Check if this is a regular expression rule and match it.
      if ($rule[0] == '/' && substr($rule, -1) == '/') {
        $matches = array();
        if (preg_match($rule, $request->url, $matches)) {
          // This pattern matched, so fill out the request and return.
          $request->data['url_pattern'] = $matches;
          return $action;
        }
      }
      // Otherwise, this is just a normal string match.
      else {
        // Patterns that end with two slashes are exact.
        $is_strict = substr($rule, -2) == '//';
        if ($is_strict)
          $rule = substr($rule, 0, -2);

        // Set up some variables for the loop.
        $is_match = TRUE;
        $rule_fragments = explode('/', $rule);
        $count_rule_fragments = count($rule_fragments);
        $count_fragments = count($fragments);
        $extractions = array();

        // If this is a strict matcher, then do a quick test based on fragments.
        if ($is_strict && $count_rule_fragments != $count_fragments)
          continue;

        // Loop over the pieces of the rule, matching the fragments to that of
        // the request.
        foreach ($rule_fragments as $i => $rule_frag) {
          // Don't iterate past the length of the request. Prefix matching means
          // that this can still be a match.
          if ($i >= $count_fragments)
            break;

          // If this fragment is a key to be extracted, do so into a temporary
          // array.
          if (strlen($rule_frag) && $rule_frag[0] == '{' && substr($rule_frag, -1) == '}') {
            $key = substr($rule_frag, 1, -1);
            $extractions[$key] = $fragments[$i];
          }
          // Otherwise, the path components mutch match.
          else if ($rule_frag != $fragments[$i]) {
            $is_match = FALSE;
            break;
          }
        }

        // If no match was made, try the next rule.
        if (!$is_match)
          continue;

        // A match was made, so merge the path components that were extracted by
        // key and return the match.
        $request->data = array_merge($extractions, $request->data);
        return $action;
      }
    }
  }

  /*! @brief Takes a value from the map and returns an Action object.
    The values in the map are either an Action class name or a relative path to
    a file containing an Action class.

    Mapping to a class requires that the value start with an upper-case letter.

    Paths start with a lower-case letter. The last path component will be
    transformed into a class name via ::_ClassNameFromFileName(). Note that if
    the file extension is not included  in the path, .php will be automatically
    appended.

    @return Action|NULL The loaded action, or NULL on error.
  */
  public function LookupAction($map_value)
  {
    // If the first character is uppercase or a namespaced class, simply return
    // the value.
    $first_char = $map_value[0];
    if ($first_char == '\\' || ctype_upper($first_char)) {
      $class = $map_value;
    } else {
      // Otherwise this is a path. Check if an extension is present, and if not,
      // add one.
      $pathinfo = pathinfo($map_value);
      if (!isset($pathinfo['extension'])) {
        $map_value .= '.php';
        $pathinfo['extension'] = 'php';
      }

      $class = $this->_ClassNameFromFileName($pathinfo);
    }

    if ($this->file_loader) {
      $loader = $this->file_loader;
      $class = $loader($class, $map_value);
    }

    return new $class($this->controller());
  }

  /*!
    Takes a file name and returns the name of an Action class. This uses an
    under_score to CamelCase transformation with an 'Action' suffix:
      lost_password -> LostPasswordAction

    This can be overridden to provide a custom transformation.

    @param array Result of a pathinfo() call

    @return string Action class name.
  */
  protected function _ClassNameFromFileName($pathinfo)
  {
    $filename = $pathinfo['filename'];
    return \hoplite\base\UnderscoreToCamelCase($filename);
  }
}
