<?php

include("db.php");
$db = new DeutscheBahn();
foreach ($db->getStationByName('Hannover Hbf')[0]->getDepatures(10) as $journey) {
	print($journey->getProduct()."\t".$journey->getLine()."\t".$journey->getDirection()."\n");
}
