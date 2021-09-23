<?php

declare(strict_types = 1);

class ProcessDelegated {
	private $db;

	public function __construct($db){
		$this->db = $db;
	}

	public function run(): CountryRangeDB {
		$logger = new Logger;
		$logger->logn("Start processing ($this->db)");

		# TODO: parse arg, remove grep
		$commandLine = 'grep -e "^[^#].*|ipv4|.*|\(allocated\|assigned\).*$" '.$this->db;

		$pipes = [];
		$descriptorspec = [["pipe", "r"], ["pipe", "w"]];

		$r = proc_open($commandLine, $descriptorspec, $pipes);

		$data = new CountryRangeDB($this->db);
		while($line = fgets($pipes[1])){
			$parts = explode("|", trim($line));

			if(count($parts) < 7)
				continue;

			if(!($country = country_rule($parts[1])))
				continue;

			$ipStart = ip2long($parts[3]);
			$ipCount = (int)$parts[4];
			$ipEnd = $ipStart + $ipCount - 1;
			// $data[] = "$country,$ipStart,$ipEnd";
			// $data[] = new CountryRange($country, $ipStart, $ipEnd);
			$data->addRecord(new CountryRange($country, $ipStart, $ipEnd));
		}
		proc_close($r);

		$logger->logTSn("Done processing ($this->db) in ");

		return $data;
	}
}
