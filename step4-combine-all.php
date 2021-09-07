<?php

require_once('config.php');
require_once('lib/console.inc.php');
require_once('IP2Country/RangeDB.php');
require_once('IP2Country/CountryRangeDB.php');

$db_patt = $CONFIG['tempout_root'].DIRECTORY_SEPARATOR."*.db";

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

print "Saving all.db...";
$db->save("combined.db");
print "DONE\n";
