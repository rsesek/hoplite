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
  A struct can be used to maintain a list of properties. Any properties not
  strictly defined in the |$fields| array will throw exceptions when accessed.
*/
class Struct
{
  /*! @var array The fields of this struct. */
  protected $fields = array();

  /*! @var array The data that corresponds to the |$fields|. */
  protected $data = array();

  /*!
    Creates a new Struct, optionally initializing the data members from an
    iterable object.
  */
  public function __construct($data = array())
  {
    foreach ($data as $key => $value)
      if (!in_array($key, $this->fields))
        throw new StructException('Invalid field "' . $key . '"');
      else
        $this->data[$key] = $value;
  }

  /*! Gets a specified key. This can return NULL. */
  public function __get($key)
  {
    if (!in_array($key, $this->fields))
      throw new StructException('Invalid field "' . $key . '"');

    if (!isset($this->data[$key]))
      return NULL;

    return $this->data[$key];
  }
  public function Get($key) { return $this->__get($key); }

  /*! Sets the specified key-value pair. */
  public function __set($key, $value)
  {
    if (!in_array($key, $this->fields))
      throw new StructException('Cannot set value for invalid field "' . $key . '"');
    $this->data[$key] = $value;
  }
  public function Set($key, $value) { $this->__set($key, $value); }

  /*! Takes values from an array or object and sets the relevant values. */
  public function SetFrom($array)
  {
    foreach ($array as $key => $value)
      if (in_array($key, $this->fields))
        $this->data[$key] = $value;
  }

  /*! Returns the data members as an array. */
  public function ToArray()
  {
    return $this->data;
  }

  /*! Returns the number of members in the struct. */
  public function Count()
  {
    return count($this->fields);
  }

  /*! Returns the list of fields the Struct has. */
  public function GetFields()
  {
    return $this->fields;
  }
}

class StructException extends \Exception {}
