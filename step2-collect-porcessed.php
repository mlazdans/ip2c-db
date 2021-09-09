<?php

declare(strict_types = 1);

require_once('boot.php');
require_once('console.php');

$countries = array();
foreach(glob($CONFIG['whoisdata_root'].DIRECTORY_SEPARATOR."*.processed") as $database){
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

$output_dir = $CONFIG['tempin_root'].DIRECTORY_SEPARATOR;
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
