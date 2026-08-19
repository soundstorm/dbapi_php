<?php

class DeutscheBahnJourney {
	private $time;
	private $realTime;
	private $delay;
	private $notes;
	private $platform;
	private $newPlatform;
	private $target;
	private $depStation;
	private $product;
	private $productShort;
	private $line;

	public function __construct($time, $realTime, $delay, $notes, $platform, $newPlatform, $depStation, $target, $product, $line) {
		$this->time         = $time;
		$this->realTime     = $realTime;
		$this->delay        = $delay;
		$this->notes        = $notes;
		$this->platform     = $platform;
		$this->newPlatform  = $newPlatform;
		$this->depStation   = $depStation;
		$this->target       = $target;
		$this->product      = $product;
		$this->productShort = substr($line, 0, strpos($line, " "));
		$this->line         = substr($line, strpos($line, " ") + 1);
	}
	/*
	* Get arrival/departure as DateTime
	*/
	public function getTime() {
		return $this->time;
	}
	/*
	* Get real time arrival/departure as DateTime
	*/
	public function getRealTime() {
		return $this->realTime;
	}
	/*
	* Get delay as DateTimeInterface
	*/
	public function getDelay() {
		return $this->delay;
	}
	/*
	* Calculate delay in minutes
	*/
	public function getDelayNum() {
		return (int)$this->getDelay()->format("%H") * 60 + (int)$this->getDelay()->format("%I");
	}
	/*
	* Get notes like delay cause or outage
	*/
	public function getNotes() {
		return $this->notes;
	}
	/*
	* Get regular platform, '-' if no platforms are defined
	*/
	public function getPlatform() {
		return $this->platform;
	}
	/*
	* Check if platform is changed
	*/
	public function hasNewPlatform() {
		return !is_null($this->newPlatform);
	}
	/*
	* Get new Platform if available, null otherwise
	*/
	public function getNewPlatform() {
		return $this->newPlatform;
	}
	/*
	* Get service direction
	*/
	public function getTarget() {
		return $this->target;
	}
	/*
	* Get service direction
	*/
	public function getDirection() {
		return $this->target;
	}
	/*
	* Get Station, may differ from requested Station like ZOB or (U), but is directly associated
	*/
	public function getDepStation() {
		return $this->depStation;
	}
	/*
	* Get Generic Line Product e.g. STR/RE
	*/
	public function getProduct() {
		return $this->product;
	}
	/*
	* Get Line Product e.g. STB/FLX
	*/
	public function getProductShort() {
		return $this->productShort;
	}
	/*
	* Get Line Number e.g. 8
	*/
	public function getLine() {
		return $this->line;
	}
	/*
	* Check, if service is cancelled
	*/
	public function isCancelled() {
		foreach ($this->notes as $note) {
			if (isset($note->text) && $note->text == "Halt entfällt") return 1;
		}
		return 0;
	}
}

class DeutscheBahnStation {
	private $name;
	private $stationId;
	private $locationId;
	private $coordinates;
	private $locationType;
	private $products;
	private $evaNr;
	private $filter = Array("HOCHGESCHWINDIGKEITSZUEGE","INTERCITYUNDEUROCITYZUEGE","INTERREGIOUNDSCHNELLZUEGE","NAHVERKEHRSONSTIGEZUEGE","SBAHNEN","BUSSE","SCHIFFE","UBAHN","STRASSENBAHN","ANRUFPFLICHTIGEVERKEHRE");

	public function __construct($name, $stationId, $locationId, $coordinates, $products, $locationType, $evaNr) {
		$this->name = $name;
		$this->stationId = $stationId;
		$this->locationId = $locationId;
		$this->coordinates = $coordinates;
		$this->products = $products;
		$this->locationType = $locationType;
		$this->evaNr = $evaNr;
	}

	/*
	* Get Station Name
	*/
	public function getName() {
		return $this->name;
	}
	/*
	* Get Station Identifier
	*/
	public function getStationID() {
		return $this->stationId;
	}
	/*
	* Get Station Identifier, reverse compatibility
	*/
	public function getStationNumber() {
		return $this->locationId;
	}
	/*
	* Array of longitude and latitude
	*/
	public function getCoordinates() {
		return $this->coordinates;
	}
	/*
	* Only ST allowed
	*/
	public function getStationType() {
		return $this->locationType;
	}

	/*
	* Get services/products available at station, if queried by getStationByName
	*/
	public function getStationProducts() {
		return $this->products;
	}

	/*
	* Set filters for services.
	*/
	public function setFilter($ice = 1, $ic_ec = 0, $ir = 0, $re = 0, $s = 0, $bus = 0, $ship = 0, $u = 0, $str = 0, $ast = 0) {
		$filter = Array();
		if ($ice) $filter[] = "HOCHGESCHWINDIGKEITSZUEGE";
		if ($ic_ec) $filter[] = "INTERCITYUNDEUROCITYZUEGE";
		if ($ir) $filter[] = "INTERREGIOUNDSCHNELLZUEGE";
		if ($re) $filter[] ="NAHVERKEHRSONSTIGEZUEGE";
		if ($s) $filter[] ="SBAHNEN";
		if ($bus) $filter[] = "BUSSE";
		if ($ship) $filter[] = "SCHIFFE";
		if ($u) $filter[] = "UBAHN";
		if ($str) $filter[] = "STRASSENBAHN";
		if ($ast) $filter[] = "ANRUFPFLICHTIGEVERKEHRE";
		$this->filter = $filter;
	}

	/* Currently not working.
	*/
	private function getConnection($dest, $date, $type) {
		if (is_null($date)) {
			$date = new DateTime();
		}
		$headers = Array(
			"User-Agent: DBNavigator/iOS/26.8.0",
			"Content-Type: application/x.db.vendo.mob.verbindungssuche.v8+json",
			"Accept: application/x.db.vendo.mob.verbindungssuche.v8+json",
			"X-Correlation-ID: FOO",
		);
		$req = Array();
		$req['bahnBonusInfo'] = Array(
			'activeBonusPoints' => 0,
			'statusLevel' => 0
		);
		$req['einstiegsTypList'] = Array("STANDARD");
		$req['fahrverguenstigungen'] = Array(
			"deutschlandTicketVorhanden" => false,
			"nurDeutschlandTicketVerbindungen" => false
		);
		$req['klasse'] = "KLASSE_2";
		$req['reiseHin'] = Array(
			"wunsch" => Array(
				"abgangsLocationId" => preg_replace('/p=[\d]*/','p=1706553807', str_replace("U=81","U=80",$this->locationId)),
				"economic" => true,
				"verkehrsmittel" => $this->filter,
				"viaLocations" => Array(),
				"zeitWunsch" => Array(
					"reiseDatum" => $date->format("Y-m-d\TH:i:sP"),
					"zeitPunktArt" => $type
				),
				"zielLocationId" => preg_replace('/p=[\d]*/','p=1706553807',str_replace("U=81","U=80", substr($dest, 0, strpos($dest, "i="))))
			)
		);
		$req['reisendenProfil'] = Array();
		$req['reisendenProfil']['reisende'] = Array();
		$req['reisendenProfil']['reisende'][0] = Array(
			"ermaessigungen" => Array("KEINE_ERMAESSIGUNG KLASSENLOS"),
			"reisendenTyp" => "ERWACHSENER"
		);
		$req['reservierungsKontingenteVorhanden'] = false;
		print_r($headers);
		$json = json_encode($req);
		$ch = curl_init("https://app.services-bahn.de/mob/angebote/fahrplan");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
		curl_setopt($ch, CURLOPT_SSL_EC_CURVES, 'X25519:P-256');
 		curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-CHACHA20-POLY1305');
 		curl_setopt($ch, CURLOPT_TLS13_CIPHERS, 'TLS_AES_128_GCM_SHA256:TLS_CHACHA20_POLY1305_SHA256:TLS_AES_256_GCM_SHA384');
		curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		$ret = curl_exec($ch);
		return Null;
	}

	/* Currently not working.
	*/
	public function getConnectionDeparture($dest, $date = Null) {
		return $this->getJourney($dest, $date, "ABFAHRT");
	}
	/* Currently not working.
	*/
	public function getConnectionArrival($dest, $date = Null) {
		return $this->getJourney($dest, $date, "ANKUNFT");
	}

	/*
	* Query services at location
	*/
	public function getLocation() {
		$headers = Array(
			"User-Agent: DBNavigator/iOS/26.8.0",
			"Accept: application/x.db.vendo.mob.location.v3+json",
			"X-Correlation-ID: FOO",
		);
		$ch = curl_init("https://app.services-bahn.de/mob/location/details/".$this->evaNr);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_SSL_EC_CURVES, 'X25519:P-256');
 		curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-CHACHA20-POLY1305');
 		curl_setopt($ch, CURLOPT_TLS13_CIPHERS, 'TLS_AES_128_GCM_SHA256:TLS_CHACHA20_POLY1305_SHA256:TLS_AES_256_GCM_SHA384');
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		$ret = curl_exec($ch);
		return json_decode($ret);
	}

	/*
	* @param type    ankunft|abfahrt
	* @param num     Unused, backwards compatibility, getting all departures within 1h by default
	* @param time    Time as H:i
	* @param date    Date as Y-m-d
	* @param target  Unused, backwards compatibility, cannot set direction
	*/
	private function getStationBoard($type, $num, $time, $date, $target) {
		$headers = Array(
			"User-Agent: DBNavigator/iOS/26.8.0",
			"Content-Type: application/x.db.vendo.mob.bahnhofstafeln.v2+json",
			"Accept: application/x.db.vendo.mob.bahnhofstafeln.v2+json",
			"X-Correlation-ID: FOO",
		);
		$req = Array();
		if (is_null($date)) {
			$req['datum'] = date('Y-m-d');
		} else {
			$req['datum'] = $date;
		}
		if (is_null($date)) {
			$req['anfragezeit'] = date('H:i');
		} else {
			$req['anfragezeit'] = $time;
		}
		$req['ursprungsBahnhofId'] = $this->locationId;
		$req['verkehrsmittel'] = $this->filter;
		$json = json_encode($req);
		$ch = curl_init("https://app.services-bahn.de/mob/bahnhofstafel/".$type);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
		curl_setopt($ch, CURLOPT_SSL_EC_CURVES, 'X25519:P-256');
 		curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-CHACHA20-POLY1305');
 		curl_setopt($ch, CURLOPT_TLS13_CIPHERS, 'TLS_AES_128_GCM_SHA256:TLS_CHACHA20_POLY1305_SHA256:TLS_AES_256_GCM_SHA384');
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		$ret = curl_exec($ch);

		$jsonJourneys = json_decode($ret);
		if ($type == "ankunft") {
			$jsonJourneys = $jsonJourneys->bahnhofstafelAnkunftPositionen;
		} else {
			$jsonJourneys = $jsonJourneys->bahnhofstafelAbfahrtPositionen;
		}
		$journeys = Array();
		foreach ($jsonJourneys as $jsonJourney) {
			if ($type == "ankunft") {
				$t = new DateTime($jsonJourney->ankunftsDatum);
				if (isset($jsonJourney->ezAnkunftsDatum)) {
					$rt = new DateTime($jsonJourney->ezAnkunftsDatum);
				} else {
					$rt = $t;
				}
				$dir = isset($jsonJourney->abgangsOrt) ? $jsonJourney->abgangsOrt : "-";
			} else {
				$t = new DateTime($jsonJourney->abgangsDatum);
				if (isset($jsonJourney->ezAbgangsDatum)) {
					$rt = new DateTime($jsonJourney->ezAbgangsDatum);
				} else {
					$rt = $t;
				}
				$dir = isset($jsonJourney->richtung) ? $jsonJourney->richtung : "-";
			}
			$del = date_diff($t, $rt);
			$depStation = new DeutscheBahnStation(
				$jsonJourney->abfrageOrt->name,
				(isset($jsonJourney->abfrageOrt->stationId) ? $jsonJourney->abfrageOrt->stationId : -1),
				$jsonJourney->abfrageOrt->locationId,
				Array("longitude"=>-1,"latitude"=>-1),
				-1,
				"",
				0
			);
			//preg_match("/([a-zA-Z]+)\s*(\d*)#/", $att["PROD"], $m);
			//$prod = count($m) == 3 ? $m[1] : '-';
			//$line = count($m) == 3 ? $m[2] : '-';

			$journeys[] = new DeutscheBahnJourney(
				$t,
				$rt,
				$del,
				$jsonJourney->echtzeitNotizen,
				isset($jsonJourney->gleis) ? $jsonJourney->gleis : '-',
				isset($jsonJourney->ezGleis) ? $jsonJourney->ezGleis : NULL,
				$depStation,
				$dir,
				$jsonJourney->produktGattung,
				$jsonJourney->mitteltext
			);
		}

		return $journeys;
	}

	/*
	* @param num     Unused, backwards compatibility, getting all departures within 1h by default
	* @param time    Time as H:i
	* @param date    Date as Y-m-d
	* @param target  Unused, backwards compatibility, cannot set direction
	*/
	public function getDepartures($num = NULL, $time = NULL, $date = NULL, $target = NULL) {
		return $this->getStationBoard("abfahrt", $num, $time, $date, $target);
	}
	/*
	* @param num     Unused, backwards compatibility, getting all departures within 1h by default
	* @param time    Time as H:i
	* @param date    Date as Y-m-d
	* @param target  Unused, backwards compatibility, cannot set direction
	*/
	public function getArrivals($num = NULL, $time = NULL, $date = NULL, $target = NULL) {
		return $this->getStationBoard("ankunft", $num, $time, $date, $target);
	}
}

class DeutscheBahn {
	private function getStationJSON($json, $num) {
		$headers = Array(
			"User-Agent: DBNavigator/iOS/26.8.0",
			"Content-Type: application/x.db.vendo.mob.location.v3+json",
			"Accept: application/x.db.vendo.mob.location.v3+json",
			"X-Correlation-ID: FOO",
		);
		$ch = curl_init("https://app.services-bahn.de/mob/location/search");
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
		curl_setopt($ch, CURLOPT_SSL_EC_CURVES, 'X25519:P-256');
 		curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-CHACHA20-POLY1305');
 		curl_setopt($ch, CURLOPT_TLS13_CIPHERS, 'TLS_AES_128_GCM_SHA256:TLS_CHACHA20_POLY1305_SHA256:TLS_AES_256_GCM_SHA384');
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		$ret = curl_exec($ch);

		$jsonStations = json_decode($ret);
		$stations = Array();
		foreach ($jsonStations as $jsonStation) {
			if ($jsonStation->locationType == "ST") {
				$stations[] = new DeutscheBahnStation(
					$jsonStation->name,
					(isset($jsonStation->stationId) ? $jsonStation->stationId : -1),
					$jsonStation->locationId,
					$jsonStation->coordinates,
					$jsonStation->products,
					$jsonStation->locationType,
					$jsonStation->evaNr
				);
			}
		}
		return $stations;
	}

	/*
	* @param name   Name of Station to query
	* @param num    Unused, backwards compatibility
	*/
	public function getStationByName($name, $num=1) {
		$json = json_encode(Array(
			"locationTypes" => Array(
				"ST"
			),
			"searchTerm" => $name
		));
		return $this->getStationJSON($json, $num);
	}
}
