<?php declare(strict_types=1);
namespace Y0lk\SQLDumper\Test;

use PHPUnit\Framework\TestCase;
use Y0lk\SQLDumper\Table;
use Y0lk\SQLDumper\TableDumper;

class TableDumperTest extends TestCase
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
    	$return = $dumper->withStructure(true);

        $this->assertSame($return, $dumper);
    	$this->assertTrue($dumper->hasStructure());
    }

    public function testWithoutStructre()
    {
    	$dumper = new TableDumper(new Table('table_name'));
    	$return = $dumper->withStructure(false);

        $this->assertSame($return, $dumper);
    	$this->assertFalse($dumper->hasStructure());
    }

    public function testWithData()
    {
    	$dumper = new TableDumper(new Table('table_name'));
    	$return = $dumper->withData(true);

        $this->assertSame($return, $dumper);
    	$this->assertTrue($dumper->hasData());
    }

    public function testWithoutData()
    {
    	$dumper = new TableDumper(new Table('table_name'));
    	$return = $dumper->withData(false);

        $this->assertSame($return, $dumper);
    	$this->assertFalse($dumper->hasData());
    }

    public function testWithDrop()
    {
    	$dumper = new TableDumper(new Table('table_name'));
    	$return = $dumper->withDrop(true);

        $this->assertSame($return, $dumper);
    	$this->assertTrue($dumper->hasDrop());
    }

    public function testWithoutDrop()
    {
    	$dumper = new TableDumper(new Table('table_name'));
    	$return = $dumper->withDrop(false);

        $this->assertSame($return, $dumper);
    	$this->assertFalse($dumper->hasDrop());
    }

    public function testWhere()
    {
    	$dumper = new TableDumper(new Table('table_name'));
    	$return = $dumper->where('test="foo" AND test2="bar"');

        $this->assertSame($return, $dumper);
    	$this->assertEquals('test="foo" AND test2="bar"', $dumper->getWhere());
    }

    public function testDump()
    {
        $dumper = new TableDumper(new Table('table1'));

        $this->expectOutputRegex("/^DROP TABLE IF EXISTS `table1`;\s+CREATE TABLE `table1`(.+);\s+INSERT INTO `table1`(.+);\s*$/s");

        $stream = fopen('php://output', 'w');
        $dumper->dump(SQLDumperTest::getDB(), $stream);
        fclose($stream);
    }
}