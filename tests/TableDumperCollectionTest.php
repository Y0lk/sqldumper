<?php
namespace Y0lk\SQLDumper\Test;

use Y0lk\SQLDumper\Table;
use Y0lk\SQLDumper\TableDumper;
use Y0lk\SQLDumper\TableDumperCollection;

class TableDumperCollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testAddValue()
    {
    	$table = new Table('table_name');
    	$dumper = new TableDumper($table);

    	$listTableDumpers = new TableDumperCollection;
    	$listTableDumpers[] = $dumper;

    	$this->assertSame($listTableDumpers['table_name'], $dumper);
    }

    public function testAddWrongValue()
    {
    	$this->setExpectedException('Exception', 'TableDumperCollection only accepts TableDumper objects');

    	$listTableDumpers = new TableDumperCollection;
    	$listTableDumpers[] = 'test';
    }

    public function testAppendValue()
    {
    	$table = new Table('table_name');
    	$dumper = new TableDumper($table);

    	$listTableDumpers = new TableDumperCollection;
    	$listTableDumpers->append($dumper);

    	$this->assertSame($listTableDumpers['table_name'], $dumper);
    }

    public function testAppendWrongValue()
    {
    	$this->setExpectedException('Exception', 'TableDumperCollection only accepts TableDumper objects');

    	$listTableDumpers = new TableDumperCollection;
    	$listTableDumpers->append('test');
    }

    public function testAddTableString()
    {
    	$listTableDumpers = new TableDumperCollection;
    	$listTableDumpers->addTable('table_name');

    	$this->assertInstanceOf(TableDumper::class, $listTableDumpers['table_name']);
    	$this->assertEquals($listTableDumpers['table_name']->getTable()->getName(), 'table_name');
    }

    public function testAddTableObject()
    {
    	$table = new Table('table_name');

    	$listTableDumpers = new TableDumperCollection;
    	$listTableDumpers->addTable($table);

    	$this->assertInstanceOf(TableDumper::class, $listTableDumpers['table_name']);
    	$this->assertEquals($listTableDumpers['table_name']->getTable()->getName(), 'table_name');
    }

    public function testAddTableWrongValue()
    {
    	$this->setExpectedException('Exception', 'Invalid value supplied for argument \'table\'');

    	$listTableDumpers = new TableDumperCollection;
    	$listTableDumpers->addTable(42);
    }

    public function testAddListTablesString()
    {
    	$inputList = ['table1','table2'];

    	$listTableDumpers = new TableDumperCollection;
    	$listTableDumpers->addListTables($inputList);

    	$this->assertInstanceOf(TableDumper::class, $listTableDumpers['table1']);
    	$this->assertInstanceOf(TableDumper::class, $listTableDumpers['table2']);
    	$this->assertEquals($listTableDumpers['table1']->getTable()->getName(), 'table1');
    	$this->assertEquals($listTableDumpers['table2']->getTable()->getName(), 'table2');
    }

    public function testAddListTablesObject()
    {
    	$inputList = [new Table('table1'), new Table('table2')];

    	$listTableDumpers = new TableDumperCollection;
    	$listTableDumpers->addListTables($inputList);

    	$this->assertInstanceOf(TableDumper::class, $listTableDumpers['table1']);
    	$this->assertInstanceOf(TableDumper::class, $listTableDumpers['table2']);
    	$this->assertEquals($listTableDumpers['table1']->getTable()->getName(), 'table1');
    	$this->assertEquals($listTableDumpers['table2']->getTable()->getName(), 'table2');
    }

    public function testAddListTablesDumperArray()
    {
    	$table1 = new Table('table1');
    	$dumper1 = new TableDumper($table1);
    	$table2 = new Table('table2');
    	$dumper2 = new TableDumper($table2);

    	$inputList = [$dumper1, $dumper2];

    	$listTableDumpers = new TableDumperCollection;
    	$listTableDumpers->addListTables($inputList);

    	$this->assertSame($dumper1, $listTableDumpers['table1']);
    	$this->assertSame($dumper2, $listTableDumpers['table2']);
    }

    public function testAddListTablesDumperCollection()
    {
    	//Create initial collection
    	$listTableDumpers = new TableDumperCollection;

    	$initTable1 = new Table('table1');
    	$initDumper1 = new TableDumper($initTable1);
    	$listTableDumpers[] = $initDumper1;

    	$initTable3 = new Table('table3');
    	$initDumper3 = new TableDumper($initTable3);
    	$listTableDumpers[] = $initDumper3;


    	//Create new collection, table1 in this one should override the one already in the initial collection
    	$table1 = new Table('table1');
    	$dumper1 = new TableDumper($table1);
    	$table2 = new Table('table2');
    	$dumper2 = new TableDumper($table2);

    	$inputCollection = new TableDumperCollection;
    	$inputCollection[] = $dumper1;
    	$inputCollection[] = $dumper2;

    	$listTableDumpers->addListTables($inputCollection);

    	//Should match dumper1 from 2nd collection
    	$this->assertSame($dumper1, $listTableDumpers['table1']);
    	$this->assertSame($dumper2, $listTableDumpers['table2']);
    	$this->assertSame($initDumper3, $listTableDumpers['table3']);
    }

    public function testAddListTablesWrongValue()
    {
    	$this->setExpectedException('Exception', 'Invalid value supplied for argument \'listTables\'');

    	$listTableDumpers = new TableDumperCollection;
    	$listTableDumpers->addListTables('garbage');
    }

    public function testDumperMethodCall()
    {
    	$dumper1 = new TableDumper(new Table('table1'));
    	$dumper2 = new TableDumper(new Table('table2'));

    	$listTableDumpers = new TableDumperCollection;
    	$listTableDumpers->addListTables([$dumper1, $dumper2]);

    	//Call some method from TableDumper
    	$listTableDumpers->withStructure(false);

    	//Assert that all TableDumpers were affected by the method call
    	$this->assertFalse($dumper1->hasStructure());
    	$this->assertFalse($dumper2->hasStructure());

    	//Call some method from TableDumper
    	$listTableDumpers->withStructure(true);

    	//Assert that all TableDumpers were affected by the method call
    	$this->assertTrue($dumper1->hasStructure());
    	$this->assertTrue($dumper2->hasStructure());
    }
}