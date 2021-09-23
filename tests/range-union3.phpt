--TEST--
--FILE--
<?php

require_once("../boot.php");

$r1 = new Range(10,20);
$r2 = new Range(5,25);

$r1->union($r2);
print $r1;

?>
--EXPECT--
5,25,0
