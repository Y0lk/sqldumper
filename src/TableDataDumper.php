<?php declare(strict_types=1);
namespace Y0lk\SQLDumper;

use PDO;
use PDOStatement;
use RuntimeException;
use PDOException;

/**
 * A TableDumper instance is used to dump data of single Table
 *
 * @author Gabriel Jean <gabriel@inkrebit.com>
 */
class TableDataDumper {
    /**
     * Amount of rows to process per SELECT statement
     */
    public const CHUNK_SIZE = 1000;

    /**
     * @var TableDumper   TableDumper instance related to this data dumper
     */
    protected $tableDumper;

    /**
     * @param TableDumper TableDumper this data dumper will be based on
     */
    public function __construct(TableDumper $tableDumper)
    {
        $this->tableDumper = $tableDumper;
    }

    /**
     * Prepares the SELECT statement that will be used to get the data
     * @param  PDO    $db       PDO instance to use for DB queries
     * @return PDOStatement     Returns the PDOStatement for to be used for processing chunks
     */
    protected function prepareSelect(PDO $db): PDOStatement
    {
        //Get data from table
        $select = 'SELECT * FROM '.$this->tableDumper->getTable()->getName();

        $where = $this->tableDumper->getWhere();

        if (!empty($where)) {
            $select .= ' WHERE '.$this->tableDumper->getWhere();
        }

        //Add limit
        $select .= ' LIMIT :limit OFFSET :offset';

        $stmt = $db->prepare($select);

        if (!($stmt instanceof PDOStatement)) {
            throw new RuntimeException("Error occured preparing SELECT statement");
        }

        $stmt->bindValue(':limit', self::CHUNK_SIZE, PDO::PARAM_INT);

        return $stmt;
    }

    /**
     * Gets the data for a chunk of rows and writes this part of the statement to the dump stream
     * @param  PDOStatement $stmt       SELECT statement to get data for the chunk
     * @param  resource       $stream   Stream to write to
     * @param  int          $chunkIndex Index of the chunk to process
     * @return int                      Returns the current row index after processing this chunk
     */
    protected function processChunk(PDOStatement $stmt, $stream, int $chunkIndex): int
    {
        $stmt->bindValue(':offset', $chunkIndex*self::CHUNK_SIZE, PDO::PARAM_INT);
        $stmt->execute();

        $rowIndex = $chunkIndex*self::CHUNK_SIZE;

        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            //Write start of INSERT statement
            if ($rowIndex === 0) {
                //Gets keys from array indexes of first row
                fwrite($stream, $this->prepareInsert(array_keys($row)));
            }

            fwrite($stream, $this->prepareRow(array_values($row), $rowIndex === 0));
            $rowIndex++;
        }

        $stmt->closeCursor();
        return $rowIndex;
    }

    /**
     * Prepare the first part of the INSERT statement
     * @param  array  $listCols   List columns to include in that INSERT statement
     * @return string             Returns the first of the INSERT statement
     */
    protected function prepareInsert(array $listCols): string
    {
        //Escape them
        foreach ($listCols as $key => $col) {
            $listCols[$key] = '`'.$col.'`';
        }

        $cols = implode(',', $listCols);
        
        return 'INSERT INTO `'.$this->tableDumper->getTable()->getName().'` ('.$cols.') VALUES ';
    }

    /**
     * Takes a list of values for one row and prepares the string for the INSERT statement
     * @param  array  $listValues List of values for that row
     * @param  bool   $first      TRUE if this is the first row, FALSE otherwise
     * @return string             Return the values for that row as a string for the INSERT statement
     */
    protected function prepareRow(array $listValues, bool $first): string
    {
        //Write values of this row
        $valuesDump = '';

        if (!$first) {
            $valuesDump .= ", \r\n";
        }

        //Quote values or replace with NULL if null
        foreach ($listValues as $key => $value) {
            $quotedValue = str_replace("'", "\'", str_replace('"', '\"', (string) $value));
            $listValues[$key] = (!isset($value) ? 'NULL' : "'".$quotedValue."'");
        }

        //Add values from this row to valuesDump
        $valuesDump .= '('.implode(',', $listValues).')';

        return $valuesDump;
    }

    /**
     * Writes an INSERT statement for the data to the dump stream
     * 
     * @param  PDO      $db     PDO instance to use for DB queries
     * @param  resource $stream Stream to write the statement to
     * 
     * @return void
     */
    public function dump(PDO $db, $stream): void
    {
        $stmt = $this->prepareSelect($db);

        $chunkIndex = 0;

        //Dump an INSERT of all rows with paging 
        do {
            $rowIndex = $this->processChunk($stmt, $stream, $chunkIndex);
            $chunkIndex++;
        } while ($rowIndex === $chunkIndex*self::CHUNK_SIZE);

        //If there was at least one row, write end of INSERT statement
        if ($rowIndex > 0) {
            fwrite($stream, ";\r\n");
        }
    }
}