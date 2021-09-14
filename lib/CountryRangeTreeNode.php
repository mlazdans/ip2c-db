<?php

class CountryRangeTreeNode {
	var $min;
	var $max;
	var $key;
	var $left;
	var $right;

	function __construct(&$data, $key){
		$this->key = $key;
		$this->min = $data[$key]->start;
		$this->max = $data[$key]->end;
		$this->left = $this->right = NULL;
	}
}
