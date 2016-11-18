<?php
namespace Y0lk\SQLDumper;

class TableDumper {
	protected $db;

	protected $table;

	protected $withStructure = true;
	protected $withData = true;

	protected $dropTable = true;

	public function __construct(PDO $db, Table $table)
	{
		$this->db = $db;
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

	public function dropTable($dropTable = true)
	{
		$this->dropTable = $dropTable;
		return $this;
	}

	public function where($where_string)
	{
		$this->where = $where_string;
	}

	protected function getTable()
	{
		return $this->table;
	}

	protected function getCreateStatement() 
	{
		$statement = $this->db->exec('SHOW CREATE TABLE '.$this->table->getName());
		return $statement."\r\n";
	}

	protected function getDropStatement()
	{
		return 'DROP TABLE IF EXISTS '.$this->db->quote($this->table->getName()). "\r\n";
	}

	public function dump()
	{
		$dump = '';

		//Drop table statement
		if($this->dropTable) {
			$dump .= $this->getDropStatement();
		}

		//Create table statement
		if($this->withStructure) {
			$dump .= $this->getCreateStatement();
		}

		//Data
		
		return $dump;
	}
}