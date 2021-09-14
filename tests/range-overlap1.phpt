--TEST--
--FILE--
<?php

require_once("../boot.php");

$r1 = new Range(1,1);
$r2 = new Range(1,1);

print (int)$r1->doOverlap($r2);

?>
--EXPECT--
1