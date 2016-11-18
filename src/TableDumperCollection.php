<?php
namespace Y0lk\SQLDumper;

use ArrayObject;

class TableDumperCollection extends ArrayObject 
{
    public function append($value)
    {
        //Make sure we're adding a TableDumper object
        if(!($value instanceof TableDumper)) {
            throw new \Exception("TableDumperCollection only accepts TableDumper objects", 1);
        }

        //Append with table_name as key
        return $this->offsetSet($value->getTable()->getName(), $value);
    }

    public function addTable($table)
    {  
        if($table instanceof Table) {
            $tableName = $table->getName();
        } elseif(is_string($table)) {
            $tableName = $table;
        } else {
            throw new \Exception("Invalid value supplied for argument 'table'", 1);
        }

        //First check if a dumper already exists for this table
        if(!$this->offsetExists($tableName)) {
            //Create new one
            $table = new Table($tableName);
            $this->offsetSet($tableName, new TableDumper($table));
        }

        return $this->offsetGet($tableName);
    }

    public function addListTables($listTables)
    {
        //If arg is a TableDumperCollection, merge into this one
        if($listTables instanceof TableDumperCollection) {
            foreach ($listTables as $table) {
                $this->append($table);
            }

            return $listTables;
        } 
        elseif(is_array($listTables)) {
            //Create TableDumperCollection 
            $listDumpers = new TableDumperCollection;

            foreach ($listTables as $table) {
                //If table is already a Dumper, simply append to this
                if($table instanceof TableDumper) {
                    $listDumpers[] = $table;
                    $this->append($table);
                } else {
                    $listDumpers[] = $this->addTable($table);
                }
            }

            return $listDumpers;
        }
        else {
            throw new \Exception("Invalid value supplied for argument 'listTables'", 1);
            return NULL;
        }
    }

    public function __call($name, $arguments)
    {
        //Call methods on TableDumper values
        foreach ($this as $value) {
            call_user_func_array([$value, $name], $arguments);
        }

        return $this;
    }
}