<?php

include("db.php");
try {
	$db = new DeutscheBahn();
	foreach ($db->getStationByName('Hannover Hbf')[0]->getDepartures(10) as $journey) {
		print($journey->getProductShort()."\t".$journey->getLine()."\t".$journey->getDirection()."\n");
	}
} catch (\Throwable $e) {
	fwrite(STDERR, "Auf Fahrziel der Züge achten (".$e->getMessage().")\n");
	exit(1);
}
