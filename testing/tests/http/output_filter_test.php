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
use hoplite\http as http;

require_once HOPLITE_ROOT . '/http/output_filter.php';

class TestOutputFilter extends http\OutputFilter
{
  public function T_EncodeXML($d)
  {
    return $this->_EncodeXML($d);
  }
}

class OutputFilterTest extends \PHPUnit_Framework_TestCase
{
  public function setUp()
  {
    $this->fixture = new TestOutputFilter(new http\RootController(array()));
  }

  public function testEncodeXML()
  {
    $array = array(
      'test' => 1,
      'foo' => 'bar',
      'bar' => '<strong>baz</strong>',
      'baz' => array(
        'poo' => 'moo',
        'moo' => 'baa'
      )
    );
    $expected = <<<XML
<?xml version="1.0"?>
<response><test>1</test><foo>bar</foo><bar>&lt;strong&gt;baz&lt;/strong&gt;</bar><baz><poo>moo</poo><moo>baa</moo></baz></response>

XML;
    
    $this->assertEquals($expected, $this->fixture->T_EncodeXML($array));

    $obj = new \stdClass();
    $obj->int = 2;
    $obj->obj = new \stdClass();
    $obj->obj->string = 'Foo';
    $expected = <<<XML
<?xml version="1.0"?>
<response><int>2</int><obj><string>Foo</string></obj></response>

XML;
    $this->assertEquals($expected, $this->fixture->T_EncodeXML($obj));
  }
}
