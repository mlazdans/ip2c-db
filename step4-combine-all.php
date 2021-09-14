<?php

declare(strict_types = 1);

require_once('boot.php');
require_once('console.php');

$O = getopt("r:o:h");

function usage(){
	global $argv;

	print "\nUsage: $argv[0] -r <ip2country_db_root> -o <combined_db_file> [-h]\n";
	print "\n";
	print "\t-r Root for ip2country files\n";
	print "\t-o File where to save combined database\n";
	print "\t-h help\n";
	print "\n";

	exit(1);
}

if(empty($O['r']) || empty($O['o']) || isset($O['h']))
	usage();

$ROOT = realpath($O['r'].DIRECTORY_SEPARATOR);
$COMBINED = $O['o'];
$COMBINED_ROOT = realpath(pathinfo($O['o'], PATHINFO_DIRNAME));

if(!is_readable($ROOT)){
	print "Not readable: $ROOT\n";
	exit(1);
}

if(!is_writable($COMBINED_ROOT)){
	print "Not writeable: $COMBINED_ROOT\n";
	exit(1);
}

$db_patt = $ROOT.DIRECTORY_SEPARATOR."*.db";

print "Loading: $db_patt...";
$db = new CountryRangeDB("all");
foreach (glob($db_patt) as $filename) {
	$iso = pathinfo($filename, PATHINFO_FILENAME);
	$r = new RangeDB($iso);
	$r->load($filename);
	$db->copyFrom($r);
	unset($r);
}
print "DONE\n";

print "De-duplicate...\n";
while($db->equals());
print "DONE\n";

print "Overlaps:\n";
$db->overlapopen();
print "DONE\n";

print "Sorting...";
$db->sort();
print "DONE\n";

print "Saving $COMBINED...";
$db->save($COMBINED);
print "DONE\n";

exit(0);
