<?php

declare(strict_types = 1);

function build_tree(&$data, $floor, $ceil){
	if($floor > $ceil)
		return null;

	$mid = floor(($floor + $ceil) / 2);
	//print "\$floor=$floor; \$ceil=$ceil, mid=$mid\n";
	$root = new CountryRangeTreeNode($data, $mid);
	$root->right = build_tree($data, $mid + 1, $ceil);
	$root->left = build_tree($data, $floor, $mid - 1);

	if(!is_null($root->right) && ($root->right->min < $root->min))
		$root->min = $root->right->min;

	if(!is_null($root->left) && ($root->left->min < $root->min))
		$root->min = $root->left->min;

	if(!is_null($root->right) && ($root->right->max > $root->max))
		$root->max = $root->right->max;

	if(!is_null($root->left) && ($root->left->max > $root->max))
		$root->max = $root->left->max;

	return $root;
}

function search_tree(&$data, $node, $q, $d = 0){
	dprint(str_repeat(" ", $d * 2));
	if($node == null){
		dprint("End of three\n");
		return false;
	}

	$item = &$data[$node->key];
	if(($q < $node->min) || ($q > $node->max)) {
		dprint("Out of range\n");
		return false;
	}

	$found = false;
	if(($q >= $item->start) && ($q <= $item->end)){
		dprint("Found $q in $item($node->key)\n");
		$found = $node->key;
	}

	dprint("Search right\n");
	if(($found_right = search_tree($data, $node->right, $q, $d + 1)) !== false) {
		//print "!! Found-right $q in $found_right\n";
		if($found !== false){
			if($data[$found_right]->interval < $data[$found]->interval){
				$found = $found_right;
			}
		} else {
			$found = $found_right;
		}
	}

	dprint("Search left\n");
	if(($found_left = search_tree($data, $node->left, $q, $d + 1)) !== false) {
		//print "!! Found-left $q in $found_left\n";
		if($found !== false){
			if($data[$found_left]->interval < $data[$found]->interval){
				$found = $found_left;
			}
		} else {
			$found = $found_left;
		}
	}

	return $found;
}

function mid($a1, $a2){
	return round(abs($a2 - $a1) / 2);
}
