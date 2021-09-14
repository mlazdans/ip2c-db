<?php

declare(strict_types = 1);

class CountryRangeDB extends RangeDB {

	protected function __addLine($line){
		$parts = preg_split('/[,\s]/', trim((string)$line));

		list($iso, $ip_s, $ip_e) = $parts;

		$r = new CountryRange($iso, $ip_s, $ip_e);
		if(count($parts) > 3)
			$r->merges = $parts[3];

		$this->ranges[] = $r;
	}

	function copyFrom(RangeDB $db){
		foreach($db->ranges as $Range){
			$r = new CountryRange($db->name, $Range->start, $Range->end);
			$r->merges = $Range->merges;
			$this->append($r);
		}
	}
}
