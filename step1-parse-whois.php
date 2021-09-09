<?php

declare(strict_types = 1);

# {afrinic,apnic,arin,iana,lacnic,ripencc}
# https://www.ripe.net/manage-ips-and-asns/db/support/documentation/ripe-database-documentation/rpsl-object-types/4-2-descriptions-of-primary-objects/4-2-4-description-of-the-inetnum-object

# TODO: delegated vs inetnum coverage
# TODO: stats for skipped blocks: ProcessDelegatedTask -> skipped ipv4; ProcessWhoisTask -> skipped netnames

require_once('boot.php');
require_once('console.php');

$O = getopt("w:d:o:");
if(!isset($O['w']) && !isset($O['d'])){
	print "\nUsage: $argv[0] [-w <whois_database> | -d <delegated_database>]\n";
	print "\n";
	print "\t-w WHOIS database to load, accepts multiple databases.\n";
	print "\t-d delegated database file, accepts multiple databases.\n";
	//print "\t-o output file for all processed databases.\n";
	print "\n";
	print "Example: $argv[0] -w ripe.db.inetnum -d delegated-ripencc-latest -o combined.db\n";
	print "\n";
	print "Processed databases will be saved with .processed added to original name\n";
	exit;
}

$whois_databases = isset($O['w']) ? $O['w'] : [];
$delegated_databases = isset($O['d']) ? $O['d'] : [];
$output_database = isset($O['o']) ? $O['o'] : [];

if(!is_array($whois_databases)){
	$whois_databases = [$whois_databases];
}

if(!is_array($delegated_databases)){
	$delegated_databases = [$delegated_databases];
}

delfiles($CONFIG['whoisdata_root'].DIRECTORY_SEPARATOR."*.processed");

$pool = new TPool(10, 'boot.php');

foreach($delegated_databases as $db){
	$pool->submit(function($db){
		$processor = new ProcessDelegated($db);

		return save_processed($db, $processor->run());
	}, [$db]);
}

foreach($whois_databases as $db){
	$pool->submit(function($db){
		$processor = new ProcessWhois($db);

		return save_processed($db, $processor->run());
	}, [$db]);
}

$pool->shutdown();
