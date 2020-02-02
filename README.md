# SQLDumper

[![Latest Stable Version](https://img.shields.io/packagist/v/y0lk/sqldumper.svg)](https://packagist.org/packages/y0lk/sqldumper)
[![Build Status](https://img.shields.io/travis/Y0lk/sqldumper.svg)](https://travis-ci.org/Y0lk/sqldumper)
[![Code Coverage](https://scrutinizer-ci.com/g/Y0lk/sqldumper/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Y0lk/sqldumper/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Y0lk/sqldumper/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Y0lk/sqldumper/?branch=master)
[![License](https://img.shields.io/packagist/l/y0lk/sqldumper.svg)](https://github.com/y0lk/sqldumper/blob/master/LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/y0lk/sqldumper.svg?maxAge=2592000)](https://packagist.org/packages/y0lk/sqldumper)

SQLDumper is a small library designed to easily create customized SQL dumps.

## Installation

Via Composer

```shell
$ composer require y0lk/sqldumper
```

## Usage
The concept is that you can select tables and set certain "dump parameters" for each table, made so you can chain a bunch of calls to quickly create a customized dump.

```php
use Y0lk\SQLDumper\SQLDumper;

//Init the dumper with your DB info
$dumper = new SQLDumper('localhost', 'dbname', 'root', '');

//Set all tables to dump without data and without DROP statement
$dumper->allTables()
	->withData(false)
	->withDrop(false);

//Set table1 to dump with data
$dumper->table('table1')
	->withData(true);

//Set table2 and table3 to dump without structure (data only), and table3 with where condition
$dumper->listTables([
		'table2',
		'table3'
	])
	->withStructure(false)
	->table('table3')
		->where('id=2 OR foo="bar"');

//This will group DROP statements and put them at the beginning of the dump
$dumper->groupDrops(true);

//This will group INSERT statements and put them at the end of the dump
$dumper->groupInserts(true);

$dumper->save('dump.sql');
```


## Dumper options

### groupDrops(bool $group)
When set to TRUE, this will group DROP statements and put them at the beginning of the dump

### groupInserts(bool $group)
When set to TRUE, this will group INSERT statements and put them at the end of the dump

## Table selection
There are 3 basic methods to select tables. When a table or a list of tables are selected, they are returned as TableDumper objects on which you can set options for the dump.


### allTables()
Selects all the tables in the DB

### table(string|Table $table)
Select by table's name (string) or a Table object

### listTables(array $listTables)
Select by a list of table names (string) or Table object


## Table options
This is the list of methods available on each each TableDumper

### withStructure(bool $withStructure)
Whether to dump the table CREATE structure

### withData(bool $withData)
Whether to dump table data (INSERT statement)

### withDrop(bool $withDrop)
Wheter to include the DROP statement (before CREATE)

### where(string $where_string)
WHERE query string as regular SQL


## Output

### dump(resource $stream)
This the main dump function, outputs the dump to a stream.

### save(string $filename)
Saves the dump to a file

## License

The MIT License (MIT). Please see [License File](https://github.com/Y0lk/sqldumper/blob/master/LICENSE) for more information.