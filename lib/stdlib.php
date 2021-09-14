<?php

declare(strict_types = 1);

function dprint($msg) {
	if(!empty($GLOBALS['debug'])) {
		print $msg;
	}
}

function read_zip($f_name){
	$tmp = tempnam('', 'ip2c');
	if(!($f_out = fopen($tmp, 'w'))){
		print "Cannot create temporary file!";
	}

	if($f_out && ($zip = zip_open($f_name))){
		while($zip_entry = zip_read($zip)){
			if(zip_entry_open($zip, $zip_entry, "r")){
				$buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
				zip_entry_close($zip_entry);
				fputs($f_out, $buf);
			}
		}
		fclose($f_out);
		zip_close($zip);
		return $tmp;
	} else {
		return false;
	}
}

function microtime_float(){
	return microtime(true);
}

function mt(){
	return microtime_float();
}

function mt_start(){
	$GLOBALS['__TS'] = mt();
}

function mt_print(){
	print print_time($GLOBALS['__TS']);
}

function print_time($start_time, $end_time = false){
	if(!$end_time){
		$end_time = mt();
	}
	$seconds = $end_time - $start_time;

	$d = floor($seconds / 86400);
	$seconds -= $d * 86400;

	$h = floor($seconds / 3600);
	$seconds -= $h * 3600;

	$m = floor($seconds / 60);
	$seconds -= $m * 60;

	$s = $seconds;

	$print_time = [];
	if($d)
		$print_time[]= $d."d";

	if($h)
		$print_time[]= $h."h";

	if($m)
		$print_time[]= $m."min";

	$print_time[]= sprintf("%.2f sec", $s);

	return join(" ", $print_time);
}

function print_memory($mem){
	return  number_format($mem / 1024 / 1024, 2, '.', '').'MB';
}

function delfiles($pattern){
	if(substr($pattern, -1) == '*'){
		trigger_error("Refusing pattern: $pattern");
		return false;
	}

	foreach (glob($pattern) as $filename) {
		unlink($filename);
	}
}

function save_processed($key, $data){
	if($f = fopen("$key.processed", "w")){
		foreach($data as $v)
			fputs($f, "$v\n");

		return fclose($f);
	}

	return false;
}

# TODO: logger
function download_db($db, $root){
	$ext = pathinfo($db, PATHINFO_EXTENSION);
	if($ext == 'gz'){
		$dbout = $root.DIRECTORY_SEPARATOR.pathinfo($db, PATHINFO_FILENAME);
		$fopenf = "gzopen";
	} else {
		$dbout = $root.DIRECTORY_SEPARATOR.basename($db);
		$fopenf = "fopen";
	}

	print "Starting downloading: $db...\n";
	if(($f = $fopenf($db, "rb")) === false)
		return false;

	if(($fo = fopen($dbout, "wb")) === false)
		return false;

	while(!feof($f) && (($data = fread($f, 4096)) !== false))
		fwrite($fo, $data);

	return fclose($fo) && fclose($f);
}

# https://stackoverflow.com/a/62248418/10973173
function get_total_cpu_cores() {
	return (int) ((PHP_OS_FAMILY == 'Windows')?(getenv("NUMBER_OF_PROCESSORS")+0):substr_count(file_get_contents("/proc/cpuinfo"),"processor"));
}

function country_rule($c){
	$c = strtoupper(trim($c));
	if($c == 'EU')
		return false;
	elseif($c == 'ZZ')
		return false;
	elseif($c == 'UNITED STATES')
		return 'US';
	else {
		if(strlen($c) != 2)
			trigger_error("Unexpected country ISO code ($c)", E_USER_WARNING);

		return $c;
	}
}

# https://stackoverflow.com/a/5858676/10973173
function cidrToRange($cidr) {
	$range = [];
	$cidr = explode('/', $cidr);

	# Fight 24.152.0/22
	for($i = 0; $i < 4; $i++){
		if($i0 = ip2long($cidr[0])){
			$range[0] = $i0 & ((-1 << (32 - (int)$cidr[1])));
			break;
		}
		$cidr[0] .= '.0';
	}

	$range[1] = $range[0] + pow(2, (32 - (int)$cidr[1])) - 1;

	return $range;
}