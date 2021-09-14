<?php

declare(strict_types = 1);

require_once('boot.php');
require_once('console.php');

$O = getopt("t:r:h");

function usage(){
	global $argv;

	print "\nUsage: $argv[0] -r <download_directory> [-t <thred_count>] [-h]\n";
	print "\n";
	print "\t-r folder to downloaded databases\n";
	print "\t-t thread count (>1)\n";
	print "\t-h help\n";

	exit(1);
}

# TODO: logger
function download_db($db, $root){
	$ext = pathinfo($db, PATHINFO_EXTENSION);
	if($ext == 'gz'){
		$dbout = $root.DIRECTORY_SEPARATOR.pathinfo($db, PATHINFO_FILENAME);
		$fopenf = "gzopen";
	} else {
		$dbout = $root.DIRECTORY_SEPARATOR.basename($db);
		$fopenf = "fopen";
	}

	print "Starting downloading: $db...\n";

	if($f = $fopenf($db, "rb")){
		if($fo = fopen($dbout, "wb")){
			while(!feof($f)){
				$data = fread($f, 4096);
				fwrite($fo, $data);
			}
			fclose($fo);
		}
		fclose($f);
		return true;
	}

	return false;
}

if(empty($O['r']) || isset($O['h']))
	usage();

$ROOT = $O['r'];

if(!is_writable($ROOT)){
	print "Not writable: $ROOT\n";
	exit(1);
}

$THREAD_COUNT = 0;
if(isset($O['t']))
	if(($THREAD_COUNT = (int)$O['t']) < 2)
		$THREAD_COUNT = 0;

$dbs = [
	'ftp://ftp.ripe.net/pub/stats/afrinic/delegated-afrinic-latest',
	'ftp://ftp.ripe.net/pub/stats/apnic/delegated-apnic-latest',
	'ftp://ftp.ripe.net/pub/stats/arin/delegated-arin-extended-latest',
	'ftp://ftp.ripe.net/pub/stats/lacnic/delegated-lacnic-latest',
	'ftp://ftp.ripe.net/pub/stats/ripencc/delegated-ripencc-latest',

	'ftp://ftp.afrinic.net/dbase/afrinic.db.gz',
	'ftp://ftp.apnic.net/public/apnic/whois/apnic.db.inetnum.gz',
	'ftp://ftp.arin.net/pub/rr/arin.db.gz',
	'ftp://ftp.lacnic.net/lacnic/dbase/lacnic.db.gz',
	'ftp://ftp.ripe.net/ripe/dbase/split/ripe.db.inetnum.gz',
];

$host_db = [];
foreach($dbs as $db){
	$pi = parse_url($db);
	$host = $pi['host'];

	if(!isset($host_db[$host]))
		$host_db[$host] = [];

	$host_db[$host][] = $db;
}

if($THREAD_COUNT)
	$pool = new TPool($THREAD_COUNT, 'boot.php');

$download_host_dbs = function($dbs, $root){
	foreach($dbs as $db)
		if(!download_db($db, $root))
			return false;

	return true;
};

foreach($host_db as $host=>$dbs){
	if($THREAD_COUNT)
		$pool->submit($download_host_dbs, [$dbs, $ROOT]);
	elseif(!$download_host_dbs($dbs, $ROOT))
		exit(1);
}

if($THREAD_COUNT){
	$pool->shutdown();
	foreach($pool->jobs as $job)
		if(!$job->future->value())
			exit(1);
}

exit(0);
