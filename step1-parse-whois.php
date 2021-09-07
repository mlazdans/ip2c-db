<?php

# {afrinic,apnic,arin,iana,lacnic,ripencc}
# https://www.ripe.net/manage-ips-and-asns/db/support/documentation/ripe-database-documentation/rpsl-object-types/4-2-descriptions-of-primary-objects/4-2-4-description-of-the-inetnum-object

# TODO: delegated vs inetnum coverage
# TODO: stats for skipped blocks: ProcessDelegatedTask -> skipped ipv4; ProcessWhoisTask -> skipped netnames

require_once('config.php');
require_once('lib/console.inc.php');
require_once('lib/IP2Country/ProcessWhoisTask.php');
require_once('lib/IP2Country/ProcessDelegatedTask.php');

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

$whois_databases = isset($O['w']) ? $O['w'] : array();
$delegated_databases = isset($O['d']) ? $O['d'] : array();
$output_database = isset($O['o']) ? $O['o'] : array();

if(!is_array($whois_databases)){
	$whois_databases = array($whois_databases);
}

if(!is_array($delegated_databases)){
	$delegated_databases = array($delegated_databases);
}

delfiles($CONFIG['whoisdata_root'].DIRECTORY_SEPARATOR."*.processed");

class WhoisDBCollector extends Threaded{
	public function addData($key, $data){
		$f = fopen("$key.processed", "w");
		foreach($data as $v){
			fputs($f, "$v\n");
		}
		fclose($f);
	}
}

print "Start processing WHOIS data\n";
$collector = new WhoisDBCollector();

$pool = new Pool($threads);
foreach($delegated_databases as $database){
	$pool->submit(new ProcessDelegatedTask($database, $collector));
}
foreach($whois_databases as $database){
	$pool->submit(new ProcessWhoisTask($database, $collector));
}
$pool->shutdown();
print "End processing WHOIS data\n";
