<?php

declare(strict_types = 1);

class CountryRange extends Range {
	var string $iso;

	function __construct(string $iso, int $start, int $end, int $merges, int $source, int $status){
		parent::__construct($start, $end, $merges, $source, $status);
		$this->iso = $iso;
	}

	function __toString() {
		return "$this->iso,".parent::__toString();
	}

	static function cmpCountryStart(CountryRange $r1, CountryRange $r2) {
		return strcmp($r1->iso, $r2->iso);
	}
}
