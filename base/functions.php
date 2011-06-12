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

namespace hoplite\base;

/*!
 Iterates over an array and unsets any empty elements in the array. This
 operates on the parameter itself.
*/
function ArrayStripEmpty(Array & $array)
{
  foreach ($array as $key => $value)
    if (is_array($array[$key]))
      ArrayStripEmpty($array[$key]);
    else if (empty($value))
      unset($array[$key]);
}

/*!
  Turns an under_scored string into a CamelCased one. If |$first_char| is
  TRUE, then the first character will also be capatalized.
*/
function UnderscoreToCamelCase($string, $first_char = TRUE)
{
  if ($first_char)
    $string[0] = strtoupper($string[0]);
  return preg_replace_callback('/_([a-z])/',
      function($c) { return strtoupper($c[1]); },
      $string);
}

/*!
  Turns a CamelCase string to an under_scored one.
*/
function CamelCaseToUnderscore($string)
{
  $string = preg_replace('/([A-Z]+)([A-Z][a-z])/','\1_\2',$string);
  $string = preg_replace('/([a-z])([A-Z])/','\1_\2', $string);
  return strtolower($string);
}

/*!
  Creates a random string of length |$length|, or a random length between 20
  and 100 if NULL.
*/
function Random($length = NULL)
{
  if ($length === NULL)
    $length = rand(20, 100);

  $string = '';
  for ($i = 0; $i < $length; $i++) {
    $type = rand(0, 300);
    if ($type < 100)
      $string .= rand(0, 9);
    else if ($type < 200)
      $string .= chr(rand(65, 90));
    else
      $string .= chr(rand(97, 122));
  }

  return $string;
}
