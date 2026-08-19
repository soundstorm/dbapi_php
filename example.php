<?php

include("db.php");
$db = new DeutscheBahn();
foreach ($db->getStationByName('Hannover Hbf')[0]->getDepartures(10) as $journey) {
	print($journey->getProductShort()."\t".$journey->getLine()."\t".$journey->getDirection()."\n");
}
