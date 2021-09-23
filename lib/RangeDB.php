<?php

declare(strict_types = 1);

class RangeDB {
	/** @var Range[] $ranges */
	var $ranges = [];
	var $name = '';
	var $merges = 0;

	function __construct(string $name) {
		$this->name = $name;
	}

	function __toString() {
		return $this->name;
	}

	protected function addLine(string $line){
		$parts = preg_split('/[,\s]/', trim($line));
		list($start, $end, $merges) = $parts;

		$r = new Range((int)$start, (int)$end, (int)$merges);

		$this->addRecord($r);
	}

	function addRecord(Range $item){
		$this->ranges[] = $item;
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

	function compact() {
		$this->ranges = array_filter($this->ranges, function(Range $r){
			return !$r->deleted;
		});
	}

	function sort($mode = "Start") {
		if(!count($this->ranges))
			return;

		usort($this->ranges, [$this->ranges[0], "cmp$mode"]);
	}

	function equals($compact = true) {
		$deleted = 0;
		$i = count($this->ranges);

		//print "Match from ($from:$d) at:\n";
		$this->sort("Start");
		for($r = 0; $r < $i - 1; $r++) {
			if($this->ranges[$r]->deleted)
				continue;

			for($t = $r + 1; $t < $i; $t++) {
				if($this->ranges[$t]->deleted)
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
						$this->ranges[$t]->delete();
						$deleted++;
					} elseif($r2->merges > $r1->merges){
						print ", deleting $r1\n";
						$this->ranges[$r]->delete();
						$deleted++;
					} else {
						print ", complete equal skipping!!!\n";
					}
				}
			}
		}

		if($compact)
			$this->compact();

		return $deleted;
	}

	function overlapopen($compact = true) {
		$deleted = 0;
		$i = count($this->ranges);

		//print "Match from ($from:$d) at:\n";
		$this->sort("Start");
		for($r = 0; $r < $i - 1; $r++) {
			if($this->ranges[$r]->deleted)
				continue;

			for($t = $r + 1; $t < $i; $t++) {
				if($this->ranges[$t]->deleted)
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

		if($compact)
			$this->compact();

		return $deleted;
	}

	function overlap($compact = true) {
		$deleted = 0;

		$i = count($this->ranges);

		$this->sort("Start");
		for($r = 0; $r < $i - 1; $r++) {
			if($this->ranges[$r]->deleted)
				continue;

			for($t = $r + 1; $t < $i; $t++) {
				if($this->ranges[$t]->deleted)
					continue;

				if($this->ranges[$r]->doOverlapOrConnect($this->ranges[$t])){
					$this->ranges[$r]->merges += $this->ranges[$t]->merges + 1;
					$this->ranges[$r]->union($this->ranges[$t]);
					$this->ranges[$t]->delete();
					$deleted++;
				} else {
					# Sorted, so no more overlaps
					break;
				}
			}
		}

		if($compact)
			$this->compact();

		return $deleted;
	}
}
