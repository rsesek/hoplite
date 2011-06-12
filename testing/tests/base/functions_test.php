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
use hoplite\base as base;

require_once HOPLITE_ROOT . '/base/functions.php';

class FunctionsTest extends \PHPUnit_Framework_TestCase
{
    public function testArrayStripEmpty()
    {
        $array = array(1, 4, 6);
        base\ArrayStripEmpty($array);
        $this->assertEquals(3, count($array));

        $array = array(1, 0, 5, '');
        base\ArrayStripEmpty($array);
        $this->assertEquals(2, count($array));

        $array = array('', 'test' => array('', 6));
        base\ArrayStripEmpty($array);
        $this->assertEquals(1, count($array));
        $this->assertEquals(1, count($array['test']));

        $array = array('foo', NULL, 'bar');
        base\ArrayStripEmpty($array);
        $this->assertEquals(2, count($array));
    }

    public function testUnderscoreToCamelCase()
    {
        $str = 'under_score';
        $this->assertEquals('UnderScore', base\UnderscoreToCamelCase($str));
        $this->assertEquals('underScore', base\UnderscoreToCamelCase($str, FALSE));

        $str = 'many_many_under_scores';
        $this->assertEquals('ManyManyUnderScores', base\UnderscoreToCamelCase($str));
    }

    public function testCamelCaseToUnderscore()
    {
        $str = 'CamelCase';
        $this->assertEquals('camel_case', base\CamelCaseToUnderscore($str));

        $str = 'camelCase';
        $this->assertEquals('camel_case', base\CamelCaseToUnderscore($str));

        $str = 'AVeryLongTitleCase';
        $this->assertEquals('a_very_long_title_case', base\CamelCaseToUnderscore($str));
    }

    protected function _RandomHelper($arg, $lower, $upper)
    {
        $list = array();
        for ($i = 0; $i < 200; $i++)
        {
            $rand = base\Random($arg);
            $this->assertNotContains($rand, $list, 'Duplicate random string!');
            $list[] = $rand;
            $this->assertGreaterThanOrEqual($lower, strlen($rand), 'Random string not in lower bound');
            $this->assertLessThanOrEqual($upper, strlen($rand), 'Random string not in upper bound');
        }
    }

    public function testRandomDefault()
    {
        $this->_RandomHelper(NULL, 20, 100);
    }

    public function testRandomArgument()
    {
        $this->_RandomHelper(20, 20, 20);
    }
}
