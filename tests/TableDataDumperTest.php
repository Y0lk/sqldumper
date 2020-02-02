<?php declare(strict_types=1);
namespace Y0lk\SQLDumper\Test;

use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Y0lk\SQLDumper\Table;
use Y0lk\SQLDumper\TableDataDumper;
use Y0lk\SQLDumper\TableDumper;

class TableDataDumperTest extends TestCase
{
	public function testSelectException()
	{
		$dumper = (new TableDumper(new Table('table1')))->where('invalidquery');
		$dataDumper = new TableDataDumper($dumper);


		$db = new PDO('sqlite::memory:', 
                            null, 
                            null,
                            [
                                PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT
                            ]
        );

		$this->expectException(RuntimeException::class);

		$stream = fopen('php://output', 'w');
		$dataDumper->dump($db, $stream);
		fclose($stream);
	}
}