--TEST--
--FILE--
<?php

require_once("../boot.php");

$r1 = new Range(1,10);
$r2 = new Range(2,9);
$r3 = new Range(2,4);

print $r1->intersection($r2)->intersection($r3);

?>
--EXPECT--
2,4
