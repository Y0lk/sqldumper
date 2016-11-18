<?php
namespace Y0lk\SQLDumper;

use PDO;

class TableDumper {
    protected $db;

    protected $table;

    protected $withStructure = true;
    protected $withData = true;

    protected $withDrop = true;

    public function __construct(Table $table)
    {
        $this->table = $table;
    }

    public function withStructure($withStructure = true)
    {
        $this->withStructure = $withStructure;
        return $this;
    }

    public function withData($withData = true)
    {
        $this->withData = $withData;
        return $this;
    }

    public function withDrop($withDrop = true)
    {
        $this->withDrop = $withDrop;
        return $this;
    }

    public function where($where_string)
    {
        $this->where = $where_string;
        return $this;
    }

    protected function getTable()
    {
        return $this->table;
    }

    protected function dumpCreateStatement(PDO $db, $stream) 
    {
        $stmt = $db->query('SHOW CREATE TABLE `'.$this->table->getName().'`');

        fwrite($stream, $stmt->fetchColumn(1).";\r\n");

        $stmt->closeCursor();
    }

    protected function dumpDropStatement(PDO $db, $stream)
    {
        fwrite($stream, 'DROP TABLE IF EXISTS `'.$this->table->getName(). "`;\r\n");
    }

    protected function dumpInsertStatement(PDO $db, $stream)
    {
        //Get data from table
        $select = 'SELECT * FROM '.$this->table->getName();

        if(!empty($this->where)) {
            $select .= ' WHERE '.$this->where;
        }

        //Add limit
        $limit = 1000;
        $select .= ' LIMIT :limit OFFSET :offset';

        $stmt = $db->prepare($select);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

        $i = 0;
        $j = 0;

        //Dump an INSERT of all rows with paging 
        do {    
            $stmt->bindValue(':offset', $i*$limit, PDO::PARAM_INT);
            $stmt->execute();

            while(($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
                //Write start of INSERT statement
                if($j === 0) {
                    //Gets keys from array indexes of first row
                    $fields = implode(',', array_keys($row));
                    
                    fwrite($stream, 'INSERT INTO `'.$this->table->getName().'` ('.$fields.') VALUES ');
                }

                //Write values of this row
                $valuesDump = '';

                if($j > 0) {
                    $valuesDump .= ", \r\n";
                }

                $listValues = array_values($row);

                //Quote values or replace with NULL if null
                foreach($listValues as $key => $value) {
                    $quotedValue = str_replace("'", "\'", str_replace('"', '\"', $value));
                    $listValues[$key] = (!isset($value) ? 'NULL' : "'" . $quotedValue."'") ;
                }

                //Add values from this row to valuesDump
                $valuesDump .= '('.implode(',', $listValues).')';

                fwrite($stream, $valuesDump);
                $j++;
            }

            $stmt->closeCursor();
            $i++;

        } while($j === $i*$limit);

        //If there was at least one row, write end of INSERT statement
        if($j > 0) {
            fwrite($stream, ";\r\n");
        }
    }

    public function dump(PDO $db, $stream)
    {
        //Drop table statement
        if($this->withDrop) {
            $this->dumpDropStatement($db, $stream);
        }

        //Create table statement
        if($this->withStructure) {
            $this->dumpCreateStatement($db, $stream);
        }

        //Data
        if($this->withData) {
            $this->dumpInsertStatement($db, $stream);
        }
    }
}