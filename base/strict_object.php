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
  Objects that expose public properties without accessors |public $foo = NULL;|
  should inherit from this class to prevent undefined property access. This
  prevents subtle bugs caused by typos. For arbitrary key-value storage, use a
  Dictionary.
*/
class StrictObject
{
  public function __get($key)
  {
    throw new StrictObjectException('Cannot get ' . get_class($this) . '::' . $key);
  }

  public function __set($key, $value)
  {
    throw new StrictObjectException('Cannot set ' . get_class($this) . '::' . $key);
  }
}

class StrictObjectException extends \Exception {}
