<?php

declare(strict_types = 1);

# {afrinic,apnic,arin,iana,lacnic,ripencc}
# https://www.ripe.net/manage-ips-and-asns/db/support/documentation/ripe-database-documentation/rpsl-object-types/4-2-descriptions-of-primary-objects/4-2-4-description-of-the-inetnum-object

# TODO: delegated vs inetnum coverage
# TODO: stats for skipped blocks: ProcessDelegatedTask -> skipped ipv4; ProcessWhoisTask -> skipped netnames

require_once('boot.php');
require_once('console.php');

$O = getopt("w:d:t:h");

function usage(){
	global $argv;

	print "\nUsage: $argv[0] [-w <whois_database> | -d <delegated_database>] [-t <thred_count>] [-h]\n";
	print "\n";
	print "\t-w WHOIS database to load, accepts multiple databases.\n";
	print "\t-d delegated database file, accepts multiple databases.\n";
	print "\t-t thread count (>1)\n";
	print "\t-h help\n";
	print "\n";
	print "Example: $argv[0] -w ripe.db.inetnum -d delegated-ripencc-latest -o combined.db\n";
	print "\n";
	print "Processed databases will be saved with .processed added to original name\n";

	exit(1);
}

if((empty($O['w']) && empty($O['d'])) || isset($O['h']))
	usage();

$THREAD_COUNT = 0;
if(isset($O['t']))
	if(($THREAD_COUNT = (int)$O['t']) < 2)
		$THREAD_COUNT = 0;

$whois_databases = isset($O['w']) ? $O['w'] : [];
$delegated_databases = isset($O['d']) ? $O['d'] : [];
$output_database = isset($O['o']) ? $O['o'] : [];

if(!is_array($whois_databases)){
	$whois_databases = [$whois_databases];
}

if(!is_array($delegated_databases)){
	$delegated_databases = [$delegated_databases];
}

# $type = [delegated|whois]
$process_db = function(string $db, string $type){
	if($type == 'delegated'){
		$processor = new ProcessDelegated($db);
	} elseif($type == 'whois'){
		$processor = new ProcessWhois($db);
	} else {
		return false;
	}

	return save_processed($db, $processor->run());
};

if($THREAD_COUNT)
	$pool = new TPool($THREAD_COUNT, 'boot.php');

foreach($delegated_databases as $db){
	if($THREAD_COUNT)
		$pool->submit($process_db, [$db, 'delegated']);
	elseif(!$process_db($db, 'delegated'))
		exit(1);
}

foreach($whois_databases as $db){
	if($THREAD_COUNT)
		$pool->submit($process_db, [$db, 'whois']);
	elseif(!$process_db($db, 'whois'))
		exit(1);
}

if($THREAD_COUNT){
	$pool->shutdown();
	foreach($pool->jobs as $job)
		if(!$job->future->value())
			exit(1);
}

exit(0);
