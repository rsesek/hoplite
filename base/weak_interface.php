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

namespace hoplite\base {

/*!
  A WeakInterface is a class that implements some interface and then can bind
  a subset of those methods to some other object. This allows a delegate pattern
  to be implemented more easily in that the delegate class need not implement
  every method.

  To use this class, define an interface as you normally would. When making a
  call to an object that implements the interface, create a WeakInterface object
  and instead make the calls on that.
*/
class WeakInterface
{
  /*! @var array Map of MethodImps keyed by the method name. */
  private $imps = array();

  /*! @var object The object to which this interface is bound. */
  private $object = NULL;

  /*! @var ReflectionClass The reflector of #object. */
  private $object_reflector = NULL;

  /*! @var bool Whether or not a NULL object will throw when called. */
  private $null_allowed = FALSE;

  /*! Creates a weak interface with the name of an actual interface. */
  public function __construct($interface)
  {
    $reflector = new \ReflectionClass($interface);
    $methods = $reflector->GetMethods();
    foreach ($methods as $method) {
      $name = $method->name;
      $this->imps[$name] = new internal\MethodImp($this, $method);
    }
  }

  /*! Gets the object to which this interface is bound. */
  public function get()
  {
    return $this->object;
  }

  /*! Sets whether NULL is allowed. */
  public function set_null_allowed($flag)
  {
    $this->null_allowed = $flag;
  }

  /*! Binds an object to the interface. */
  public function Bind($object)
  {
    $this->_CheckObject($object);
    $this->object = $object;
    $this->object_reflector = new \ReflectionClass($this->object);
  }

  /*! Magic method that performs the actual method invocation. */
  public function __call($meth, $args)
  {
    if (!$this->object) {
      if (!$this->null_allowed)
        throw new WeakInterfaceException('NULL object when calling ' . $meth);
      else
        return;
    }

    return $this->imps[$meth]->Invoke($this->object_reflector, $args);
  }

  /*! Ensures that the bound object properly implements the interface. */
  private function _CheckObject($object)
  {
    $reflector = new \ReflectionClass($object);
    $methods = $reflector->GetMethods();
    $by_name = array();
    foreach ($methods as $method) {
      $by_name[$method->name] = $method;
    }

    foreach ($this->imps as $name => $imp) {
      if ($imp->IsRequired() && !isset($by_name[$name]))
        throw new WeakInterfaceException($reflector->name . ' does not implement required interface method ' . $name);

      if (isset($by_name[$name]) && !$imp->Matches($by_name[$name]))
        throw new WeakInterfaceException($reflector->name . '::' . $name . ' does not match interface definition');
    }
  }
}

class WeakInterfaceException extends \Exception {}

}  // namespace hoplite\base

namespace hoplite\base\internal {

/*!
  An encapsulation of a method implementation.
*/
class MethodImp
{
  /*! @var WeakInterface The interface to which this method belongs. */
  private $interface = NULL;

  /*! @var ReflectionMethod The method of the actual interface that this is implementing. */
  private $method = NULL;

  /*! @var bool Whether or not this is required. */
  private $required = FALSE;

  public function __construct(\hoplite\base\WeakInterface $interface,
                              \ReflectionMethod $method)
  {
    $this->interface = $interface;
    $this->method = $method;
    $this->required = strpos($this->method->GetDocComment(), '@required') !== FALSE;
  }

  /*! Forwards a method call. */
  public function Invoke($reflector, $args)
  {
    try {
      $impl = $reflector->GetMethod($this->method->name);
    } catch (\ReflectionException $e) {
      if ($this->required)
        throw $e;
      return;
    }
    return $impl->InvokeArgs($this->interface->get(), $args);
  }

  /*! Performs method signature validation. */
  public function Matches(\ReflectionMethod $method)
  {
    return $method->GetParameters() == $this->method->GetParameters();
  }

  /*! Checks if a method is marked as required. */
  public function IsRequired()
  {
    return $this->required;
  }
}

}  // namespace hoplite\base\internal
