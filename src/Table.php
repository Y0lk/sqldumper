<?php
namespace Y0lk\SQLDumper;

class Table {
    protected $name;

    public function __construct($name) 
    {
        $this->name = $name;
    }

    public function getName() 
    {
        return $this->name;
    }
}