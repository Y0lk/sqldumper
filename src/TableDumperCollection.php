<?php
namespace Y0lk\SQLDumper;

use ArrayObject;

class TableDumperCollection extends ArrayObject 
{
	public function append($value)
	{
		//Make sure we're adding a TableDumper object
		if(!($value instanceof TableDumper)) {
			throw new Exception("TableDumperCollection only accepts TableDumper objects", 1);
		}

		//Append with table_name as key
		return $this->offsetSet($value->getTable()->getName(), $value);
	}

	public function table($tableName)
    {   
        //First check if a dumper already exists for this table
        if(!isset($this[$tableName])) {
            //Create new one
            $table = new Table($tableName);
            $this[$tableName] = new TableDumper($this->db, $table);
        }

        return $this[$tableName];
    }

    public function listTables($listTableNames)
    {
    	foreach ($listTableNames as $tableName) {
    		$this->table($tableName);
    	}
    }

	public function __call($name, $arguments)
	{
		//Call methods on TableDumper values
		foreach ($this as $value) {
			call_user_func_array($name, $arguments);
		}

		return $this;
	}
}