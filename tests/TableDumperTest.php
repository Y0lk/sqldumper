<?php
namespace Y0lk\SQLDumper\Test;

use Y0lk\SQLDumper\Table;
use Y0lk\SQLDumper\TableDumper;

class TableDumperTest extends \PHPUnit_Framework_TestCase
{
	public function testGetTable()
	{
		$table = new Table('table_name');
		$dumper = new TableDumper($table);

		$this->assertSame($table, $dumper->getTable());
	}

    public function testWithStructre()
    {
    	$dumper = new TableDumper(new Table('table_name'));
    	$dumper->withStructure(true);

    	$this->assertTrue($dumper->hasStructure());
    }

    public function testWithoutStructre()
    {
    	$dumper = new TableDumper(new Table('table_name'));
    	$dumper->withStructure(false);

    	$this->assertFalse($dumper->hasStructure());
    }

    public function testWithData()
    {
    	$dumper = new TableDumper(new Table('table_name'));
    	$dumper->withData(true);

    	$this->assertTrue($dumper->hasData());
    }

    public function testWithoutData()
    {
    	$dumper = new TableDumper(new Table('table_name'));
    	$dumper->withData(false);

    	$this->assertFalse($dumper->hasData());
    }

    public function testWithDrop()
    {
    	$dumper = new TableDumper(new Table('table_name'));
    	$dumper->withDrop(true);

    	$this->assertTrue($dumper->hasDrop());
    }

    public function testWithoutDrop()
    {
    	$dumper = new TableDumper(new Table('table_name'));
    	$dumper->withDrop(false);

    	$this->assertFalse($dumper->hasDrop());
    }

    public function testWhere()
    {
    	$dumper = new TableDumper(new Table('table_name'));
    	$dumper->where('test="foo" AND test2="bar"');

    	$this->assertEquals('test="foo" AND test2="bar"', $dumper->getWhere());
    }
}