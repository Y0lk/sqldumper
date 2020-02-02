<?php declare(strict_types=1);
namespace Y0lk\SQLDumper;

/**
 * A Table object represents a SQL table
 *
 * @author Gabriel Jean <gabriel@inkrebit.com>
 */
class Table {
    /**
     * @var string
     */
    protected $name;

    /**
     * @param string    Table name as a string
     */
    public function __construct(string $name) 
    {
        $this->name = $name;
    }

    /**
     * Get the name of the table
     * 
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}