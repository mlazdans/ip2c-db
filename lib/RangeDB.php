<?php

declare(strict_types = 1);

class RangeDB {
	var $name = '';
	/** @var Range[] $ranges */
	protected iterable $ranges = [];
	protected int $merges = 0;
	protected int $recCount = 0;

	function __construct(string $name) {
		$this->name = $name;
	}

	function __toString() {
		return $this->name;
	}

	protected function addLine(string $line){
		$parts = preg_split('/[,\s]/', trim($line));
		list($start, $end, $merges, $source, $status) = $parts;

		$r = new Range((int)$start, (int)$end, (int)$merges, (int)$source, (int)$status);

		$this->addRecord($r);
	}

	function getRanges(){
		return $this->ranges;
	}

	function addRecord(Range $item){
		$this->ranges[] = $item;
		$this->recCount++;
	}

	function loadFile(string $file) {
		if(($f = fopen($file, "r")) === false)
			return false;

		while($line = fgets($f))
			$this->addLine($line);

		return fclose($f);
	}

	function loadArray(iterable $lines) {
		foreach($lines as $line)
			$this->addLine($line);
	}

	function save(string $file) {
		if(($f = fopen($file, "w")) === false)
			return false;

		foreach($this->ranges as $Range)
			fputs($f, "$Range\n");

		return fclose($f);
	}

	// function compact() {
	// 	$this->ranges = array_filter($this->ranges, function(Range $r){
	// 		return !$r->deleted;
	// 	});
	// }

	function sort($mode = "Start") {
		usort($this->ranges, ["Range", "cmp$mode"]);
	}

	function equals($compact = true) {
		$deleted = 0;
		$i = $this->recCount;

		$this->sort("Start");
		for($r = 0; $r < $i - 1; $r++) {
			if(!isset($this->ranges[$r]))
				continue;

			for($t = $r + 1; $t < $i; $t++) {
				if(!isset($this->ranges[$t]))
					continue;

				$r1 = $this->ranges[$r];
				$r2 = $this->ranges[$t];
				if($r1->end < $r2->start)
					break;

				if($r1->isEqual($r2)){
					printf("\t$r1->iso.db (%s - %s) equals $r2->iso.db (%s - %s)",
						long2ip($r1->start), long2ip($r1->end),
						long2ip($r2->start), long2ip($r2->end)
					);

					if($r1->merges > $r2->merges){
						print ", deleting $r2\n";
						unset($this->ranges[$t]);
						$deleted++;
					} elseif($r2->merges > $r1->merges){
						print ", deleting $r1\n";
						unset($this->ranges[$r]);
						$deleted++;
						break;
					} else {
						print ", complete equal skipping!!!\n";
					}
				}
			}
		}

		// if($compact)
		// 	$this->compact();

		return $deleted;
	}

	# TODO: remove?
	function overlapopen($compact = true) {
		$deleted = 0;
		$i = $this->recCount;

		$this->sort("Start");
		for($r = 0; $r < $i - 1; $r++) {
			if(!isset($this->ranges[$r]))
				continue;

			for($t = $r + 1; $t < $i; $t++) {
				if(!isset($this->ranges[$t]))
					continue;

				$r1 = $this->ranges[$r];
				$r2 = $this->ranges[$t];

				if($r1->end < $r2->start)
					break;

				if(($r1->doOverlap($r2) && !$r1->isWithin($r2) && !$r2->isWithin($r1)))
					printf("\t$r1->iso (%s - %s) ($r1->merges) overlaps $r2->iso (%s - %s) ($r2->merges)\n",
						long2ip($r1->start), long2ip($r1->end),
						long2ip($r2->start), long2ip($r2->end)
					);
			}
		}

		// if($compact)
		// 	$this->compact();

		return $deleted;
	}

	function overlap($compact = true) {
		$deleted = 0;

		$i = $this->recCount;

		$this->sort("Start");
		for($r = 0; $r < $i - 1; $r++) {
			if(!isset($this->ranges[$r]))
				continue;

			for($t = $r + 1; $t < $i; $t++) {
				if(!isset($this->ranges[$t]))
					continue;

				if($this->ranges[$r]->doOverlapOrConnect($this->ranges[$t])){
					$this->ranges[$r]->merges += $this->ranges[$t]->merges + 1;
					$this->ranges[$r]->union($this->ranges[$t]);
					unset($this->ranges[$t]);
					$deleted++;
				} else {
					# Sorted, so no more overlaps
					break;
				}
			}
		}

		// if($compact)
		// 	$this->compact();

		return $deleted;
	}
}
