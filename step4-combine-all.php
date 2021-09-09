<?php

declare(strict_types = 1);

require_once('boot.php');
require_once('console.php');

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
