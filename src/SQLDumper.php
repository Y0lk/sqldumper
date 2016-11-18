<?php
namespace Y0lk\SQLDumper;

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

        //Init main TableDumperCollection
        $this->listTableDumpers = new TableDumperCollection;
    }

    public function table($tableName)
    {
        return $this->listTableDumpers->table($tableName);
    }

    public function listTables(...$listTableNames)
    {
        //Create TableDumper collection
        $listDumpers = new TableDumperCollection;
        $listDumpers->listTables($listTableNames);

        return $listDumpers;
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
        $listDumpers = call_user_func_array([$this, 'listTables'], $lsitRows);

        return $listDumpers;
    }

    public function save($filename) 
    {

    }
}