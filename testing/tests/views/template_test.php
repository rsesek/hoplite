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
use hoplite\views\Template;

require_once HOPLITE_ROOT . '/views/template.php';

class TemplateTest extends \PHPUnit_Framework_TestCase
{
  private function _Render($template)
  {
    return $template->Render();
  }

  public function testRenderSimple()
  {
    $template = Template::NewWithData('Hello World');
    $this->assertEquals('Hello World', $this->_Render($template));
  }

  public function testRender1Var()
  {
    $template = Template::NewWithData('Hello, {% $name | str %}');
    $template->name = 'Robert';
    $this->assertEquals('Hello, Robert', $this->_Render($template));
  }

  public function testRender2Vars()
  {
    $template = Template::NewWithData('Hello, {% $name %}. Today is the {% $date->day | int %} of July.');
    $date = new \stdClass();
    $date->day = 26;
    $template->name = 'Robert';
    $template->date = $date;
    $this->assertEquals('Hello, Robert. Today is the 26 of July.', $this->_Render($template));
  }

  public function testRenderIf()
  {
    $template = Template::NewWithData(
        'You are {!% if (!$user->logged_in): %}not logged in{!% else: %}{% $user->name %}{!% endif %}');
    $template->user = new \stdClass();
    $template->user->logged_in = TRUE;
    $template->user->name = 'Robert';
    $this->assertEquals('You are Robert', $this->_Render($template));

    $template->user->logged_in = FALSE;
    $this->assertEquals('You are not logged in', $this->_Render($template));
  }

  public function testExceptions()
  {
    try {
      $catch = FALSE;
      $template = Template::NewWithData('Hello %}');
    } catch (\hoplite\views\TemplateException $e) {
      $message = $e->GetMessage();
      // Check that the column number is correct.
      $this->assertTrue(strpos($message, '1:6') !== FALSE);
      $catch = TRUE;
    }
    $this->assertTrue($catch);

    try {
      $catch = FALSE;
      $template = Template::NewWithData("Salve\n{% {%");
    } catch (\hoplite\views\TemplateException $e) {
      $message = $e->GetMessage();
      $this->assertTrue(strpos($message, '2:4') !== FALSE);
      $catch = TRUE;
    }
    $this->assertTrue($catch);

    try {
      $catch = FALSE;
      $template = Template::NewWithData("Salve\n\n{% \$name {!%");
    } catch (\hoplite\views\TemplateException $e) {
      $message = $e->GetMessage();
      $this->assertTrue(strpos($message, '3:10') !== FALSE);
      $catch = TRUE;
    }
    $this->assertTrue($catch);
  }

  public function testRenderVars()
  {
    $template = Template::NewWithData('Some {% $v %}');
    $this->assertEquals('Some value', $template->Render(array('v' => 'value')));

    $template->v = 'other';
    $this->assertEquals('Some thing', $template->Render(array('v' => 'thing')));

    $this->assertEquals('Some other', $template->Render());
  }
}
