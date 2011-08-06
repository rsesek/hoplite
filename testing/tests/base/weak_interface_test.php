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

require_once HOPLITE_ROOT . '/base/weak_interface.php';

interface AllOptional
{
  public function DoSomething();
  public function DoSomething1(array $a);
}

class AllOptionalImp
{
  public $do_something = FALSE;

  public function DoSomething()
  {
    $this->do_something = TRUE;
    return 'foo';
  }
}

class AllOptionalImpBad
{
  public function DoSomething1($a, $b) {}
}

interface OneRequired
{
  public function DoSomething();

  /** @required */
  public function DoRequired();
}

class OneRequiredImp
{
  public $do_required = FALSE;

  public function DoRequired()
  {
    $this->do_required = TRUE;
  }
}

class WeakInterfaceTest extends \PHPUnit_Framework_TestCase
{
  public function testAllOptional()
  {
    $delegate = new base\WeakInterface('hoplite\test\AllOptional');
    $delegate->Bind(new AllOptionalImp);
    $this->assertEquals('foo', $delegate->DoSomething());
    $this->assertTrue($delegate->get()->do_something);
    $delegate->DoSomething1();
  }

  public function testOneRequired()
  {
    $delegate = new base\WeakInterface('hoplite\test\OneRequired');
    $delegate->Bind(new OneRequiredImp);
    $delegate->DoRequired();
    $this->assertTrue($delegate->get()->do_required);
    $delegate->DoSomething();
  }

  public function testRequirements()
  {
    $this->setExpectedException('hoplite\base\WeakInterfaceException');
    $delegate = new base\WeakInterface('hoplite\test\OneRequired');
    $delegate->Bind(new AllOptionalImp);
  }

  public function testNull()
  {
    $this->setExpectedException('hoplite\base\WeakInterfaceException');
    $delegate = new base\WeakInterface('hoplite\test\AllOptional');
    $delegate->DoSomething();
  }

  public function testNullAllowed()
  {
    $delegate = new base\WeakInterface('hoplite\test\AllOptional');
    $delegate->set_null_allowed(TRUE);
    $delegate->DoSomething();
  }

  public function testMethodSignatures()
  {
    $this->setExpectedException('hoplite\base\WeakInterfaceException');
    $delegate = new base\WeakInterface('hoplite\test\AllOptional');
    $delegate->Bind(new AllOptionalImpBad);
  }

  public function testTiming()
  {
    $delegate = new base\WeakInterface('hoplite\test\AllOptional');
    $imp = new AllOptionalImp;
    $delegate->Bind($imp);

    $mt_s = microtime(TRUE);
    $delegate->DoSomething();
    $mt_e = microtime(TRUE);
    print 'WeakInterface: ' . ($mt_e - $mt_s) . 'µs' . "\n";

    $mt_s = microtime(TRUE);
    $imp->DoSomething();
    $mt_e = microtime(TRUE);
    print 'Straight Call: ' . ($mt_e - $mt_s) . 'µs' . "\n";
    
  }
}
