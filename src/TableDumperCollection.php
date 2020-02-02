<?php declare(strict_types=1);
namespace Y0lk\SQLDumper;

use ArrayObject;
use PDO;
use InvalidArgumentException;

/**
 * A TableDumperCollection is used to group TableDumper objects together, allowing you to specify dump options on multiple table at once. 
 * All TableDumper methods can be called directly on a TableDumperCollection, and will be executed on all the TableDumper instances in that collection.
 *
 * @author Gabriel Jean <gabriel@inkrebit.com>
 */
class TableDumperCollection extends ArrayObject 
{
    /**
     * {@inheritDoc}
     */
    public function append($value)
    {
        //Make sure we're adding a TableDumper object
        if (!($value instanceof TableDumper)) {
            throw new InvalidArgumentException("TableDumperCollection only accepts TableDumper objects", 1);
        }

        //Append with table_name as key
        return $this->offsetSet($value->getTable()->getName(), $value);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($index, $newval)
    {
        //Make sure we're adding a TableDumper object
        if (!($newval instanceof TableDumper)) {
            throw new InvalidArgumentException("TableDumperCollection only accepts TableDumper objects", 1);
        }

        //Append with table_name as key
        return parent::offsetSet($newval->getTable()->getName(), $newval);
    }


    /**
     * @param Table|string  Adds a table, either by name, or by Table instance, to the collection
     *
     * @return TableDumper Retruns a TableDumper of the table that was just added
     */
    public function addTable($table): TableDumper
    {  
        if ($table instanceof Table) {
            $tableName = $table->getName();
        } elseif (is_string($table)) {
            $tableName = $table;
            $table = new Table($tableName);
        } else {
            throw new InvalidArgumentException("Invalid value supplied for argument 'table'", 1);
        }

        //First check if a dumper already exists for this table
        if (!$this->offsetExists($tableName)) {
            //Create new one 
            $this->offsetSet($tableName, new TableDumper($table));
        }

        return $this->offsetGet($tableName);
    }


    /**
     * @param TableDumperCollection|array<TableDumper|Table|string>    Adds a list of tables, either by passing TableDumperCollection, or an array containing either TableDumper objects, Table objects or table naes
     *
     * @return TableDumperCollection Returns a TableDumperCollection of the list of tables that was just added
     */
    public function addListTables($listTables): TableDumperCollection
    {
        //If arg is a TableDumperCollection, merge into this one
        if ($listTables instanceof TableDumperCollection) {
            foreach ($listTables as $table) {
                $this->append($table);
            }

            return $listTables;
        } elseif (is_array($listTables)) {
            return $this->addListTableArray($listTables);
        }

        throw new \InvalidArgumentException("Invalid value supplied for argument 'listTables'", 1);
    }

    /**
     * Adds a list of tables passed as an array
     * @param array $listTables Array of tables to add
     *
     * @return TableDumperCollection Returns a TableDumperCollection of the list of tables that was just added
     */
    protected function addListTableArray(array $listTables): TableDumperCollection
    {
        //Create TableDumperCollection 
        $listDumpers = new TableDumperCollection;

        foreach ($listTables as $table) {
            //If table is already a Dumper, simply append to this
            if ($table instanceof TableDumper) {
                $listDumpers[] = $table;
                $this->append($table);
            } else {
                $listDumpers[] = $this->addTable($table);
            }
        }

        return $listDumpers;
    }

    /**
     * Writes all DROP statements to the dump stream
     * 
     * @param  resource $stream Stream to write the dump to
     * 
     * @return void
     */
    public function dumpDropStatements($stream): void
    {
        foreach ($this as $dumper) {
            if ($dumper->hasDrop()) {
                $dumper->dumpDropStatement($stream);   
            }
        }
    }

    /**
     * Writes all INSERT statements to the dump stream
     * 
     * @param  PDO      $db     PDO instance to use for DB queries
     * @param  resource $stream Stream to write the dump to
     * 
     * @return void
     */
    public function dumpInsertStatements(PDO $db, $stream): void
    {
        foreach ($this as $dumper) {
            if ($dumper->hasData()) {
                $dumper->dumpInsertStatement($db, $stream);
            }
        }
    }

    /**
     * Writes all the SQL statements of this dumper to the dump stream
     * 
     * @param  PDO      $db             PDO instance to use for DB queries
     * @param  resource $stream         Stream to write the dump to
     * @param  boolean  $groupDrops     Determines if DROP statements will be grouped
     * @param  boolean  $groupInserts   Determines if INSERT statements will be grouped
     * 
     * @return void
     */
    public function dump(PDO $db, $stream, bool $groupDrops = false, bool $groupInserts = false): void
    {   
        if ($groupDrops) {
            $this->dumpDropStatements($stream);
        }

        foreach ($this as $dumper) {
            if (!$groupDrops) {
                $dumper->dumpDropStatement($stream);
            }

            $dumper->dumpCreateStatement($db, $stream);

            if (!$groupInserts) {
                $dumper->dumpInsertStatement($db, $stream);
            }
        }

        if ($groupInserts) {
            $this->dumpInsertStatements($db, $stream);
        }
    }


    public function __call(string $name, array $arguments)
    {
        //Call methods on TableDumper values
        foreach ($this as $value) {
            call_user_func_array([$value, $name], $arguments);
        }

        return $this;
    }
}