--TEST--
--FILE--
<?php

require_once("../boot.php");

$r1 = new Range(1,2);
$r2 = new Range(1,1);

print $r1->intersection($r2);

?>
--EXPECT--
1,1
