<?php
namespace Y0lk\SQLDumper\Test;

use PHPUnit\Framework\TestCase;
use Y0lk\SQLDumper\Table;

class TableTest extends TestCase
{
    public function testTableName() 
    {
    	$table = new Table('table_name');
    	$this->assertEquals($table->getName(), 'table_name');
    }
}