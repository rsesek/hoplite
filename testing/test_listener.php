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

// This TestListener is meant to print to stdout and show CLI output for the
// running test suite. It unfortunately conflicts with the standard text runner
// UI, so it must be configured manually (see runner.php for an example).
//
// The format of the output is designed to mimic the Google Test (GTest)
// <http://googletest.googlecode.com> framework output.
class TestListener extends \PHPUnit_Util_Printer implements \PHPUnit_Framework_TestListener
{
    const COLOR_NONE = 0;
    const COLOR_RED = 1;
    const COLOR_GREEN = 2;
    const COLOR_BLUE = 3;
    const COLOR_PURPLE = 4;
    const COLOR_CYAN = 5;

    // The start time of the test suite.
    private $suite_start_time = 0;

    // The suite depth.
    private $suite_depth = 0;

    // The number of errors that occured in a suite.
    private $suite_error_counts = 0;

    // Array of failing tests.
    private $failing = array();

    // Array of skipped tests.
    private $skipped = array();

    // Array of incomplete tests.
    private $incomplete = array();

    // An error occurred.
    public function addError(\PHPUnit_Framework_Test $test,
                             \Exception $e,
                             $time)
    {
        $this->_Print(NULL, $this->_ErrorLocation($e));
        $this->_Print('  ', $e->GetMessage());
        $this->_Print('[    ERROR ]', $test->ToString() . ' (' . $this->_Round($time) . ' ms)', self::COLOR_RED);
        ++$this->suite_error_count;
        $this->failing[] = $test->ToString();
    }

    // A failure occurred.
    public function addFailure(\PHPUnit_Framework_Test $test,
                               \PHPUnit_Framework_AssertionFailedError $e,
                               $time)
    {
        $this->_Print(NULL, $this->_ErrorLocation($e));
        $this->_Print('  ', $e->GetMessage());
        $this->_Print('[  FAILED  ]', $test->ToString() . ' (' . $this->_Round($time) . ' ms)', self::COLOR_RED);
        ++$this->suite_error_count;
        $this->failing[] = $test->ToString();
    }

    // Incomplete test.
    public function addIncompleteTest(\PHPUnit_Framework_Test $test,
                                      \Exception $e, $time)
    {
        $this->incomplete[] = $test->ToString();
        $this->_Print('INCOMPLETE', $e->GetMessage(), self::COLOR_PURPLE);
    }

    // Skipped test.
    public function addSkippedTest(\PHPUnit_Framework_Test $test,
                                   \Exception $e,
                                   $time)
    {
        $this->skipped[] = $test->ToString();
        $this->_Print('SKIPPED', $e->GetMessage(), self::COLOR_BLUE);
    }

    // A test suite started.
    public function startTestSuite(\PHPUnit_Framework_TestSuite $suite)
    {
        // Wrap the suite header.
        ob_start();

        $this->_Print($this->_SuiteMarker(), $this->_DescribeSuite($suite), self::COLOR_GREEN);
        $this->suite_start_time = microtime(TRUE);
        ++$this->suite_depth;
        $this->suite_error_count = 0;

        // Wrap the suite contents.
        ob_start();
    }

    // A test suite ended.
    public function endTestSuite(\PHPUnit_Framework_TestSuite $suite)
    {
        $main_suite = (--$this->suite_depth == 0);
        $color_red  = (($main_suite && count($this->failing)) || $this->suite_error_count > 0);
        $any_output = ob_get_length();

        $delta = microtime(TRUE) - $this->suite_start_time;
        $this->_Print(
            $this->_SuiteMarker(),
            $this->_DescribeSuite($suite) . ' (' . $this->_Round($delta) . ' ms total)',
            ($color_red ? self::COLOR_RED : self::COLOR_GREEN));
        $this->Write("\n");

        // If this is the main suite (the one to which all other tests/suites
        // are attached), then print the test summary.
        if ($main_suite && $color_red) {
            $count = count($this->failing);
            $tests = $this->_Plural('TEST', $count, TRUE);
            $this->Write($this->_Color("  YOU HAVE $count FAILING $tests:\n", self::COLOR_RED));
            foreach ($this->failing as $test) {
                $this->Write("  $test\n");
            }
            $this->Write("\n");
        }

        $count = count($this->incomplete);
        $any_output |= $count > 0;
        if ($main_suite && $count) {
            $tests = $this->_Plural('TEST', $count, TRUE);
            $this->Write($this->_Color("  YOU HAVE $count INCOMPLETE $tests:\n", self::COLOR_PURPLE));
            foreach ($this->incomplete as $test) {
                $this->Write("  $test\n");
            }
            $this->Write("\n");
        }

        $count = count($this->skipped);
        if ($main_suite && $count) {
            $tests = $this->_Plural('TEST', $count, TRUE);
            $this->Write($this->_Color("  YOU HAVE $count SKIPPED $tests:\n", self::COLOR_BLUE));
            foreach ($this->skipped as $test) {
                $this->Write("  $test\n");
            }
            $this->Write("\n");
        }

        // Flush the test output.
        ob_end_flush();

        // Flush the suite header.
        if ($main_suite || $any_output)
            ob_end_flush();
        else
            ob_end_clean();
    }

    // A test started.
    public function startTest(\PHPUnit_Framework_Test $test)
    {
        $this->_Print('[ RUN      ]', $test->ToString(), self::COLOR_GREEN);
    }

    // A test ended.
    public function endTest(\PHPUnit_Framework_Test $test, $time)
    {
        $name = $test->ToString();
        if (in_array($name, $this->skipped) || in_array($name, $this->incomplete)) {
            $this->_Print('[    ABORT ]', $name . ' (' . $this->_Round($time) . ' ms)', self::COLOR_CYAN);
        } else if (!in_array($name, $this->failing)) {
            $this->_Print('[       OK ]', $name . ' (' . $this->_Round($time) . ' ms)', self::COLOR_GREEN);
        }
    }

    // Returns the description for a test suite.
    private function _DescribeSuite(\PHPUnit_Framework_TestSuite $suite)
    {
        $count = $suite->Count();
        return sprintf('%d %s from %s', $count, $this->_Plural('test', $count), $suite->GetName());
    }

    // Returns the test suite marker.
    private function _SuiteMarker()
    {
        if ($this->suite_depth == 0)
            return '[==========]';
        else
            return '[----------]';
    }

    // Prints a line to output.
    private function _Print($column, $annotation, $color = self::COLOR_NONE)
    {
        $column = $this->_Color($column, $color);
        $this->Write("$column $annotation\n");
    }

    // Takes in a float from microtime() and returns it formatted to display as
    // milliseconds.
    private function _Round($time)
    {
        return round($time * 1000);
    }

    // Returns the error location as a string.
    private function _ErrorLocation(\Exception $e)
    {
        $trace = $e->GetTrace();
        $frame = NULL;
        // Find the first frame from non-PHPUnit code, which is where the error
        // should have occurred.
        foreach ($trace as $f) {
            if (isset($f['file']) && strpos($f['file'], 'PHPUnit/Framework') === FALSE) {
                $frame = $f;
                break;
            }
        }
        if (!$frame)
            $frame = $trace[0];
        return $frame['file'] . ':' . $frame['line'];
    }

    // Colors |$str| to be a certain |$color|.
    private function _Color($str, $color)
    {
        $color_code = '';
        switch ($color) {
            case self::COLOR_RED:    $color_code = '0;31'; break;
            case self::COLOR_GREEN:  $color_code = '0;32'; break;
            case self::COLOR_BLUE:   $color_code = '0;34'; break;
            case self::COLOR_PURPLE: $color_code = '0;35'; break;
            case self::COLOR_CYAN:   $color_code = '0;36'; break;
        }
        if ($color == self::COLOR_NONE) {
            return $str;
        }        
        return "\x1b[{$color_code}m{$str}\x1b[0m";
    }

    // Returns the plural of the |$word| if |$count| is greater than one.
    private function _Plural($word, $count, $capitalize = FALSE)
    {
        if ($count > 1)
            return $word . ($capitalize ? 'S' : 's');
        return $word;
    }
}
