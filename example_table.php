<?php
if (isset($_GET["station"])) {
	$station = $_GET["station"];
} else {
	$station = "Hannover Hbf";
}
include("db.php");
$numEntries = 12;

$error = false;
try {
	$db = new DeutscheBahn();
	$stations = $db->getStationByName($station);
	if (empty($stations)) {
		throw new DeutscheBahnApiException("Station '$station' nicht gefunden");
	}
	$station = $stations[0];
	$departures = $station->getDepartures($numEntries);
} catch (\Throwable $e) {
	$error = true;
}
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>Abfahrtstafel <?=$error ? "" : $station->getName()?></title>
	<meta http-equiv="refresh" content="60">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
	<style>
		body {
			background: #000080;
			color: #FFF;
			font-family: "Arial";
			font-size: 18pt;
		}
		#header {
			font-size: 24pt;
		}
		#time {
			display: inline-block;
		}
		#name {
			display: inline-block;
			text-align: center;
			margin: auto;
		}
		.top {
			background: #FFFF00;
			color: #000;
		}
		#departures {
			display: table;
			width: 100%;
		}
		.top, .dep {
			display: table-row;
		}
		.dep:nth-child(odd) {
			background: #0000A0;
		}
		.time, .dest, .plat {
			display: table-cell;
		}
		.time .sched, .time .prod {
			display: inline-block;
		}
		.time .del, .time .eta {
			display: inline-block;
			float: right;
			padding-right: 20px;
		}
		.time {
			width: 210px;
		}
		.dest.cancel .name {
			text-decoration: line-through;
		}
		.plat.newPl, .time .eta, .delayCause {
			color: #FFFF00;
		}
		#errorMsg {
			text-align: center;
			margin-top: 40px;
		}
	</style>
</head>
<body>
<div id="header">
	<div id="time"><?php $tm = localtime();printf("%02d:%02d", $tm[2], $tm[1]);?></div>
	<div id="name"><?=$error ? "" : $station->getName()?></div>
</div>
<?php if ($error): ?>
<div id="errorMsg">Auf Fahrziel der Züge achten</div>
<?php else: ?>
<div id="departures">
	<div class="top">
		<div class="time">Zeit</div>
		<div class="dest">Nach</div>
		<div class="plat">Gleis</div>
	</div>
<?php
foreach ($departures as $dep) {?>
	<div class="dep">
		<div class="time">
			<div class="firstrow">
				<div class="sched"><?=$dep->getTime()->format("H:i")?></div>
				<div class="del"><?=$dep->isCancelled()?"":($dep->getDelayNum()?$dep->getDelayNum():"")?></div>
			</div>
				<div class="prod"><?=$dep->getProductShort()?> <?=$dep->getLine()?></div>
				<div class="eta"><?=$dep->isCancelled() || !$dep->getDelayNum()?"":$dep->getRealTime()->format("H:i")?></div>
		</div>
		<div class="dest<?=$dep->isCancelled()?" cancel":""?>">
			<div class="name"><?=$dep->getDirection()?></div>
			<div class="delayCause"><?=count($dep->getNotes()) && isset($dep->getNotes()[0]->text)?$dep->getNotes()[0]->text:""?></div>
		</div>
		<div class="plat<?=$dep->hasNewPlatform()?" newPl":""?>"><?=$dep->hasNewPlatform()?$dep->getNewPlatform():$dep->getPlatform()?></div>
	</div>
<?php } ?>
</div>
<?php endif; ?>
</body>
</html>
