<?php

declare(strict_types = 1);

class Range {
	const STATUS_ALLOCATED       = 1;
	const STATUS_ASSIGNED        = 2;
	const STATUS_REALLOCATED     = 3;
	const STATUS_REASSIGNED      = 4;

	const SOURCE_INETNUM         = 1;
	const SOURCE_STATS           = 2;

	var int $start;
	var int $end;
	var int $interval;
	var int $merges;
	var int $status;
	var int $source;

	function __construct(int $start, int $end, int $merges, int $source, int $status) {
		$this->start = $start;
		$this->end = $end;
		$this->merges = $merges;
		$this->source = $source;
		$this->status = $status;
		$this->interval = $this->end - $this->start;
	}

	function __toString() {
		return "$this->start,$this->end,$this->merges,$this->source,$this->status";
	}

	function isWithin(Range $r) {
		return ($this->start >= $r->start) && ($this->end <= $r->end);
	}

	function isEqual(Range $r){
		return ($this->start == $r->start) && ($this->end == $r->end);
	}

	function doOverlap(Range $r) {
		return ($this->start <= $r->end) && ($r->start <= $this->end);
	}

	function doOverlapOrConnect(Range $r) {
		return ($this->start <= $r->end + 1) && ($r->start <= $this->end + 1);
	}

	function doConnect(Range $r) {
		return ($this->start == ($r->end + 1)) || ($r->start == ($this->end + 1));
	}

	function intersection(Range $r){
		$nr = clone $r;
		$nr->start = max($this->start, $r->start);
		$nr->end = min($this->end, $r->end);
		$nr->interval = $nr->end - $nr->start;
		return $nr;
	}

	function substract(Range $r){
		if($this->start < $r->start)
			$this->end = $r->start - 1;
		elseif($this->start > $r->start)
			$this->start = $r->end + 1;
	}

	function union(Range $r) {
		$this->start = min($this->start, $r->start);
		$this->end = max($this->end, $r->end);
		$this->interval = $this->end - $this->start;
	}

	static function cmpStartEnd(Range $r1, Range $r2) {
		if ($r1->start == $r2->end)
			return 0;
		else
			return $r1->start < $r2->end ? 1 : -1;
	}

	static function cmpEndStart(Range $r1, Range $r2) {
		if ($r1->end == $r2->start)
			return 0;
		else
			return $r1->end > $r2->start ? 1 : -1;
	}

	static function cmpStart(Range $r1, Range $r2) {
		if ($r1->start == $r2->start)
			return Range::cmpEnd($r1, $r2);
		else
			return $r1->start > $r2->start ? 1 : -1;
	}

	static function cmpStartDesc(Range $r1, Range $r2) {
		return Range::cmpStart($r1, $r2) * -1;
	}

	static function cmpEndAsc(Range $r1, Range $r2) {
		return Range::cmpEnd($r1, $r2) * -1;
	}

	static function cmpInterval(Range $r1, Range $r2) {
		if ($r1->interval == $r2->interval)
			return Range::cmpStart($r1, $r2);
		else
			return $r1->interval < $r2->interval ? 1 : -1;
	}

	static function cmpStartInterval(Range $r1, Range $r2) {
		if ($r1->start == $r2->start)
			return Range::cmpInterval($r1, $r2);
		else
			return Range::cmpStart($r1, $r2);
	}

	static function cmpEnd(Range $r1, Range $r2) {
		if ($r1->end == $r2->end)
			return 0;
		else
			return ($r1->end > $r2->end) ? 1 : -1;
	}

	static function cmpEndDesc(Range $r1, Range $r2) {
		return Range::cmpEnd($r1, $r2) * -1;
	}
}
