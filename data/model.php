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

namespace hoplite\data;

require_once HOPLITE_ROOT . '/base/struct.php';

/*!
  A Model represents a single instance of a database row and its relations.
  It inherits the strict data member policy from base\Struct and maintains
  a condition by which it fetches and updates data. The role of the Model
  class is not to validate data, but to persist it. Validation is the job
  of the Controller (in phalanx, that is a Task object); the Model will
  persist the data and help access related information.
  
  This class requires the use of a PDO object.
*/
class Model extends \hoplite\base\Struct
{
  /*! @var PDO The object the model will use when performing operations. */
  static protected $db = NULL;

  /*! @var string The string prefix to put in front of the table name. */
  protected $table_prefix = '';

  /*! @var string The name of the database table to which the object belongs. */
  protected $table = 'table';

  /*! @var string The condition to select this data object by. Parameters should
                  be keyed using :keyname syntax.
  */
  protected $condition = 'pkey = :pkey';

  /*! @var string The name of the field(s) that provide the primary key. This
                  can either be a single string or an array for a compound key.

    A word on primary keys: This class assumes that if the primary key is
    singular (defined as a string), then it is auto-increment. The
    implications of this are that the set primary key value is igorned on
    inserts and upates. If you do not want this behavior for a table with a
    truly singluar primary key, define it as a plural/compound primary key
    in the Model (defined as an array).
  */
  protected $primary_key = 'pkey';

  public function condition() { return $this->condition; }
  public function set_condition($cond) { $this->condition = $cond; }

  static public function db() { return self::$db; }
  static public function set_db(\PDO $db) { self::$db = $db; }

  /*!
    Constructor. This takes in either the value(s) to substitute into the
    |$this->condition| or NULL to create a new instance of the model.
  */
  public function __construct($condition_data = NULL)
  {
    if (is_array($condition_data)) {
      if (!is_array($this->primary_key))
        throw new ModelException('Cannot create ' . get_class($this) . ' with an array when primary key is singular.');

      foreach ($condition_data as $key => $value)
        if (in_array($key, $this->primary_key))
          $this->Set($key, $value);
    } else if (!is_null($condition_data)) {
      if (is_array($this->primary_key))
        throw new ModelException('Cannot create ' . get_class($this) . ' when a singular value is given for a compound primary key.');
      $this->Set($this->primary_key, $condition_data);
    }

    $this->table = $this->table_prefix . $this->table;
  }

  /*! Fetches an object and returns the result based on the |$this->condition|. */
  public function Fetch()
  {
    $stmt = self::$db->Prepare("SELECT * FROM {$this->table} WHERE " . $this->condition());
    $stmt->Execute($this->_GetSQLParams($stmt));
    $result = $stmt->FetchObject();
    if (!$result)
      throw new ModelException("Could not fetch " . get_class($this));
    return $result;
  }

  /*!
    Fetches an object and stores the result in the model, overwriting existing
    data values.
  */
  public function FetchInto()
  {
    $this->SetFrom($this->Fetch());
  }

  /*!
    Inserts the new model into the database. This will explicitly filter out
    primary key information if it is a singular key.
  */
  public function Insert()
  {
    $data = $this->ToArray();
    if (!is_array($this->primary_key))
      unset($data[$this->primary_key]);

    $keys = array_keys($data);
    $placeholders = array_map(function ($s) { return ":$s"; }, $keys);
    $stmt = self::$db->Prepare("
      INSERT INTO {$this->table}
        (" . implode(', ', $keys) . ")
      VALUES
        (" . implode(', ', $placeholders) . ")
    ");
    $stmt->Execute($data);

    if (!is_array($this->primary_key))
      $this->Set($this->primary_key, self::$db->LastInsertID());
  }

  /*! Updates the database based on the values set in the model. */
  public function Update()
  {
    $updates   = array_map(function($s) { return "$s = :$s"; }, array_keys($this->ToArray()));
    $condition = $this->condition();
    $stmt = self::$db->Prepare("UPDATE {$this->table} SET " . implode(', ', $updates) . " WHERE $condition");
    $stmt->Execute($this->_GetSQLParams($stmt));
  }

  /*! Deletes a record in the database based on the set condition. */
  public function Delete()
  {
    $stmt = self::$db->Prepare("DELETE FROM {$this->table} WHERE " . $this->condition());
    $stmt->Execute($this->_GetSQLParams($stmt));
  }

  /*!
    Returns a subest of |$this->data| that is required to execute a given
    PDOStatement. This will only work on queries whose parameters are
    specified using :name syntax. It will filter all values that are not
    used in the query.
  */
  protected function _GetSQLParams(\PDOStatement $stmt)
  {
    $matches = array();
    preg_match_all('/\:([a-z0-9_\-]+)/i', $stmt->queryString, $matches);
    $params = array();
    $data   = $this->ToArray();

    foreach ($matches[1] as $key)
      $params[$key] = $data[$key];      

    return $params;
  }

  /*!
    Returns an array of Model objects of the correct type based on a group
    condition. If no condition is specified, returns all results in the table.
    If the |$params| argument is not an array, it will be assumed to be a
    single value and will be converted to an array.
  */
  static public function FetchGroup($condition = '', $params = array())
  {
    $class = get_called_class();  // Late static binding.
    $props = new $class();
    $sql = "SELECT * FROM {$props->table_prefix}{$props->table}";
    if ($condition)
      $sql .= " WHERE $condition";
    $stmt = self::$db->Prepare($sql);

    if (!is_array($params))
      $params = array($params);
    $stmt->Execute($params);

    $results = array();
    while ($obj = $stmt->FetchObject()) {
      $model = new $class();
      $model->SetFrom($obj);
      $results[] = $model;
    }

    return $results;
  }
}

class ModelException extends \Exception {}
