<?php

declare(strict_types = 1);

class CountryRangeDB extends RangeDB {
	protected function addLine(string $line){
		$parts = preg_split('/[,\s]/', trim($line));

		list($iso, $start, $end, $merges, $source, $status) = $parts;

		$r = new CountryRange($iso, (int)$start, (int)$end, (int)$merges, (int)$source, (int)$status);

		$this->addRecord($r);
	}

	function copyFrom(RangeDB $db){
		foreach($db->ranges as $Range){
			$r = new CountryRange($db->name, $Range->start, $Range->end, $Range->merges, $Range->source, $Range->status);
			$this->addRecord($r);
		}
	}
}
