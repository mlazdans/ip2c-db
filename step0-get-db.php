<?php

require_once('config.php');
require_once('lib/console.inc.php');

$dbs = array(
	'ftp://ftp.ripe.net/ripe/dbase/split/ripe.db.inetnum.gz',
	'ftp://ftp.ripe.net/pub/stats/afrinic/delegated-afrinic-latest',
	'ftp://ftp.ripe.net/pub/stats/apnic/delegated-apnic-latest',
	'ftp://ftp.ripe.net/pub/stats/arin/delegated-arin-extended-latest',
	'ftp://ftp.ripe.net/pub/stats/lacnic/delegated-lacnic-latest',
	'ftp://ftp.ripe.net/pub/stats/ripencc/delegated-ripencc-latest',

	'ftp://ftp.arin.net/pub/rr/arin.db.gz',
	'ftp://ftp.afrinic.net:/dbase/afrinic.db.gz',
	'ftp://ftp.apnic.net/public/apnic/whois/apnic.db.inetnum.gz',
);

$allComplete = true;
foreach($dbs as $db){
	$ext = pathinfo($db, PATHINFO_EXTENSION);
	if($ext == 'gz'){
		$dbout = $CONFIG['whoisdata_root'].DIRECTORY_SEPARATOR.pathinfo($db, PATHINFO_FILENAME);
		$fopenf = "gzopen";
	} else {
		$dbout = $CONFIG['whoisdata_root'].DIRECTORY_SEPARATOR.basename($db);
		$fopenf = "fopen";
	}

/*
	if(file_exists($dbout)){
		print "File exists: ($dbout), skipping\n";
		continue;
	}
*/

	print "Downloading: ($db) to ($dbout)...";
	if($f = $fopenf($db, "rb")){
		if($fo = fopen($dbout, "wb")){
			while(!feof($f)){
				$data = fread($f, 4096);
				fwrite($fo, $data);
			}
			fclose($fo);
		}
		fclose($f);
		print "done\n";
	} else {
		$allComplete = false;
		print "FAIL\n";
	}
}

if($allComplete){
	exit(0);
} else {
	exit(1);
}
