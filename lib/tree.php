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

function build_tree(&$data, $floor, $ceil){
  if($floor > $ceil){
    return null;
  }

  $mid = (int)(floor($floor + $ceil) / 2);
  //print "\$floor=$floor; \$ceil=$ceil, mid=$mid\n";
  $root = new CountryRangeTreeNode($data, $mid);
  $root->right = build_tree($data, $mid + 1, $ceil);
  $root->left = build_tree($data, $floor, $mid - 1);

  if(!is_null($root->right) && ($root->right->min < $root->min)) {
    $root->min = $root->right->min;
  }
  if(!is_null($root->left) && ($root->left->min < $root->min)){
    $root->min = $root->left->min;
  }

  if(!is_null($root->right) && ($root->right->max > $root->max)) {
    $root->max = $root->right->max;
  }
  if(!is_null($root->left) && ($root->left->max > $root->max)){
    $root->max = $root->left->max;
  }

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

// mx = max x
function draw_tree($i, $node, $x = 0, $dir = 0, $d = 0){
  //print str_repeat(" ", $d * 2);

  $W = imagesx($i);
  $H = imagesy($i);

  $maxd = 7;
  //$xstep = 10;
  $ystep = $H / ($maxd + 2);
  //$koef = 1+1/$maxd;
  $koef = 1.01;
  //print "$koef\n";
  $cr = 15;

  // Circle radius and color
  $cc = imagecolorallocate($i, 0x88, 0xFF, 0x88 + $dir * 0x88);
  $w = imagecolorallocate($i, 0xFF,0xFF,0xFF);

  //$cx = $x / 2;
  //$l = log($d+2);
  //$cx = $startx + ($x / 7) * $dir;
  $cx = round($x);
  //$cx = $W / 2 + $xstep * $l * $dir * $d;
  //$l = $W/2 + ($W/2) * log10($d);
  $cy = round($ystep * $d);
  if(!$node || ($d > $maxd)){
    $t = "null";
    if($d > $maxd)
      $t = "depth";
    $tx = $cx - ((strlen($t) - 1) * imagefontwidth(1)) / 2;
    $ty = $cy + $cr;
    imagestring($i, 1, $tx, $ty, $t, $w);
    //print "End of branch\n";
    return;
  }
  $t = "$dir:$d:$node->min-$node->max";
  //print "cx=$cx; cy=$cy; t=$t\n";
  //bool imageellipse($image , int $cx , int $cy , int $width , int $height , int $color )
  imagefilledellipse($i, $cx, $cy, $cr, $cr, $cc);

  $cxl = $cx - $cx/($koef*($d+1));
  $cxr = $cx + $cx/($koef*($d+1));
  //print "$cxl:$cxr\n";
  imageline($i, $cx, $cy, $cxl, $cy + $ystep, $cc);
  imageline($i, $cx, $cy, $cxr, $cy + $ystep, $cc);

  $t = "cx=$cx cy=$cy";
  $tx = $cx - ((strlen($t) - 1) * imagefontwidth(1)) / 2;
  $ty = $cy + $cr;
  imagestring($i, 1, $tx, $ty, $t, $w);

  draw_tree($i, $node->left, $cxl, -1, $d + 1);
  draw_tree($i, $node->right, $cxr, +1, $d + 1);
  //bool imageline ( resource $image , int $x1 , int $y1 , int $x2 , int $y2 , int $color )
  //imageline($i, mid($x - $cr + $d * $step, $W), $y, mid($x - $cr + $step + $d * $step, $W), $y + $step, $lc);
  //draw_tree($i, $node->right, $x + $step, $y + $step, $d + 1);
  //draw_tree($i, $node->left, $x - $step, $y + $step + 1);
}

function printtr(&$data, $root){
  print '
  <style>
  table td {
    text-align: center;
    vertical-align: top;
    white-space: nowrap;
  }
  .left {
    text-align: right;
    background-color: #ccffcc;
  }
  .right {
    background-color: #ccccff;
  }
  </style>
  ';
  print printnode($data, $root);
}

function printnode(&$data, $node, $d = 0){
  if($node == null){
    return "null";
  }
  if($d > 5){
   return "-limit-";
  }

  $item = &$data[$node->key];

  $s = long2ip($item->start);
  $e = long2ip($item->end);
  $c = $item->iso;
  $i = $item->end - $item->start;

  $min = long2ip($node->min);
  $max = long2ip($node->max);
  $count = $node->max - $node->min;

  $left = printnode($data, $node->left, $d + 1);
  $right = printnode($data, $node->right, $d + 1);

  $html = "
  <table border=1>
  <tr>
    <td colspan=\"2\">
      [$node->key]<br>
      [($min-$max),$count]<br>
      [$c($s-$e),$i]
    </td>
  </tr>
  <tr>
    <td class=\"left\" width=\"50%\">$left</td>
    <td class=\"right\" width=\"50%\">$right</td>
  </tr>
  </table>
  ";

  return $html;
}
