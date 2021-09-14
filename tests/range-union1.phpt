--TEST--
--FILE--
<?php

require_once("../boot.php");

$r1 = new Range(1,1);
$r2 = new Range(1,1);

$r1->union($r2);
print $r1;

?>
--EXPECT--
1,1
