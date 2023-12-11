<?php

class DeutscheBahnJourney {
	private $time;
	private $date;
	private $delay;
	private $delayNum;
	private $delayReason;
	private $platform;
	private $newPlatform;
	private $direction;
	private $target;
	private $depStation;
	private $product;
	private $line;

	public function __construct($time, $date, $delay, $delayNum, $delayReason, $platform, $newPlatform, $direction, $target, $depStation, $product, $line) {
		$this->time        = $time;
		$this->date        = $date;
		$this->delay       = $delay;
		$this->delayNum    = $delayNum;
		$this->delayReason = $delayReason;
		$this->platform    = $platform;
		$this->direction   = $direction;
		$this->target      = $target;
		$this->depStation  = $depStation;
		$this->product     = $product;
		$this->line        = $line;
	}
	public function getTime() {
		return $this->time;
	}
	public function getDate() {
		return $this->date;
	}
	public function getDelay() {
		return $this->delay;
	}
	public function getDelayNum() {
		return $this->delayNum;
	}
	public function getDelayReason() {
		return $this->delayReason;
	}
	public function getPlatform() {
		return $this->platform;
	}
	public function hasNewPlatform() {
		return !is_null($this->newPlatform);
	}
	public function getNewPlatform() {
		return $this->newPlatform;
	}
	public function getDirection() {
		return $this->direction;
	}
	public function getTarget() {
		return $this->target;
	}
	public function getDepStation() {
		return $this->depStation;
	}
	public function getProduct() {
		return $this->product;
	}
	public function getLine() {
		return $this->line;
	}
	public function isCancelled() {
		return $this->delay == "cancel";
	}
}

class DeutscheBahnStation {
	private $name;
	private $extid;
	private $extstnr;
	private $x;
	private $y;
	private $type;
	private $filter = 1023;

	public function __construct($name, $extid, $extstnr, $x, $y, $type) {
		$this->name = $name;
		$this->extid = $extid;
		$this->extstnr = $extstnr;
		$this->x = $x;
		$this->y = $y;
		$this->type = $type;
	}

	public function getName() {
		return $this->name;
	}
	public function getStationID() {
		return $this->extid;
	}
	public function getStationNumber() {
		return $this->extstnr;
	}
	public function getCoordinates() {
		return Array($this->x, $this->y);
	}
	public function getStationType() {
		return $this->type;
	}

	public function setFilter($ice = 1, $ic_ec = 0, $ir = 0, $re = 0, $s = 0, $bus = 0, $ship = 0, $u = 0, $str = 0, $ast = 0) {
		$this->filter =
			($ice   ? 0x200 : 0) +
			($ic_ec ? 0x100 : 0) +
			($ir    ? 0x080 : 0) +
			($re    ? 0x040 : 0) +
			($s     ? 0x020 : 0) +
			($bus   ? 0x010 : 0) +
			($ship  ? 0x008 : 0) +
			($u     ? 0x004 : 0) +
			($str   ? 0x002 : 0) +
			($ast   ? 0x001 : 0);
	}

	private function getStationBoard($type, $num, $time, $date, $target) {
		$query  = "start=yes&L=vs_java3&productsFilter=".sprintf("%010d", decbin($this->filter));
		$query .= "&input={$this->extstnr}";
		$query .= "&boardType={$type}";
		if (!is_null($num)) {
			$query .= "&maxJourneys={$num}";
		}
		if (!is_null($time)) {
			$query .= "&time={$time}";
		}
		if (!is_null($date)) {
			$query .= "&date={$date}";
		}
		if (!is_null($target)) {
			$query .= "&dirInput={$target}";
		}
		
		$ch = curl_init("https://reiseauskunft.bahn.de/bin/stboard.exe/dn?{$query}");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$ret = curl_exec($ch);
		$ret = "<?xml version=\"1.0\" encoding=\"iso-8859-1\" ?><ResC xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\">" . $ret . "</ResC>";

		$parser = xml_parser_create();
		xml_parse_into_struct($parser, $ret, $values);
		xml_parser_free($parser);

		$journey = Array();
		foreach ($values as $value) {
			if ($value["tag"] == "JOURNEY" && $value["type"] != "close") {
				$att = $value["attributes"];
				$p = substr($att["PROD"], 0, strpos($att["PROD"], "#"));
				preg_match("/([a-zA-Z]+)\s*(\d*)#/", $att["PROD"], $m);
				$prod = count($m) == 3 ? $m[1] : '-';
				$line = count($m) == 3 ? $m[2] : '-';
				$journeys[] = new DeutscheBahnJourney(
					$att["FPTIME"],
					$att["FPDATE"],
					$att["DELAY"],
					$att["E_DELAY"],
					$att["DELAYREASON"],
					array_key_exists("PLATFORM", $att) ? $att["PLATFORM"] : "-",
					array_key_exists("NEWPL", $att) ? $att["PLATFORM"] : "-",
					$att["DIR"],
					array_key_exists("TARGET", $att) ? $att["TARGET"] : "-",
					array_key_exists("DEPSTATION", $att) ? $att["DEPSTATION"] : "-",
					$prod,
					$line
				);
			}
		}

		return $journeys;
	}

	public function getDepatures($num = NULL, $time = NULL, $date = NULL, $target = NULL) {
		return $this->getStationBoard("dep", $num, $time, $date, $target);
	}
	public function getArrivals($num = NULL, $time = NULL, $date = NULL, $target = NULL) {
		return $this->getStationBoard("arr", $num, $time, $date, $target);
	}
}

class DeutscheBahn {
	private function getStationXML($req, $num) {
		$xml  = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\r\n";
		$xml .= "<ReqC ver=\"1.1\" prod=\"String\" lang=\"DE\">\r\n";
		$xml .= "<LocValReq id=\"001\" maxNr=\"{$num}\" sMode=\"1\">\r\n";
		$xml .= $req."\r\n";
		$xml .= "</LocValReq>\r\n";
		$xml .= "</ReqC>";

		$ch = curl_init("https://reiseauskunft.bahn.de/bin/query.exe/dn");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		$ret = curl_exec($ch);

		$parser = xml_parser_create();
		xml_parse_into_struct($parser, $ret, $values);
		xml_parser_free($parser);

		$stations = Array();
		foreach ($values as $value) {
			if ($value["tag"] == "STATION") {
				$att = $value["attributes"];
				$stations[] = new DeutscheBahnStation(
					$att["NAME"],
					$att["EXTERNALID"],
					$att["EXTERNALSTATIONNR"],
					$att["X"],
					$att["Y"],
					$att["TYPE"]
				);
			}
		}
		return $stations;
	}

	public function getStationByName($name, $num=1) {
		return $this->getStationXML("<ReqLoc type=\"ST\" match=\"{$name}\" />", $num);
	}
}
