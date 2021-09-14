<?php

declare(strict_types = 1);

class RangeDB {
	var $ranges;
	var $deleted = 0;
	var $name = '';

	function __construct(string $name) {
		$this->name = $name;
		$this->ranges = new \Ds\Vector;
	}

	function __toString() {
		return $this->name;
	}

	protected function __addLine($line){
		$parts = preg_split('/[,\s]/', trim((string)$line));
		list($start, $end) = $parts;

		$r = new Range($start, $end);
		if(count($parts) > 2)
			$r->merges = $parts[2];

		$this->ranges[] = $r;
	}

	protected function __load($item){
		if($item instanceof Range)
			$this->ranges[] = $item;
		elseif(is_array($item) || $item instanceof Ds\Sequence)
			$this->loadFromArray($item);
		else
			$this->loadFromFile($item);

		return count($this->ranges);
	}

	function save($file) {
		if(($f = fopen($file, "w")) === false)
			return false;

		foreach($this->ranges as $Range)
			fputs($f, "$Range\n");

		return fclose($f);
	}

	function getWithMerges() {
		foreach($this->ranges as $Range)
			yield "$Range,$Range->merges";
	}

	function saveWithMerges($file) {
		if(($f = fopen($file, "w")) === false)
			return false;

		foreach($this->getWithMerges() as $line)
			fputs($f, "$line\n");

		return fclose($f);
	}

	function append($item) {
		return $this->__load($item);
	}

	function load($item) {
		$this->deleted = 0;
		$this->ranges->clear();

		return $this->__load($item);
	}

	protected function loadFromFile($file) {
		if(($f = fopen($file, "r")) === false)
			return false;

		while($line = fgets($f))
			$this->__addLine($line);

		return fclose($f);
	}

	protected function loadFromArray($lines) {
		foreach($lines as $line)
			$this->__addLine($line);
	}

	function compact() {
		$this->ranges = $this->ranges->filter(function($value){
			return !$value->deleted;
		});
		$this->deleted = 0;
	}

	function sort($mode = "Start") {
		if(!count($this->ranges))
			return;

		$this->ranges->sort(array(get_class($this->ranges[0]), "cmp$mode"));
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
						$this->deleted++;
						$deleted++;
					} elseif($r2->merges > $r1->merges){
						print ", deleting $r1\n";
						$this->ranges[$r]->delete();
						$this->deleted++;
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
					$this->deleted++;
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
