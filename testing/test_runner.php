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

if (!defined('HOPLITE_ROOT')) {
    define('HOPLITE_ROOT', dirname(dirname(__FILE__)));
    define('TEST_ROOT', dirname(__FILE__));
}

// PHPUnit 3.5.5.
require_once 'PHPUnit/Autoload.php';
require_once TEST_ROOT . '/test_listener.php';

class HopliteTestRunner extends \PHPUnit_TextUI_Command
{
    static public function Main($exit = TRUE)
    {
        $command = new self();
        $command->Run($_SERVER['argv'], $exit);
    }

    protected function HandleCustomTestSuite()
    {
        $this->arguments['printer'] = new TestListener();
    }
}

HopliteTestRunner::Main();
