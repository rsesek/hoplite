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

namespace hoplite\views;

use \hoplite\base\Profiling;

require_once HOPLITE_ROOT . '/base/filter.php';
require_once HOPLITE_ROOT . '/base/profiling.php';

/*!
  Renders a template with additional vars.
  @param string The template name to render
  @param array Variables with which to render.
*/
function Inject($name, $vars = array())
{
  echo TemplateLoader::Fetch($name)->Render($vars);
}

/*! @brief Creates a URL via RootController::MakeURL().
  This requires the root controller be set in the $GLOBALS as
  hoplite\http\RootController.
  @param string Path.
*/
function MakeURL($path)
{
  return $GLOBALS['hoplite\http\RootController']->MakeURL($path, FALSE);
}

/*!
  Template parses a a text file (typically HTML) by expanding a small macro
  language into "compiled" PHP.

  The opening and close tags are user-customizable but the default is {% %}.

  The open and close tags are translated to '<?php' and '?>', respectively.

  Modifiers may be placed after the open and close tags as shorthand for
  further shorthand.

    {% expression %}
        Evaluates a non-printing expression, treating it as pure PHP. This can
        be used to evaluate conditions:
            {% if (!$user->user_id): %}<p>Hello, Guest!</p>{% endif %}

    {%= $value %}
        Prints the value and automatically HTML-escapes it.

    {%= $value | int }
        Prints $value by coerceing it to an integer. Other coercion types
        are str (the default if no pipe symbol, above), int, float, and
        raw (no escaping).
*/
class Template
{
  /*! @var string The macro opening delimiter. */
  static protected $open_tag = '{%';

  /*! @var string The macro closing delimiter. */
  static protected $close_tag = '%}';

  /*! @var string The name of the template. */
  protected $template_name = '';

  /*! @var string The compiled template. */
  protected $data = NULL;

  /*! @var array Variables to provide to the template. */
  protected $vars = array();

  /*!
    Creates a new Template with a given name.
    @param string
  */
  private function __construct($name)
  {
    $this->template_name = $name;
  }

  static public function NewWithData($name, $data)
  {
    $template = new Template($name);
    $template->data = $template->_ProcessTemplate($data);
    return $template;
  }

  static public function NewWithCompiledData($name, $data)
  {
    $template = new Template($name);
    $template->data = $data;
    return $template;
  }

  /*!
    Sets the open and closing delimiters. The caller is responsible for choosing
    values that will not cause the parser to barf.
  */
  static public function set_open_close_tags($open_tag, $close_tag)
  {
    self::$open_tag = $open_tag;
    self::$close_tag = $close_tag;
  }

  /*! Gets the name of the template. */
  public function template_name() { return $this->template_name; }

  /*! Gets the parsed data of the template. */
  public function template() { return $this->data; }

  /*! Overload property accessors to set view variables. */
  public function __get($key)
  {
    return $this->vars[$key];
  }
  public function __set($key, $value)
  {
    $this->vars[$key] = $value;
  }

  /*! This includes the template and renders it out. */
  public function Render($vars = array())
  {
    $_template = $this->data;
    $_vars = array_merge($this->vars, $vars);
    $render = function () use ($_template, $_vars) {
      extract($_vars);
      eval('?>' . $_template . '<' . '?');
    };

    ob_start();

    $error = error_reporting();
    error_reporting($error & ~E_NOTICE);

    $render();

    error_reporting($error);

    if (Profiling::IsProfilingEnabled())
      TemplateLoader::GetInstance()->MarkTemplateRendered($this->name);

    $data = ob_get_contents();
    ob_end_clean();
    return $data;
  }

  /*! @brief Does any pre-processing on the template.
    This performs the macro expansion. The language is very simple and is merely
    shorthand for the PHP tags.

    @param string Raw template data
    @return string Executable PHP
  */
  protected function _ProcessTemplate($data)
  {
    // The parsed output as compiled PHP.
    $processed = '';

    // If processing a macro, this contains the contents of the macro while
    // it is being extracted from the template.
    $macro = '';
    $length = strlen($data);
    $i = 0;  // The current position of the iterator.
    $looking_for_end = FALSE;  // Whehter or not an end tag is expected.
    $line_number = 1;  // The current line number.
    $i_last_line = 0;  // The value of |i| at the previous new line, used for column numbering.

    $open_tag_len = strlen(self::$open_tag);
    $close_tag_len = strlen(self::$close_tag);

    while ($i < $length) {
      // See how far the current position is from the end of the string.
      $delta = $length - $i;

      // When a new line is reached, update the counters.
      if ($data[$i] == "\n") {
        ++$line_number;
        $i_last_line = $i;
      }

      // Check for the open tag.
      if ($delta >= $open_tag_len &&
          substr($data, $i, $open_tag_len) == self::$open_tag) {
        // If an expansion has already been opened, then it's an error to nest.
        if ($looking_for_end) {
          $column = $i - $i_last_line;
          throw new TemplateException("Unexpected start of expansion at line $line_number:$column");
        }

        $looking_for_end = TRUE;
        $macro = '';
        $i += $open_tag_len;
        continue;
      }
      // Check for the close tag.
      else if ($delta >= $close_tag_len &&
               substr($data, $i, $close_tag_len) == self::$close_tag) {
        // If an end tag was encountered without an open tag, that's an error.
        if (!$looking_for_end) {
          $column = $i - $i_last_line;
          throw new TemplateException("Unexpected end of expansion at line $line_number:$column");
        }

        $expanded_macro = $this->_ProcessMacro($macro);
        $processed .= "<?php $expanded_macro ?>";
        $looking_for_end = FALSE;
        $i += $close_tag_len;
        continue;
      }

      // All other characters go into a storage bin. If currently in a macro,
      // save off the data separately for parsing.
      if ($looking_for_end)
        $macro .= $data[$i];
      else
        $processed .= $data[$i];
      ++$i;
    }

    return $processed;
  }

  /*!
    Takes the contents of a macro, i.e. the string between the open and close
    tags, and transforms it into a PHP statement.
  */
  protected function _ProcessMacro($macro)
  {
    if (strlen($macro) < 1)
      return $macro;

    // If the macro has a modifier character, as described in the class comment,
    // futher thransform the statement.
    switch ($macro[0]) {
      case '=': return $this->_ProcessInterpolation(substr($macro, 1));
      default:  return $macro;
    }
  }

  /*!
    Creates a printing expression for a value, optionally coercing and escaping
    it to a specific type.
  */
  protected function _ProcessInterpolation($macro)
  {
    // The pipe operator specifies how to sanitize the output.
    $formatter_pos = strrpos($macro, '|');

    // No specifier defaults to escaped string.
    if ($formatter_pos === FALSE)
      return 'echo hoplite\\base\\filter\\String(' . $macro . ')';

    // Otherwise, apply the right filter.
    $formatter = trim(substr($macro, $formatter_pos + 1));
    $function = '';
    switch (strtolower($formatter)) {
      case 'int':   $function = 'Int'; break;
      case 'float': $function = 'Float'; break;
      case 'str':   $function = 'String'; break;
      case 'raw':   $function = 'RawString'; break;
      default:
        throw new TemplateException('Invalid macro formatter "' . $formatter . '"');
    }

    // Now get the expression and return a PHP statement.
    $expression = trim(substr($macro, 0, $formatter_pos));
    return 'echo hoplite\\base\\filter\\' . $function . '(' . $expression . ')';
  }
}

class TemplateException extends \Exception {}
