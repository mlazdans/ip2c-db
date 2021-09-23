<?php

declare(strict_types = 1);

class CountryRange extends Range {
	var string $iso;

	function __construct(string $iso, int $start, int $end, int $merges = 0){
		parent::__construct($start, $end, $merges);
		$this->iso = $iso;
	}

	function __toString() {
		return "$this->iso,".parent::__toString();
	}

	static function cmpCountryStart(CountryRange $r1, CountryRange $r2) {
		// if($r1->deleted || $r2->deleted)
		// 	return 0;
		// else
			return strcmp($r1->iso, $r2->iso);
	}
}
