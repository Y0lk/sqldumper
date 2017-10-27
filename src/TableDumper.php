<?php
namespace Y0lk\SQLDumper;

use PDO;

/**
 * A TableDumper instance is used to dump a single Table, allowing you to specify certain dump options for this table only
 *
 * @author Gabriel Jean <gabriel@inkrebit.com>
 */
class TableDumper {

    /**
     * @var Table   Table instance related to this dumper
     */
    protected $table;

    /**
     * @var boolean Specifies whether to include the table's structure (CREATE statement)
     */
    protected $withStructure = true;

    /**
     * @var boolean Specifies whether to include the table's data (INSERT statements)
     */
    protected $withData = true;

    /**
     * @var boolean Specifies whether to include a DROP TABLE statement
     */
    protected $withDrop = true;

    /**
     * @var string WHERE parameters written as a regular SQL string to select specific data from this table
     */
    protected $where = '';


    /**
     * @param Table Table this dumper will be used for
     */
    public function __construct(Table $table)
    {
        $this->table = $table;
    }

    /**
     * Specifies whether to include the table's structure (CREATE statement)
     * 
     * @param  boolean  $withStructure  TRUE to include structure, FALSE otherwise
     * 
     * @return TableDumper  Returns the TableDumper instance
     */
    public function withStructure($withStructure = true)
    {
        $this->withStructure = $withStructure;
        return $this;
    }

    /**
     * Specifies whether to include the table's data (INSERT statements)
     * 
     * @param  boolean  $withData   TRUE to include data, FALSE otherwise
     * 
     * @return TableDumper  Returns the TableDumper instance
     */
    public function withData($withData = true)
    {
        $this->withData = $withData;
        return $this;
    }

    /**
     * Specifies whether to include a DROP TABLE statement
     * 
     * @param  boolean  $withDrop   TRUE to include DROP statement, FALSE otherwise
     * 
     * @return TableDumper  Returns the TableDumper instance
     */
    public function withDrop($withDrop = true)
    {
        $this->withDrop = $withDrop;
        return $this;
    }

    /**
     * Returns whether or not this dumper will include the table's structure
     * 
     * @return boolean Returns TRUE if it includes it, FALSE otherwise
     */
    public function hasStructure()
    {
        return $this->withStructure;
    }


    /**
     * Returns whether or not this dumper will include the table's data
     * 
     * @return boolean Returns TRUE if it includes it, FALSE otherwise
     */
    public function hasData()
    {
        return $this->withData;
    }


    /**
     * Returns whether or not this dumper will include the table's DROP statement
     * 
     * @return boolean Returns TRUE if it includes it, FALSE otherwise
     */
    public function hasDrop()
    {
        return $this->withDrop;
    }


    /**
     * Add WHERE parameters to use when dumping the data 
     * 
     * @param  string   $where_string   SQL string that you would normally write after WHERE keyowrd
     * 
     * @return TableDumper  Returns the TableDumper instance
     */
    public function where($where_string)
    {
        $this->where = $where_string;
        return $this;
    }

    /**
     * Get the where param string
     * @return string Returns the WHERE param string
     */
    public function getWhere()
    {
        return $this->where;
    }

    /**
     * Get the Table related to this dumper
     * 
     * @return Table    Returns the Table instance
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Writes the CREATE statement to the dump stream
     * 
     * @param  PDO      $db     PDO instance to use for DB queries
     * @param  resource $stream Stream to write the dump to
     * 
     * @return void
     */
    protected function dumpCreateStatement(PDO $db, $stream) 
    {
        $stmt = $db->query('SHOW CREATE TABLE `'.$this->table->getName().'`');

        fwrite($stream, $stmt->fetchColumn(1).";\r\n");

        $stmt->closeCursor();
    }

    /**
     * Writes the DROP statement to the drump stream
     * 
     * @param  resource $stream Stream to write the dump to
     * 
     * @return void
     */
    protected function dumpDropStatement($stream)
    {
        fwrite($stream, 'DROP TABLE IF EXISTS `'.$this->table->getName()."`;\r\n");
    }

    /**
     * Writes the INSERT statements to the drump stream
     * 
     * @param  PDO      $db     PDO instance to use for DB queries
     * @param  resource $stream Stream to write the dump to
     * 
     * @return void
     */
    protected function dumpInsertStatement(PDO $db, $stream)
    {
        //Get data from table
        $select = 'SELECT * FROM '.$this->table->getName();

        if (!empty($this->where)) {
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

            while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
                //Write start of INSERT statement
                if ($j === 0) {
                    //Gets keys from array indexes of first row
                    $listFields = array_keys($row);

                    //Escape them
                    foreach ($listFields as $key => $field) {
                        $listFields[$key] = '`'.$field.'`';
                    }

                    $fields = implode(',', $listFields);
                    
                    fwrite($stream, 'INSERT INTO `'.$this->table->getName().'` ('.$fields.') VALUES ');
                }

                //Write values of this row
                $valuesDump = '';

                if ($j > 0) {
                    $valuesDump .= ", \r\n";
                }

                $listValues = array_values($row);

                //Quote values or replace with NULL if null
                foreach ($listValues as $key => $value) {
                    $quotedValue = str_replace("'", "\'", str_replace('"', '\"', $value));
                    $listValues[$key] = (!isset($value) ? 'NULL' : "'".$quotedValue."'");
                }

                //Add values from this row to valuesDump
                $valuesDump .= '('.implode(',', $listValues).')';

                fwrite($stream, $valuesDump);
                $j++;
            }

            $stmt->closeCursor();
            $i++;

        } while ($j === $i*$limit);

        //If there was at least one row, write end of INSERT statement
        if ($j > 0) {
            fwrite($stream, ";\r\n");
        }
    }

    /**
     * Writes all the SQL statements of this dumper to the dump stream
     * 
     * @param  PDO      $db     PDO instance to use for DB queries
     * @param  resource $stream Stream to write the dump to
     * 
     * @return void
     */
    public function dump(PDO $db, $stream)
    {
        //Drop table statement
        if ($this->withDrop) {
            $this->dumpDropStatement($stream);
        }

        //Create table statement
        if ($this->withStructure) {
            $this->dumpCreateStatement($db, $stream);
        }

        //Data
        if ($this->withData) {
            $this->dumpInsertStatement($db, $stream);
        }
    }
}