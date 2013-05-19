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

namespace hoplite\test;
use hoplite\http\Input;

require_once HOPLITE_ROOT . '/http/input.php';

/**
 * @backupGlobals enabled
 */
class InputTest extends \PHPUnit_Framework_TestCase
{
  public function setUp()
  {
    $_GET = array(
      'gint' => '12',
      'gfloat' => '3.14',
      'gstr' => '"Hello World"',
      'ghtml' => '<blink>"Hello World"</blink>',
      'gbool' => 'True',
    );
    $_POST = array(
      'pstr' => '<script type="text/javascript"> alert("Hello world"); </script>',
      'pbool' => 'YES',
      'parray' => array('13', 15, '19', '22.23'),
    );
    $_REQUEST = array_merge($_GET, $_POST);
  }

  public function testDefaultMode()
  {
    $input = new Input();
    $this->assertEquals('"Hello World"', $input->in['gstr']);

    $input = new Input(Input::TYPE_RAW);
    $this->assertArrayNotHasKey('gstr', $input->in);
  }

  public function testClean()
  {
    $input = new Input();
    $this->assertSame(12, $input->Clean('gint', Input::TYPE_INT));
    $this->assertSame(12, $input->in['gint']);
  }

  public function testCleanArray()
  {
    $input = new Input();
    $input->CleanArray(array(
      'gfloat' => Input::TYPE_FLOAT,
      'pbool' => Input::TYPE_BOOL,
    ));
    $this->assertSame(3.14, $input->in['gfloat']);
    $this->assertSame(TRUE, $input->in['pbool']);
  }

  public function testInputCleanDeep()
  {
    $input = new Input();
    $test = $input->InputCleanDeep('p', 'parray', Input::TYPE_UINT);
    $this->assertSame(array(13, 15, 19, 22), $test);
    $this->assertSame($test, $input->in['parray']);
  }
}
