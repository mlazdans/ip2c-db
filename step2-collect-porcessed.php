<?php

declare(strict_types = 1);

require_once('boot.php');
require_once('console.php');

$O = getopt("p:r:t:h");

function usage(){
	global $argv;

	print "\nUsage: $argv[0] -p <whoisdata_root> -r <output_folder> [-h]\n";
	print "\n";
	print "\t-p WHOIS / delegated .processed databases folder\n";
	print "\t-r Output folder for ip2country files\n";
	print "\t-t thread count (>1)\n";
	print "\t-h Help\n";

	exit(1);
}

if(empty($O['r']) || empty($O['p']) || isset($O['h']))
	usage();

$WHOIS_ROOT = realpath($O['p'].DIRECTORY_SEPARATOR);
if(!$WHOIS_ROOT || !is_readable($WHOIS_ROOT)){
	print "WHOIS folder not readable ($WHOIS_ROOT)\n";
	exit(1);
}

$OUTPUT_ROOT = realpath($O['r'].DIRECTORY_SEPARATOR);
if(!$OUTPUT_ROOT || !is_writable($OUTPUT_ROOT)){
	print "Output folder not writable ($OUTPUT_ROOT)\n";
	exit(1);
}

$DATABASES = glob($WHOIS_ROOT.DIRECTORY_SEPARATOR."*.processed");
if(!count($DATABASES)){
	print "No .processed databases found\n";
	exit(1);
}

$THREAD_COUNT = 0;
if(isset($O['t']))
	if(($THREAD_COUNT = (int)$O['t']) < 2)
		$THREAD_COUNT = 0;

$countries = [];
foreach($DATABASES as $database){
	print "Loading: $database...";
	$f = fopen($database, 'r');
	while(!feof($f)){
		if(preg_match("/(..),(.*)/", (string)fgets($f), $m)){
			$countries[$m[1]][] = $m[2];
		}
	}
	fclose($f);
	print "DONE\n";
}

$CompactDB = function($output_dir, $iso, $data){
	print "Start processing ($iso)\n";
	$c = new RangeDB((string)$iso);

	// $logger = new Logger;
	// $logger->logn("Start processing ($iso)");
	$c->load($data);
	while($c->overlap());
	// $logger->logTSn("Done processing ($iso) in ")->resetTS();

	return $c->saveWithMerges("$output_dir$iso.db");
};

$output_dir = $OUTPUT_ROOT.DIRECTORY_SEPARATOR;

delfiles("$output_dir*.db");

if($THREAD_COUNT)
	$pool = new TPool($THREAD_COUNT, 'boot.php');

foreach($countries as $iso=>$ranges)
	if($THREAD_COUNT)
		$pool->submit($CompactDB, [$output_dir, $iso, $ranges]);
	elseif(!$CompactDB($output_dir, $iso, $ranges))
		exit(1);

if($THREAD_COUNT){
	$pool->shutdown();
	foreach($pool->jobs as $job)
		if(!$job->future->value())
			exit(1);
}

exit(0);
