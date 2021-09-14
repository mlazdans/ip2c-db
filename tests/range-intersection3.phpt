--TEST--
--FILE--
<?php

require_once("../boot.php");

$r1 = new Range(1,10);
$r2 = new Range(2,9);

print $r1->intersection($r2);

?>
--EXPECT--
2,9
