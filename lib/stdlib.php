<?php

declare(strict_types = 1);

function dprint($msg) {
	if(!empty($GLOBALS['debug'])) {
		print $msg;
	}
}

function save_file($id, $save_path){
	$some_file = isset($_FILES[$id]) ? $_FILES[$id] : array();

	if(!$some_file){
		return false;
	}

	if(!($f_in = fopen($some_file['tmp_name'], 'r'))){
		print "Cannot open uploaded file!";
		return false;
	}

	if(!($f_out = fopen($save_path, 'w'))){
		print "Cannot save uploaded file!";
		fclose($f_in);
		return false;
	}

	while(!feof($f_in)){
		$ip = trim((string)fgets($f_in));
		$line = ip2long($ip);
		if(($line == '4294967295') || !$ip){
			continue;
		}
		fputs($f_out, "$line\n");
	}
	fclose($f_in);
	fclose($f_out);

	return $some_file['type'];
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
