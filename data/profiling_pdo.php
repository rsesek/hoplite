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

namespace hoplite\data;

use \hoplite\base\Profiling;

require_once HOPLITE_ROOT . '/base/profiling.php';

/*!
  A wrapper class around PDO that profiles all calls to the database.

  This can be used as a drop-in for \PDO, though it sets the
  PDO::ATTR_STATEMENT_CLASS to a custom instance.
*/
class ProfilingPDO extends \PDO
{
  private $traces = array();

  private $statements = array();

  public function __construct()
  {
    $args = func_get_args();
    call_user_func_array('parent::__construct', $args);
    $this->SetAttribute(\PDO::ATTR_STATEMENT_CLASS,
        array('\\hoplite\\data\\ProfilingPDOStatement', array($this)));
  }

  public function exec()
  {
    $start = microtime(true);

    $args = func_get_args();
    $r = call_user_func_array('parent::exec', $args);

    $this->traces[] = array(
      'start' => $start,
      'end'   => microtime(true),
      'trace' => debug_backtrace(),
      'query' => $statement,
    );
    return $r;
  }

  public function query()
  {
    $start = microtime(true);

    $args = func_get_args();
    $r = call_user_func_array('parent::query', $args);

    $this->traces[] = array(
      'start' => $start,
      'end'   => microtime(true),
      'trace' => debug_backtrace(),
      'query' => $args[0],
    );

    return $r;
  }

  public function prepare()
  {
    $start = microtime(true);

    $args = func_get_args();
    $r = call_user_func_array('parent::prepare', $args);

    $this->traces[] = array(
      'start'   => $start,
      'end'     => microtime(true),
      'trace'   => debug_backtrace(),
      'query'   => $args[0],
      'prepare' => TRUE,
    );

    return $r;
  }

  /*!
    Returns the array of all the traces.
  */
  public function GetTraces()
  {
    return $this->traces;
  }

  /*!
    Generates a block of HTML that displays information about the query traces.
  */
  public function ConstructHTMLDebugBlock()
  {
    $debug = '';

    $debug .= "<br />\n";
    $debug .= '<table cellpadding="4" cellspacing="1" border="0" align="center" width="30%" ' .
        'style="background-color: rgb(60, 60, 60); color: white">' . "\n\t";
    $debug .= '<tr><td><strong>Query Debug: ' . sizeof($this->traces) . ' Total </strong></td></tr>';
    foreach ($this->traces as $query) {
      $italic = isset($query['prepare']) && $query['prepare'] ? 'font-style: italic' : '';
      $debug .= "\n\t<tr style=\"background-color: rgb(230, 230, 230); color: black; $italic\">";
      $debug .= "\n\t\t<td>";
      $debug .= "\n\t\t\t$query[query]\n\n";
      if (isset($query['params'])) {
        $debug .= "\t\t\t<ol>\n\t\t\t\t<li>";
        $debug .= implode("</li>\n\t\t\t\t<li>", $query['params']);
        $debug .= "</li>\n\t\t\t</ol>\n";
      }
      $debug .= "\n\t\t\t<div style=\"font-size: 9px;\">(" .
          ($query['end'] - $query['start']) . ")</div>\n";
      $debug .= "<!--\n" . implode("\n", Profiling::FormatDebugBacktrace($query['trace'])) .
          "\n-->\n\t\t</td>\n\t</tr>";
    }

    $debug .= "\n</table>\n\n\n";

    return $debug;
  }

  /*!
    Internal function that records a query trace. This is public only for use b
    ProfilingPDOStatement.
  */
  public function RecordTrace(array $trace)
  {
    $this->traces[] = $trace;
  }
}

/*!
  A companion to ProfilingPDO that profiles prepared statements.
*/
class ProfilingPDOStatement extends \PDOStatement
{
  private $pdo = NULL;

  protected function __construct(ProfilingPDO $pdo)
  {
    $this->pdo = $pdo;
  }

  public function execute()
  {
    $start = microtime(true);

    $args = func_get_args();
    $r = call_user_func_array('parent::execute', $args);

    $this->pdo->RecordTrace(array(
      'start'  => $start,
      'end'    => microtime(true),
      'trace'  => debug_backtrace(),
      'query'  => $this->queryString,
      'params' => $args[0],
    ));
  }
}
