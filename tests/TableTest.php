<?php
namespace Y0lk\SQLDumper\Test;

use Y0lk\SQLDumper\Table;

class TableTest extends \PHPUnit_Framework_TestCase
{
    public function testTableName() 
    {
    	$table = new Table('table_name');
    	$this->assertEquals($table->getName(), 'table_name');
    }
}