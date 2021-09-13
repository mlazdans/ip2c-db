<?php

declare(strict_types = 1);

require_once('boot.php');
require_once('console.php');

$O = getopt("p:r:h");

function usage(){
	global $argv;

	print "\nUsage: $argv[0] -p <whoisdata_root> -r <output_folder> [-h]\n";
	print "\n";
	print "\t-p .processed WHOIS / delegated databases folder\n";
	print "\t-r Output folder for ip2country files\n";
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

$output_dir = $OUTPUT_ROOT.DIRECTORY_SEPARATOR;
delfiles("$output_dir*.db");

print "Saving: $output_dir*.db...";
foreach($countries as $iso=>$list){
	$db = "$output_dir$iso.db";
	$f = fopen($db, "w");
	foreach($list as $range){
		fputs($f, "$range\n");
	}
	fclose($f);
}
print "DONE\n";
