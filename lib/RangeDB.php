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

	protected function allocate($count) {
		//$this->ranges->allocate($this->ranges->capacity() + $count);
	}

	protected function __addLine($line){
		//list($start, $end) = preg_split('/[,\s]/', trim($line));
		$parts = preg_split('/[,\s]/', trim($line));
		list($start, $end) = $parts;
		$r = new Range($start, $end);
		if(count($parts) > 2){
			$r->merges = $parts[2];
		}
		$this->ranges[] = $r;
	}

	protected function __load($item){
		if($item instanceof Range){
			$this->ranges[] = $item;
		} elseif(is_array($item) || $item instanceof Ds\Sequence){
			$this->loadFromArray($item);
		} else {
			$this->loadFromFile($item);
		}

		return count($this->ranges);
	}

	function save($file) {
		//$this->sort("Start");
		$f = fopen($file, "w");
		foreach($this->ranges as $Range){
			fputs($f, "$Range\n");
		}
		fclose($f);
	}

	function getWithMerges() {
		//$this->sort("Start");
		foreach($this->ranges as $Range){
			yield "$Range,$Range->merges";
			// $data[] = "$Range,$Range->merges";
		}
		// return $data??[];
	}

	function saveWithMerges($file) {
		//$this->sort("Start");
		if(($f = fopen($file, "w")) === false)
			return false;

		foreach($this->getWithMerges() as $line)
			fputs($f, "$line\n");
		// foreach($this->ranges as $Range){
		// 	fputs($f, "$Range,$Range->merges\n");
		// }
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
		# NOTE: 23 will give roughly line count
		$this->allocate((filesize($file) / 23) * 1.5);

		$added = 0;
		$f = fopen($file, "r");
		while($line = fgets($f)) {
			$added++;
			$this->__addLine($line);
		}
		fclose($f);

		return $added;
	}

	protected function loadFromArray($lines) {
		$this->allocate(count($lines));
		# TOD: ->map()
		foreach($lines as $line) {
			$this->__addLine($line);
		}
	}

	function compact() {
		$this->ranges = $this->ranges->filter(function($value){
			return !$value->deleted;
		});
		$this->deleted = 0;
}

	function sort($mode = "Start") {
		if(!count($this->ranges)){
			return;
		}
		$this->ranges->sort(array(get_class($this->ranges[0]), "cmp$mode"));
		//$this->ranges->sort(array("CountryRange", "cmp$mode"));
	}

	function equals($compact = true) {
		$deleted = 0;
		$i = count($this->ranges);

		//print "Match from ($from:$d) at:\n";
		$this->sort("Start");
		for($r = 0; $r < $i - 1; $r++) {
			if($this->ranges[$r]->deleted){
				continue;
			}

			for($t = $r + 1; $t < $i; $t++) {
				if($this->ranges[$t]->deleted){
					continue;
				}

				$r1 = $this->ranges[$r];
				$r2 = $this->ranges[$t];
				if($r1->end < $r2->start){
					break;
				}
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
				} else {
					# Sorted, so no more instersections
					//break;
				}
			}
		}
		if($compact){
			$this->compact();
		}

		//if(!$deleted || ($i < 10000) || ($i / ($i - $this->deleted) > 1.1)){
			//$ts = mt();
			//$c1 = count($this->ranges);
			//print "compact() ";
			//$c2 = count($this->ranges);
			//print " in ".print_time($ts, mt()).", saved($c1-$c2=".($c1-$c2).")\n";
		//}

		//print "Deleted: $deleted\n";

		return $deleted;
	}

	function overlapopen($compact = true) {
		$deleted = 0;
		$i = count($this->ranges);

		//print "Match from ($from:$d) at:\n";
		$this->sort("Start");
		for($r = 0; $r < $i - 1; $r++) {
			if($this->ranges[$r]->deleted){
				continue;
			}

			for($t = $r + 1; $t < $i; $t++) {
				if($this->ranges[$t]->deleted){
					continue;
				}

				$r1 = $this->ranges[$r];
				$r2 = $this->ranges[$t];
				if($r1->end < $r2->start){
					break;
				}
				if(($r1->doOverlap($r2) && !$r1->isWithin($r2) && !$r2->isWithin($r1))){
					printf("\t$r1->iso (%s - %s) ($r1->merges) overlaps $r2->iso (%s - %s) ($r2->merges)\n",
					long2ip($r1->start), long2ip($r1->end),
					long2ip($r2->start), long2ip($r2->end)
				);
/*
				if($r1->merges > $r2->merges){
					$this->ranges[$t]->substract($this->ranges[$r]);
					print ", substracting from r2 $r2->iso\n";
					printf("\t\t$r1->iso (%s - %s) ($r1->merges) - $r2->iso (%s - %s) ($r2->merges)\n",
					long2ip($r1->start), long2ip($r1->end),
					long2ip($r2->start), long2ip($r2->end)
					);
					//$this->ranges[$t]->delete();
					//$this->deleted++;
						//$deleted++;
				} elseif($r2->merges > $r1->merges){
					$this->ranges[$r]->substract($this->ranges[$t]);
					print ", substracting from r1 $r1->iso\n";
					printf("\t\t$r1->iso (%s - %s) ($r1->merges) - $r2->iso (%s - %s) ($r2->merges)\n",
					long2ip($r1->start), long2ip($r1->end),
					long2ip($r2->start), long2ip($r2->end)
					);
					//$this->ranges[$r]->delete();
					//$this->deleted++;
						//$deleted++;
				} else {
					print ", complete equal skipping!!!\n";
				}
					//$this->ranges[$r]->union($this->ranges[$t]);
				//$this->ranges[$r]->delete();
				//$this->ranges[$t]->delete();
				//$this->deleted++;
					//$deleted++;
				*/
				} else {
					# Sorted, so no more instersections
					//break;
				}
			}
		}
		if($compact){
			$this->compact();
		}

		//if(!$deleted || ($i < 10000) || ($i / ($i - $this->deleted) > 1.1)){
			//$ts = mt();
			//$c1 = count($this->ranges);
			//print "compact() ";
			//$c2 = count($this->ranges);
			//print " in ".print_time($ts, mt()).", saved($c1-$c2=".($c1-$c2).")\n";
		//}

		//print "Deleted: $deleted\n";

		return $deleted;
	}

	function overlap($compact = true) {
		$deleted = 0;
		$i = count($this->ranges);

		//print "Match from ($from:$d) at:\n";
		$this->sort("Start");
		for($r = 0; $r < $i - 1; $r++) {
			if($this->ranges[$r]->deleted){
				continue;
			}

			for($t = $r + 1; $t < $i; $t++) {
				if($this->ranges[$t]->deleted){
					continue;
				}

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
		if($compact){
			$this->compact();
		}

		//if(!$deleted || ($i < 10000) || ($i / ($i - $this->deleted) > 1.1)){
			//$ts = mt();
			//$c1 = count($this->ranges);
			//print "compact() ";
			//$c2 = count($this->ranges);
			//print " in ".print_time($ts, mt()).", saved($c1-$c2=".($c1-$c2).")\n";
		//}

		//print "Deleted: $deleted\n";

		return $deleted;
	}
}
