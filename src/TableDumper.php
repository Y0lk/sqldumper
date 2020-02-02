<?php declare(strict_types=1);
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
    public function withStructure(bool $withStructure = true): TableDumper
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
    public function withData(bool $withData = true): TableDumper
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
    public function withDrop(bool $withDrop = true): TableDumper
    {
        $this->withDrop = $withDrop;
        return $this;
    }

    /**
     * Returns whether or not this dumper will include the table's structure
     * 
     * @return boolean Returns TRUE if it includes it, FALSE otherwise
     */
    public function hasStructure(): bool
    {
        return $this->withStructure;
    }


    /**
     * Returns whether or not this dumper will include the table's data
     * 
     * @return boolean Returns TRUE if it includes it, FALSE otherwise
     */
    public function hasData(): bool
    {
        return $this->withData;
    }


    /**
     * Returns whether or not this dumper will include the table's DROP statement
     * 
     * @return boolean Returns TRUE if it includes it, FALSE otherwise
     */
    public function hasDrop(): bool
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
    public function where(string $where_string): TableDumper
    {
        $this->where = $where_string;
        return $this;
    }

    /**
     * Get the where param string
     * @return string Returns the WHERE param string
     */
    public function getWhere(): string
    {
        return $this->where;
    }

    /**
     * Get the Table related to this dumper
     * 
     * @return Table    Returns the Table instance
     */
    public function getTable(): Table
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
    public function dumpCreateStatement(PDO $db, $stream): void 
    {
        if (!$this->hasStructure()) return;

        $stmt = $db->query('SHOW CREATE TABLE `'.$this->table->getName().'`');

        fwrite($stream, $stmt->fetchColumn(1).";\r\n");

        $stmt->closeCursor();
    }

    /**
     * Writes the DROP statement to the dump stream
     * 
     * @param  resource $stream Stream to write the dump to
     * 
     * @return void
     */
    public function dumpDropStatement($stream): void
    {
        if (!$this->hasDrop()) return;

        fwrite($stream, 'DROP TABLE IF EXISTS `'.$this->table->getName()."`;\r\n");
    }

    /**
     * Writes the INSERT statements to the dump stream
     * 
     * @param  PDO      $db     PDO instance to use for DB queries
     * @param  resource $stream Stream to write the dump to
     * 
     * @return void
     */
    public function dumpInsertStatement(PDO $db, $stream): void
    {
        if (!$this->hasData()) return;

        $dataDumper = new TableDataDumper($this);
        $dataDumper->dump($db, $stream);
    }

    /**
     * Writes all the SQL statements of this dumper to the dump stream
     * 
     * @param  PDO      $db     PDO instance to use for DB queries
     * @param  resource $stream Stream to write the dump to
     * 
     * @return void
     */
    public function dump(PDO $db, $stream): void
    {
        //Drop table statement
        $this->dumpDropStatement($stream);

        //Create table statement
        $this->dumpCreateStatement($db, $stream);

        //Data
        $this->dumpInsertStatement($db, $stream);
    }
}