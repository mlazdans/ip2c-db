--TEST--
--FILE--
<?php

require_once("../boot.php");
require_once('stdlib.php');
require_once('tree.php');

function print_item(&$data, $key){
	if($key !== FALSE){
		print $data[$key]."\n";
	} else {
		print "Not found\n";
	}
}

$db = new CountryRangeDB("ALL");
$db->addRecord(new CountryRange('LV', 1,2));
$db->addRecord(new CountryRange('LV', 1,9));
$db->addRecord(new CountryRange('LV', 1,10));
$db->addRecord(new CountryRange('LV', 2,2));
$db->addRecord(new CountryRange('LV', 3,4));
$db->addRecord(new CountryRange('LV', 5,6));
$db->addRecord(new CountryRange('LV', 6,6));
$db->addRecord(new CountryRange('LV', 7,8));

$db->sort("cmpStart");
$data = $db->getRanges();

$root = build_tree($data, 0, count($data) - 1);

print_item($data, search_tree($data, $root, 1));
print_item($data, search_tree($data, $root, 10));
print_item($data, search_tree($data, $root, 100));
print_item($data, search_tree($data, $root, -100));
print_item($data, search_tree($data, $root, 4));
print_item($data, search_tree($data, $root, 9));
print_item($data, search_tree($data, $root, 8));
print_item($data, search_tree($data, $root, 6));
print_item($data, search_tree($data, $root, 2));

?>
--EXPECT--
LV,1,2,0
LV,1,10,0
Not found
Not found
LV,3,4,0
LV,1,9,0
LV,7,8,0
LV,6,6,0
LV,2,2,0
