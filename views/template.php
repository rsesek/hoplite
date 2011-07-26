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

require_once HOPLITE_ROOT . '/base/filter.php';

/*!
 A Template is initialized with a text file (typically HTML) and can render it
 with data from some model. It has a short macro expansion system, equivalent
 to PHP short open tags, but usable on all installations. Template caching of
 the parsed state is available.
*/
class Template
{
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

  static public function NewWithData($data)
  {
    $template = new Template('');
    $template->data = $template->_ProcessTemplate($data);
    return $template;
  }

  static public function NewFromCompiledData($data)
  {
    $template = new Template('');
    $template->data = $data;
    return $template;
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
  public function Render()
  {
    $_template = $this->data;
    $_vars = $this->vars;
    $render = function () use ($_template, $_vars) {
      extract($_vars);
      eval('?>' . $_template . '<' . '?');
    };
    $render();
  }

  /*! @brief Does any pre-processing on the template.
    This performs the macro expansion. The language is very simple and is merely
    shorthand for the PHP tags.

    The most common thing needed in templates is string escaped output from an
    expression. HTML entities are automatically escaped in this format:
      <p>Hello, {% $user->name %}!</p>

    To specify the type to format, you use the pipe symbol and then one of the
    following types: str (default; above), int, float, raw.
      <p>Hello, {% %user->name | str %}</p>
      <p>Hello, user #{% $user->user_id | int %}</p>

    To evaluate a non-printing expression, simply add a '!' before the first '%':
      {!% if (!$user->user_id): %}<p>Hello, Guest!</p>{!% endif %}

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
    $in_macro = FALSE;

    $length = strlen($data);
    $i = 0;  // The current position of the iterator.
    $looking_for_end = FALSE;  // Whehter or not an end tag is expected.
    $line_number = 1;  // The current line number.
    $i_last_line = 0;  // The value of |i| at the previous new line, used for column numbering.

    while ($i < $length) {
      // See how far the current position is from the end of the string.
      $delta = $length - $i;

      // When a new line is reached, update the counters.
      if ($data[$i] == "\n") {
        ++$line_number;
        $i_last_line = $i;
      }

      // Check for simple PHP short-tag expansion.
      if ($delta >= 3 && substr($data, $i, 3) == '{!%') {
        // If an expansion has already been opened, then it's an error to nest.
        if ($looking_for_end) {
          $column = $i - $i_last_line;
          throw new TemplateException("Unexpected start of expansion at line $line_number:$column");
        }

        $looking_for_end = TRUE;
        $processed .= '<' . '?php';
        $i += 3;
        continue;
      }
      // Check for either the end tag or the start of a macro expansion.
      else if ($delta >= 2) {
        $substr = substr($data, $i, 2);
        // Check for an end tag.
        if ($substr == '%}') {
          // If an end tag was encountered without an open tag, that's an error.
          if (!$looking_for_end) {
            $column = $i - $i_last_line;
            throw new TemplateException("Unexpected end of expansion at line $line_number:$column");
          }

          // If this is a macro, it's time to process it.
          if ($in_macro)
            $processed .= $this->_ProcessMacro($macro);

          $looking_for_end = FALSE;
          $in_macro = FALSE;
          $processed .= ' ?>';
          $i += 2;
          continue;
        }
        // Check for the beginning of a macro.
        else if ($substr == '{%') {
          // If an expansion has already been opened, then it's an error to nest.
          if ($looking_for_end) {
            $column = $i - $i_last_line;
            throw new TemplateException("Unexpected start of expansion at line $line_number:$column");
          }

          $processed .= '<' . '?php echo ';
          $macro = '';
          $in_macro = TRUE;
          $looking_for_end = TRUE;
          $i += 2;
          continue;
        }
      }

      // All other characters go into a storage bin. If currently in a macro,
      // save off the data separately for parsing.
      if ($in_macro)
        $macro .= $data[$i];
      else
        $processed .= $data[$i];
      ++$i;
    }

    return $processed;
  }

  /*!
    Takes the contents of a macro |{% $some_var | int %}|, which is the part in
    between the open and close brackets (excluding '%') and transforms it into a
    PHP statement.
  */
  protected function _ProcessMacro($macro)
  {
    // The pipe operator specifies how to sanitize the output.
    $formatter_pos = strrpos($macro, '|');

    // No specifier defaults to escaped string.
    if ($formatter_pos === FALSE)
      return 'hoplite\\base\\filter\\String(' . $macro . ')';

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
    return 'hoplite\\base\\filter\\' . $function . '(' . $expression . ')';
  }
}

class TemplateException extends \Exception {}
