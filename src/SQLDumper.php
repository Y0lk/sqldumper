<?php
namespace Y0lk\SQLDumper;

use PDO;

class SQLDumper
{
    protected $host = '';
    protected $dbname = '';
    protected $username = '';
    protected $password = '';

    protected $db;

    protected $listTableDumpers;

    public function __construct($host, $dbname, $username, $password) 
    {
        $this->host = $host;
        $this->dbname = $dbname;
        $this->username = $username;
        $this->password = $password;

        //Init main TableDumperCollection
        $this->listTableDumpers = new TableDumperCollection;
        $this->connect();
    }

    protected function connect() 
    {
        $this->db = new PDO('mysql:host='.$this->host.';dbname='.$this->dbname, 
                            $this->username, 
                            $this->password,
                            [
                                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                            ]
        );
    }

    public function table($table)
    {
        return $this->listTableDumpers->addTable($table);
    }

    public function listTables($listTables)
    {   
        return $this->listTableDumpers->addListTables($listTables);
    }

    public function allTables()
    {
        //Fetch all table names
        $stmt = $this->db->prepare('SELECT table_name FROM information_schema.tables WHERE table_schema=:dbname');
        $stmt->bindValue(':dbname', $this->dbname, PDO::PARAM_STR);
        $stmt->execute();

        $listRows = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $stmt->closeCursor();

        //Call list tables with returned table names
        $listDumpers = $this->listTables($listRows);

        return $listDumpers;
    }

    public function save($filename) 
    {
        $stream = fopen($filename, 'w');
        $this->dump($stream);
        fclose($stream);
    }

    public function stdout()
    {
        $stream = fopen('php://stdout', 'w');
        $this->dump($stream);
        fclose($stream);
    }

    protected function dump($stream)
    {
        //TODO: Find a way to not use that an instead execute all foreign key constraints at the end
        fwrite($stream, "SET FOREIGN_KEY_CHECKS=0;\r\n");

        foreach ($this->listTableDumpers as $tableDumper) {
            $tableDumper->dump($this->db, $stream);
        }

        fwrite($stream, "SET FOREIGN_KEY_CHECKS=1;\r\n");
    }
}