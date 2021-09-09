<?php

declare(strict_types = 1);

require_once('boot.php');
require_once('console.php');

$dbs = [
	'ftp://ftp.ripe.net/ripe/dbase/split/ripe.db.inetnum.gz',
	'ftp://ftp.ripe.net/pub/stats/afrinic/delegated-afrinic-latest',
	'ftp://ftp.ripe.net/pub/stats/apnic/delegated-apnic-latest',
	'ftp://ftp.ripe.net/pub/stats/arin/delegated-arin-extended-latest',
	'ftp://ftp.ripe.net/pub/stats/lacnic/delegated-lacnic-latest',
	'ftp://ftp.ripe.net/pub/stats/ripencc/delegated-ripencc-latest',

	'ftp://ftp.arin.net/pub/rr/arin.db.gz',
	'ftp://ftp.afrinic.net:/dbase/afrinic.db.gz',
	'ftp://ftp.apnic.net/public/apnic/whois/apnic.db.inetnum.gz',
];

$pool = new TPool(8, 'boot.php');

$host_db = [];
foreach($dbs as $db){
	$pi = parse_url($db);
	$host = $pi['host'];

	if(!isset($host_db[$host])){
		$host_db[$host] = [];
	}

	$host_db[$host][] = $db;
}

foreach($host_db as $host=>$dbs){
	$pool->submit(function($dbs, $CONFIG){
		foreach($dbs as $db)
			if(!download_db($db, $CONFIG))
				return false;

		return true;
	}, [$dbs, $CONFIG]);
}

$pool->shutdown();

foreach($pool->jobs as $job){
	if(!$job->future->value()){
		exit(1);
	}
}

exit(0);
