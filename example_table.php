<?php
if (isset($_GET["station"])) {
	$station = $_GET["station"];
} else {
	$station = "Hannover Hbf";
}
include("db.php");
$numEntries = 12;

$db = new DeutscheBahn();
$station = $db->getStationByName($station)[0];
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>Abfahrtstafel <?=$station->getName()?></title>
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
		#depatures {
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
	</style>
</head>
<body>
<div id="header">
	<div id="time"><?php $tm = localtime();printf("%02d:%02d", $tm[2], $tm[1]);?></div>
	<div id="name"><?=$station->getName()?></div>
</div>
<div id="depatures">
	<div class="top">
		<div class="time">Zeit</div>
		<div class="dest">Nach</div>
		<div class="plat">Gleis</div>
	</div>
<?php
foreach ($station->getDepatures($numEntries) as $dep) {?>
	<div class="dep">
		<div class="time">
			<div class="firstrow">
				<div class="sched"><?=$dep->getTime()?></div>
				<div class="del"><?=$dep->isCancelled()?"":$dep->getDelay()?></div>
			</div>
				<div class="prod"><?=$dep->getProduct()?> <?=$dep->getLine()?></div>
				<div class="eta"><?=$dep->isCancelled() || !$dep->getDelayNum()?"":date("H:i",strtotime("+{$dep->getDelayNum()} minutes", strtotime($dep->getTime().":00")))?></div>
		</div>
		<div class="dest<?=$dep->isCancelled()?" cancel":""?>">
			<div class="name"><?=$dep->getDirection()?></div>
			<div class="delayCause"><?=$dep->getDelayReason()?></div>
		</div>
		<div class="plat<?=$dep->hasNewPlatform()?" newPl":""?>"><?=$dep->hasNewPlatform()?$dep->getNewPlatform():$dep->getPlatform()?></div>
	</div>
<?php } ?>
</div>
</body>
</html>
