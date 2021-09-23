<?php

declare(strict_types = 1);

class ProcessWhois {
	private $db;
	private CountryRangeDB $data;

	public function __construct($db){
		$this->db = $db;
		$this->data = new CountryRangeDB($db);
	}

	function fetch_record($f){
		$state = [];
		$key = '';
		while($line = fgets($f))
			if($line == "\n")
				return $state;
			elseif(($line[0] == '#') || ($line[0] == '%') || ($line[0] == '+') || ($line == 'EOF'))
				continue;
			elseif(preg_match('/^([a-z0-9\-]+):(.*)$/i', $line, $m)){
				$key = strtolower($m[1]);
				$val = trim($m[2]);
				if(isset($state[$key])){
					if(is_array($state[$key])){
						$state[$key][] = $val;
					} else {
						$state[$key] = [$state[$key], $val];
					}
				} else {
					$state[$key] = $val;
				}
			} else {
				if(isset($state[$key])){
					if(is_array($state[$key])){
						$state[$key][] = trim($line);
					} else {
						$state[$key] = [$state[$key], trim($line)];
					}
				} else {
					print "key not set ($key), line ($line)\n";
					print_r($state);
				}
			}

		return feof($f) && !$state ? false : $state;
	}

	public function run(): CountryRangeDB {
		$logger = new Logger;
		$logger->logn("Start processing ($this->db)");

		$f = fopen($this->db, "r");

		while(($record = $this->fetch_record($f)) !== false)
			$this->save_state($record);

		fclose($f);

		$logger->logTSn("Done processing ($this->db) in ");

		return $this->data;
	}

	function save_state($record){
		if(!isset($record['inetnum']))
			return;

		if(!isset($record['country']))
			return;

		if(is_array($record['country']))
			$country = $record['country'][0];
		else
			$country = $record['country'];

		$country = trim(explode("#", $country)[0]);

		$skipBlocks = 'AFRINIC-CIDR-BLOCK|APNIC-AP-ERX|ARIN-CIDR-BLOCK|ERX-NETBLOCK|'.
		'IANA-BLOCK|IANA-NETBLOCK|LACNIC-CIDR-BLOCK|RIPE-CIDR-BLOCK|'.
		'IETF-RESERVED-ADDRESS-BLOCK|IANA-BLK|APNIC-LABS';

		if(isset($record['netname']) && preg_match("/$skipBlocks/", $record['netname'])){
			// print "Skip block ($state[ipStart] - $state[ipEnd])\n";
			//trigger_error("Skipping bad block ($state[ipStart] - $state[ipEnd])");
			return;
		}

		$range = array_map('trim', explode('-', $record['inetnum']));

		if(count($range) == 1){
			# Process CIDR: 24.232.32.4/30
			list($ip_start_long, $ip_end_long) = cidrToRange($range[0]);
			$ip_start = long2ip($ip_start_long);
			$ip_end = long2ip($ip_end_long);
		} elseif(count($range) == 2){
			# Process range: 202.127.160.0 - 202.127.167.255
			list($ip_start, $ip_end) = $range;
			$ip_start_long = ip2long($ip_start);
			$ip_end_long = ip2long($ip_end);
		} else {
			print "Unexpected inetnum format ({$record['inetnum']})\n";
			return;
		}

		if(($ip_start_long === false) || ($ip_end_long === FALSE)){
			print "Incorrect IP address ($ip_start, $ip_end) for ({$record['inetnum']})";
			return;
		}

		if(!($c = country_rule($country)))
			return;

		// $this->data[] = "$c,$ip_start_long,$ip_end_long";
		// $this->data[] = new CountryRange($c, $ip_start_long, $ip_end_long);
		$this->data->addRecord(new CountryRange($c, $ip_start_long, $ip_end_long));

		return true;
	}
}
