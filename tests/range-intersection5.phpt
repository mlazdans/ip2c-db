--TEST--
--FILE--
<?php

require_once("../boot.php");

$r1 = new Range(5,5);
$r2 = new Range(1,10);

print $r1->intersection($r2);

?>
--EXPECT--
5,5,0
