<?php

declare(strict_types = 1);

require_once('boot.php');
require_once('console.php');
require_once('lib/tree.php');

define('IP2C_DB_IDENT', 'IP2C');
define('IP2C_DB_VERS_HI', 2);
define('IP2C_DB_VERS_LO', 1);

$db = new CountryRangeDB("ALL");
$db->loadFile("combined.db");

$root = build_tree($db->ranges, 0, count($db->ranges) - 1);

$rec_count = count($db->ranges);
$ip_count = $root->max - $root->min + 2; // 2=including range ends

$f = fopen('ip2c2-'.date('Ymd').'.db', 'wb');
$format = "Z".(strlen(IP2C_DB_IDENT) + 1)."ccVV"; # unsigned long (always 32 bit, little endian byte order)

fwrite($f, pack($format,
	IP2C_DB_IDENT,
	IP2C_DB_VERS_HI,
	IP2C_DB_VERS_LO,
	$rec_count,
	$ip_count
));
foreach($db->ranges as $r){
	fwrite($f, pack("VVa4", $r->start, $r->end, $r->iso)); # NOTE: a4 to align C struct
}
fclose($f);

printf("Database version: %s %d.%d, %d records, %d IPs covered",
	IP2C_DB_IDENT,
	IP2C_DB_VERS_HI,
	IP2C_DB_VERS_LO,
	$rec_count,
	$ip_count
);
