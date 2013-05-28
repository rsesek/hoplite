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

namespace hoplite\base;

/*!
  A static class used to help profile Hoplite components.
*/
class Profiling
{
  /*! @var bool Enable profiling. */
  static private $profiling_enabled = FALSE;

  /*! Enables profiling globally. */
  static public function EnableProfiling()
  {
    self::$profiling_enabled = TRUE;
  }

  /*! Gets the current state for profiling. */
  static public function IsProfilingEnabled()
  {
    return self::$profiling_enabled;
  }

  /*!
    Prepares a debug_backtrace() array for output to the browser by
    compressing the array into visible text

    @param array The return value from debug_backtrace().

    @return array Array of strings that can be imploded() for display.
  */
  static public function FormatDebugBacktrace($backtrace)
  {
    $trace = array();
    foreach ($backtrace AS $i => $frame) {
      $args = '';
      $file = $frame['file'] . ':' . $frame['line'];
      $file = str_replace($_SERVER['DOCUMENT_ROOT'], '', $file);
      $funct = (isset($frame['class']) ? $frame['class'] . '::' . $frame['function'] : $frame['function']);

      if (isset($frame['args']) && is_array($frame['args'])) {
        // Convert arrays and objects to strings.
        foreach ($frame['args'] AS $id => $arg) {
          if (is_array($arg))
            $frame['args']["$id"] = 'Array';
          else if (is_object($arg))
            $frame['args']["$id"] = get_class($arg);
        }
        $args = implode(', ', $frame['args']);
      }

      $trace[] = "#$i  $funct($args) called at [$file]";
    }

    return $trace;
  }
}
