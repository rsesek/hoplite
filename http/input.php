<?php
// Hoplite
// Copyright (c) 2013 Blue Static
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

require_once HOPLITE_ROOT . '/base/filter.php';

/*!
  The Input class is responsible for sanitizing data from untrusted incoming
  HTTP requests.

  This class was designed to be used either in a PHP 5.4 environment, or one in
  which both magic_quotes (and all its flavors) and register_globals are Off.
*/
class Input
{
  /*! @const The raw input value, unsanitized and unsafe. */
  const TYPE_RAW = 1;
  /*! @const The value as a string with whitespace trimmed. */
  const TYPE_STR = 2;
  /*! @const The value as an integer. */
  const TYPE_INT = 3;
  /*! @const The value as an unsigned integer. */
  const TYPE_UINT = 4;
  /*! @const The value as a floating point number. */
  const TYPE_FLOAT = 5;
  /*! @const The string value parsed as a boolean expression. */
  const TYPE_BOOL = 6;
  /*! @const The string sanitized for HTML characters. */
  const TYPE_HTML = 7;

  /*! @const The $_REQUEST array. */
  const SOURCE_REQUEST = 'r';
  /*! @const The $_GET array. */
  const SOURCE_GET = 'g';
  /*! @const The $_POST array. */
  const SOURCE_POST = 'p';
  /*! @const The $_COOKIE array. */
  const SOURCE_COOKIE = 'c';

  /*! The sanitized input data. */
  public $in = array();

  /*!
    The constructor for the input sanitizer class. By default, this will escape
    all the data in _REQUEST into the ->in array using |$default_mode|.
  */
  public function __construct($default_mode = self::TYPE_STR)
  {
    if ($default_mode != self::TYPE_RAW) {
      $this->in = $this->_CleanArray($_REQUEST, $default_mode);
    }
  }

  /*!
    Cleans a variable of a given type from the _REQUEST source and returns the
    sanitized result. This is the most convenient way to access incoming data.
  */
  public function Clean($variable, $type)
  {
    return $this->InputClean(self::SOURCE_REQUEST, $variable, $type);
  }

  /*!
    Convenience function that takes an array of variable => type pairs and
    calls ::Clean() on each.
  */
  public function CleanArray($pairs)
  {
    $this->InputCleanArray(self::SOURCE_REQUEST, $pairs);
  }

  /*! @brief Fully qualified cleaning method.
    This method will clean a variable in a specific source array to the
    specified type.
  */
  public function InputClean($source, $variable, $type)
  {
    $global = $this->_GetSource($source);
    $value = $this->SanitizeScalar($global["$variable"], $type);
    $this->in["$variable"] = $value;
    return $value;
  }

  /*! Convenience function like ::CleanArray but specifies the source array. */
  public function InputCleanArray($source, $pairs)
  {
    foreach ($pairs as $variable => $type) {
      $this->InputClean($source, $variable, $type);
    }
  }

  /*! Recursive variant of ::InputClean. */
  public function InputCleanDeep($source, $variable, $type)
  {
    $global = $this->_GetSource($source);
    $array = $global["$variable"];
    $this->in["$variable"] = $this->_CleanArray($array, $type);
    return $this->in["$variable"];
  }

  private function _CleanArray($array, $type)
  {
    $out = array();
    foreach ($array as $key => $value) {
      if (is_array($value)) {
        $out["$key"] = $this->_CleanArray($value, $type);
      } else {
        $out["$key"] = $this->SanitizeScalar($value, $type);
      }
    }
    return $out;
  }

  /*!
    Routine that transforms an unclean input to its cleaned output.
  */
  public function SanitizeScalar($in, $type)
  {
    if (is_array($in) || is_object($in))
      throw new InputException('Cannot clean non-scalar value');

    static $is_true = array('true', 'yes', 'y', '1');

    switch ($type) {
      case self::TYPE_RAW: return $in;
      case self::TYPE_STR: return trim($in);
      case self::TYPE_INT: return intval($in);
      case self::TYPE_UINT: return ($data = intval($in)) < 0 ? 0 : $data;
      case self::TYPE_FLOAT: return floatval($in);
      case self::TYPE_BOOL: return in_array(strtolower(trim($in)), $is_true);
      case self::TYPE_HTML: return \hoplite\base\filter\String($in);
      default:
        throw new InputException('Cannot clean scalar to unknown type ' . $type);
    }
  }

  /*! Gets the source array associated with its short type. */
  private function & _GetSource($source)
  {
    switch ($source) {
      case self::SOURCE_REQUEST: return $_REQUEST;
      case self::SOURCE_GET: return $_GET;
      case self::SOURCE_POST: return $_POST;
      case self::SOURCE_COOKIE: return $_COOKIE;
      default:
        throw new InputException('Unknown source array ' . $source);
    }
  }
}

class InputException extends \Exception {}
