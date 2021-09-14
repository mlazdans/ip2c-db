--TEST--
--FILE--
<?php

require_once("../boot.php");

$r1 = new Range(10,10);
$r2 = new Range(11,11);

print (int)$r1->doOverlap($r2);

?>
--EXPECT--
0