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
use \hoplite\data as data;

class CompoundKeyModel extends data\Model
{
  protected $table = 'test_compound';
  protected $primary_key = array('id_1', 'id_2');
  protected $condition = 'id_1 = :id_1 AND id_2 = :id_2';

  protected $fields = array(
    'id_1',
    'id_2',
    'value'
  );
}

class PrefixTest extends data\Model
{
  protected $table_prefix = 'test_';
  protected $table = 'prefix';

  public function table() { return $this->table; }
}

class ModelTest extends \PHPUnit_Framework_TestCase
{
  public $db;

  public function setUp()
  {
    $this->db = TestModel::SetUpDatabase();
    $this->db->Query("
      CREATE TABLE test_compound
      (
        id_1 integer,
        id_2 integer,
        value text,
        PRIMARY KEY (id_1, id_2)
      );
    ");
    TestModel::set_db($this->db);
    $this->assertSame($this->db, TestModel::db());
    CompoundKeyModel::set_db($this->db);
    $this->assertSame($this->db, CompoundKeyModel::db());
  }

  public function testBadCreate()
  {
    $this->setExpectedException('hoplite\data\ModelException');
    $model = new TestModel(array('id' => 1));
  }

  public function testInsert()
  {
    $model = new TestModel();
    $model->title = 'Hello';
    $model->description = 'A test';
    $model->Insert();
    $this->assertEquals(1, $model->id);
    $model->Insert();
    $this->assertEquals(2, $model->id);
  }

  public function testFetch()
  {
    $this->testInsert();
    $model = new TestModel(1);
    $model->FetchInto();
    $this->assertEquals('Hello', $model->title);
    $this->assertEquals('A test', $model->description);
  }

  public function testFetchCustomCondition()
  {
    $model = new TestModel();
    $model->title = 'test';
    $model->description = 'foobar';
    $model->Insert();

    $model = new TestModel();
    $model->set_condition('title = :title');
    $model->title = 'test';
    $model->FetchInto();
    $this->assertEquals('foobar', $model->description);
  }

  public function testFetchGroup()
  {
    $model = new TestModel();
    $model->title = 'test';
    $model->description = 'foo';
    $model->Insert();

    $model = new TestModel();
    $model->title = 'test';
    $model->description = 'bar';
    $model->Insert();

    $model = new TestModel();
    $model->title = 'foo';
    $model->description = 'test';
    $model->Insert();

    $model = new TestModel();
    $model->title = 'test';
    $model->description = 'baz';
    $model->Insert();

    $results = TestModel::FetchGroup('title = ?', 'test');
    $this->assertEquals(3, count($results));

    $results = TestModel::FetchGroup('title = :title', array('title' => 'test'));
    $this->assertEquals(3, count($results));

    $results = TestModel::FetchGroup();
    $this->assertEquals(4, count($results));
  }

  public function testUpdate()
  {
    $model = new TestModel();
    $model->title = 'Test Update';
    $model->description = 'foobar';
    $model->value = 'alpha';
    $model->Insert();

    $model = new TestModel(1);
    $model->value = 'bravo';
    $model->Update();

    $model = new TestModel(1);
    $model->FetchInto();
    $this->assertEquals('Test Update', $model->title);
    $this->assertEquals('foobar', $model->description);
    $this->assertEquals('bravo', $model->value);
  }

  public function testDelete()
  {
    $this->testInsert();
    $model = new TestModel(1);
    $model->Delete();

    $model = new TestModel(2);
    $model->FetchInto();
    $this->assertEquals('Hello', $model->title);

    $this->setExpectedException('hoplite\data\ModelException');
    $model = new TestModel(1);
    $model->FetchInto();
  }

  public function testCompoundBadCreate()
  {
    $this->setExpectedException('hoplite\data\ModelException');
    $model = new CompoundKeyModel(1);
  }

  public function testCompoundInsert()
  {
    $model = new CompoundKeyModel(array('id_1' => 1, 'id_2' => 2));
    $model->value = 'foo';
    $model->Insert();
  }

  public function testCompoundFetch()
  {
    $this->testCompoundInsert();
    $model = new CompoundKeyModel(array('id_1' => 1, 'id_2' => 2));
    $model->FetchInto();
    $this->assertEquals('foo', $model->value);
  }

  public function testPrefix()
  {
    $test = new PrefixTest();
    $this->assertEquals('test_prefix', $test->table());
  }

  public function testSuccessfulQueryWithTMI()
  {
    $model = new TestModel();
    $model->title = 'Title';
    $model->description = 'Desc';
    $model->value = 'Value';
    $model->Insert();

    $model = new TestModel(1);
    $model->title = 'Title2';
    $model->Update();
    $data = $model->Fetch();
    $this->assertEquals('Title2', $data->title);
  }
}
