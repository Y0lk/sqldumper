<?php declare(strict_types=1);
namespace Y0lk\SQLDumper\Test;

use PHPUnit\Framework\TestCase;
use Y0lk\SQLDumper\SQLDumper;
use Y0lk\SQLDumper\Table;
use Y0lk\SQLDumper\TableDumper;
use Y0lk\SQLDumper\TableDumperCollection;

use PDO;
use RuntimeException;

class SQLDumperTest extends TestCase
{
	protected static $host = 'localhost';
	protected static $dbname = 'test';
	protected static $username = 'root';
	protected static $password = '';

	public static function getSQLDumper() 
	{
		return new SQLDumper(self::$host, self::$dbname, self::$username, self::$password);
	}

	public static function getDB()
	{
		return new PDO('mysql:host='.self::$host.';dbname='.self::$dbname, 
                            self::$username, 
                            self::$password,
                            [
                                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                            ]
        );
	}

	public function testInit()
	{
		$sqlDumper = self::getSQLDumper();
		$this->assertInstanceOf(TableDumperCollection::class, $sqlDumper->getListTableDumpers());
	}

    public function testTable()
    {
    	$sqlDumper = self::getSQLDumper();
    	$dumper = $sqlDumper->table('table_name');

    	$this->assertInstanceOf(TableDumper::class, $dumper);
    	$this->assertEquals($dumper->getTable()->getName(), 'table_name');
    }

    public function testTableMethodChain()
    {
    	$sqlDumper = self::getSQLDumper();
    	$dumper = $sqlDumper->table('table_name')->withStructure(false);

    	$this->assertInstanceOf(TableDumper::class, $dumper);
    	$this->assertFalse($dumper->hasStructure());
    }

    public function testListTables()
    {
    	$sqlDumper = self::getSQLDumper();
    	$listDumpers = $sqlDumper->listTables([
    		'table1',
    		'table2'
    	]);

    	$this->assertInstanceOf(TableDumperCollection::class, $listDumpers);
    	$this->assertEquals($listDumpers['table1']->getTable()->getName(), 'table1');
    	$this->assertEquals($listDumpers['table2']->getTable()->getName(), 'table2');
    }

    public function testListTablesMethodChain()
    {
    	$sqlDumper = self::getSQLDumper();
    	$listDumpers = $sqlDumper->listTables([
    		'table1',
    		'table2'
    	])->withStructure(false);

    	$this->assertInstanceOf(TableDumperCollection::class, $listDumpers);

    	foreach ($listDumpers as $dumper) {
    		$this->assertFalse($dumper->hasStructure());
    	}
    }

    public function testAllTables()
    {
    	$sqlDumper = self::getSQLDumper();
    	$listDumpers = $sqlDumper->allTables();

    	$this->assertInstanceOf(TableDumperCollection::class, $listDumpers);


    	//Get all tables from DB
    	$db = self::getDB();
    	$stmt = $db->prepare('SELECT table_name FROM information_schema.tables WHERE table_schema=:dbname');
        $stmt->bindValue(':dbname', self::$dbname, PDO::PARAM_STR);
        $stmt->execute();

        $listRows = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $stmt->closeCursor();

        //assert all tables from DB
        foreach ($listRows as $row) {
        	$this->assertInstanceOf(TableDumper::class, $listDumpers[$row]);
        	$this->assertEquals($listDumpers[$row]->getTable()->getName(), $row);
        }
    }

    public function testDump()
    {
    	$sqlDumper = self::getSQLDumper();
    	$sqlDumper->table('table1')
    		->withStructure(false)
    		->withData(false)
    		->withDrop(false);

    	$this->expectOutputRegex("/^SET FOREIGN_KEY_CHECKS=0;\s+SET FOREIGN_KEY_CHECKS=1;\s*$/");

    	$stream = fopen('php://output', 'w');
        $sqlDumper->dump($stream);
        fclose($stream);
    }

    public function testDumpStructure()
    {
    	$sqlDumper = self::getSQLDumper();
    	$sqlDumper->table('table1')
    		->withStructure(true)
    		->withData(false)
    		->withDrop(false);

    	$this->expectOutputRegex("/^SET FOREIGN_KEY_CHECKS=0;\s+CREATE TABLE `table1`(.+);\s+SET FOREIGN_KEY_CHECKS=1;\s*$/s");

    	$stream = fopen('php://output', 'w');
        $sqlDumper->dump($stream);
        fclose($stream);
    }

    public function testDumpData()
    {
    	$sqlDumper = self::getSQLDumper();
    	$sqlDumper->table('table1')
    		->withStructure(false)
    		->withData(true)
    		->withDrop(false);

    	$this->expectOutputRegex("/^SET FOREIGN_KEY_CHECKS=0;\s+INSERT INTO `table1`(.+);\s+SET FOREIGN_KEY_CHECKS=1;\s*$/s");

    	$stream = fopen('php://output', 'w');
        $sqlDumper->dump($stream);
        fclose($stream);
    }

    public function testDumpDataWhere()
    {
    	$sqlDumper = self::getSQLDumper();
    	$sqlDumper->table('table1')
    		->withStructure(false)
    		->withData(true)
    		->withDrop(false)
    		->where('1=0'); //Since this is false, there should be no insert

    	$this->expectOutputRegex("/^SET FOREIGN_KEY_CHECKS=0;\s+SET FOREIGN_KEY_CHECKS=1;\s*$/s");

    	$stream = fopen('php://output', 'w');
        $sqlDumper->dump($stream);
        fclose($stream);
    }

    public function testDumpDrop()
    {
    	$sqlDumper = self::getSQLDumper();
    	$sqlDumper->table('table1')
    		->withStructure(false)
    		->withData(false)
    		->withDrop(true);

    	$this->expectOutputRegex("/^SET FOREIGN_KEY_CHECKS=0;\s+DROP TABLE IF EXISTS `table1`;\s+SET FOREIGN_KEY_CHECKS=1;\s*$/");

    	$stream = fopen('php://output', 'w');
        $sqlDumper->dump($stream);
        fclose($stream);
    }

    public function testDumpDefault()
    {
    	$sqlDumper = self::getSQLDumper();
    	$sqlDumper->table('table1');

    	$this->expectOutputRegex("/^SET FOREIGN_KEY_CHECKS=0;\s+DROP TABLE IF EXISTS `table1`;\s+CREATE TABLE `table1`(.+);\s+INSERT INTO `table1`(.+);\s+SET FOREIGN_KEY_CHECKS=1;\s*$/s");

    	$stream = fopen('php://output', 'w');
        $sqlDumper->dump($stream);
        fclose($stream);
    }

    public function testSave()
    {
    	$sqlDumper = self::getSQLDumper();
    	$sqlDumper->table('table1');

    	$sqlDumper->save('test.sql');

    	$this->assertFileExists('test.sql');

    	//Get content of file
    	$content = file_get_contents('test.sql');
    	$this->assertRegExp("/^SET FOREIGN_KEY_CHECKS=0;\s+DROP TABLE IF EXISTS `table1`;\s+CREATE TABLE `table1`(.+);\s+INSERT INTO `table1`(.+);\s+SET FOREIGN_KEY_CHECKS=1;\s*$/s", $content);

    	//Delete file
    	unlink('test.sql');
    }

    public function testSaveException()
    {
        $sqlDumper = self::getSQLDumper();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not read provided file');

        $sqlDumper->save('');
    }

    public function testGroupDrops()
    {
        $sqlDumper = self::getSQLDumper();
        $sqlDumper->allTables();
        $sqlDumper->groupDrops(true);

        $this->expectOutputRegex("/^SET FOREIGN_KEY_CHECKS=0;\s+DROP TABLE IF EXISTS `table1`;\s+DROP TABLE IF EXISTS `table2`;\s+CREATE TABLE `table1`(.+);\s+INSERT INTO `table1`(.+);\s+CREATE TABLE `table2`(.+);\s+SET FOREIGN_KEY_CHECKS=1;\s*$/s");

        $stream = fopen('php://output', 'w');
        $sqlDumper->dump($stream);
        fclose($stream);
    }

    public function testGroupInserts()
    {
        $sqlDumper = self::getSQLDumper();
        $sqlDumper->allTables();
        $sqlDumper->groupInserts(true);

        $this->expectOutputRegex("/^SET FOREIGN_KEY_CHECKS=0;\s+DROP TABLE IF EXISTS `table1`;\s+CREATE TABLE `table1`(.+);\s+DROP TABLE IF EXISTS `table2`;\s+CREATE TABLE `table2`(.+);\s+INSERT INTO `table1`(.+);\s+SET FOREIGN_KEY_CHECKS=1;\s*$/s");

        $stream = fopen('php://output', 'w');
        $sqlDumper->dump($stream);
        fclose($stream);
    }
}