<?php
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
    public function __construct($name) 
    {
        $this->name = $name;
    }

    /**
     * Get the name of the table
     * 
     * @return string
     */
    public function getName() 
    {
        return $this->name;
    }
}