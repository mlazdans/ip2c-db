<?php

declare(strict_types = 1);

require_once('boot.php');
require_once('console.php');

$datain = $CONFIG['tempin_root'].DIRECTORY_SEPARATOR;
$dataout = $CONFIG['tempout_root'].DIRECTORY_SEPARATOR;

delfiles("$dataout*.db");

$pool = new TPool(32, 'boot.php');

foreach (glob("$datain*.db") as $filename) {
	$iso = pathinfo($filename, PATHINFO_BASENAME);
	$data = [
		'db'=>$iso,
		'datain'=>$filename,
		'dataout'=>"$dataout$iso",
	];

	$pool->submit(function($data){
		# TODO: remove IO
		$processor = new CompactDB($data);

		return $processor->run();
	}, [$data]);
}

$pool->shutdown();
