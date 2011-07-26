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
    ob_start();
    $template->Render();
    $data = ob_get_contents();
    ob_end_clean();
    return $data;
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
    $template = Template::NewWithData('Hello, {% $name %}. Today is the {% $date->day %} of July.');
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
}
