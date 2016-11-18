<?php
use Y0lk\SQLDumper\SQLDumper;

$dumper = new SQLDumper('localhost', 'aow_dev', 'root', '');

$dumper->table('address')->withData(false);
$dumper->table('product_designs')->withData(false);
$dumper->table('product_designs')->withData(false);

$dumper->table('users')->where('id=2');

$dumper->save('dump.sql');