<?php

require_once('RangeDB.php');
require_once('CountryRange.php');

class CountryRangeDB extends RangeDB {

	protected function __addLine($line){
		$parts = preg_split('/[,\s]/', trim($line));
		list($iso, $ip_s, $ip_e) = $parts;
		$r = new CountryRange($iso, $ip_s, $ip_e);
		if(count($parts) > 3){
			$r->merges = $parts[3];
		}
		$this->ranges[] = $r;
		/*
		if(preg_match("/(..),(.*),(.*)/", $line, $m)){
			list($l, $iso, $ip_s, $ip_e) = $m;
			$this->ranges[] = new CountryRange($iso, $ip_s, $ip_e);
		} else {
			trigger_error("Incorrect format ($line)");
		}
		*/
	}

	function copyFrom(RangeDB $db){
		foreach($db->ranges as $Range){
			$r = new CountryRange($db->name, $Range->start, $Range->end);
			$r->merges = $Range->merges;
			$this->append($r);
		}
	}
}
