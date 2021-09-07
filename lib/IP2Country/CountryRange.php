<?php

require_once('Range.php');

class CountryRange extends Range {
	var $iso;

	function __construct($iso, $start, $end){
		parent::__construct($start, $end);
		$this->iso = $iso;
	}

	function __toString() {
		return "$this->iso,$this->start,$this->end";
	}

	static function cmpCountryStart(CountryRange $r1, CountryRange $r2) {
		if($r1->deleted || $r2->deleted)
			return 0;
		else
			return strcmp($r1->iso, $r2->iso);
		// if ($r1->iso == $r2->iso) {
		// 		return 0;
		// }
		// return ($r1->iso < $r2->iso) ? 1 : -1;
	}
}
