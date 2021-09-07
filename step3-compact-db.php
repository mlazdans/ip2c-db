<?php

require_once('config.php');
require_once('lib/console.inc.php');
require_once('IP2Country/CompactDBTask.php');
require_once('IP2Country/CountryRangeDB.php');

$datain = $CONFIG['tempin_root'].DIRECTORY_SEPARATOR;
$dataout = $CONFIG['tempout_root'].DIRECTORY_SEPARATOR;
delfiles("$dataout*.db");

$pool = new Pool($threads);

foreach (glob("$datain*.db") as $filename) {
	$iso = pathinfo($filename, PATHINFO_BASENAME);
	$data = array(
		'db'=>$iso,
		'datain'=>$filename,
		'dataout'=>"$dataout$iso",
	);
	$pool->submit(new CompactDBTask($data));
}

$pool->shutdown();
