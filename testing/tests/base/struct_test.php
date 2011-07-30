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

namespace hoplite\test;
use \hoplite\base as base;

require_once TEST_ROOT . '/tests/base.php';

class TestStruct extends base\Struct
{
  protected $fields = array(
    'first',
    'second',
    'third'
  );
}

class StructTest extends \PHPUnit_Framework_TestCase
{
  public $struct;

  public function setUp()
  {
    $this->struct = new TestStruct();
  }

  public function testCount()
  {
    $this->assertEquals(3, $this->struct->Count());
    $this->struct->first = 'foo';
    $this->assertEquals(3, $this->struct->Count());    
  }

  public function testSet()
  {
    $this->struct->first  = 1;
    $this->struct->second = 2;
    $this->struct->third  = 3;
  }

  public function testSetInvalid()
  {
    $this->setExpectedException('hoplite\base\StructException');
    $this->struct->fourth = 4;
  }

  public function testGetNull()
  {
    $this->assertNull($this->struct->first);
    $this->assertNull($this->struct->second);
    $this->assertNull($this->struct->third);
  }

  public function testGet()
  {
    $this->struct->first = 1;
    $this->assertEquals(1, $this->struct->first);
  }

  public function testGetInvalid()
  {
    $this->setExpectedException('hoplite\base\StructException');
    $foo = $this->struct->fourth;
  }

  public function testSetFromArray()
  {
    $array = array(
      'first'  => 1,
      'second' => 2,
      'fourth' => 4
    );
    $this->struct->SetFrom($array);
    $this->assertEquals(1, $this->struct->first);
    $this->assertEquals(2, $this->struct->second);
    $this->assertNull($this->struct->third);
    $this->assertEquals(3, $this->struct->Count());
  }

  public function testSetFromObject()
  {
    $obj = new \StdClass();
    $obj->first  = 1;
    $obj->second = 2;
    $obj->fourth = 4;
    $this->struct->SetFrom($obj);
    $this->assertEquals(1, $this->struct->first);
    $this->assertEquals(2, $this->struct->second);
    $this->assertNull($this->struct->third);
    $this->assertEquals(3, $this->struct->Count());
  }

  public function testToArray()
  {
    $this->struct->first = 'alpha';
    $this->struct->third = 'bravo';
    $array = array(
      'first' => 'alpha',
      'third' => 'bravo'
    );
    $this->assertEquals($array, $this->struct->ToArray());
  }
}
