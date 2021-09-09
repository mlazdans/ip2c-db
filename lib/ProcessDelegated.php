<?php

declare(strict_types = 1);

class ProcessDelegated {
	private $db;

	public function __construct($db){
		$this->db = $db;
	}

	public function run() {
		$logger = new Logger;
		$logger->logn("Start processing ($this->db)");

		# TODO: parse arg
		$commandLine = 'grep -e "^[^#].*|ipv4|.*|\(allocated\|assigned\).*$" '.$this->db;

		$pipes = [];
		$descriptorspec = [["pipe", "r"], ["pipe", "w"]];

		$r = proc_open($commandLine, $descriptorspec, $pipes);

		$data = [];
		while(!feof($pipes[1])){
			$parts = explode("|", trim((string)fgets($pipes[1])));
			if(count($parts) < 7){
				continue;
			}
			$country = $parts[1];
			$ipStart = ip2long($parts[3]);
			$ipCount = $parts[4];
			$ipEnd = $ipStart + $ipCount - 1;
			$data[] = "$country,$ipStart,$ipEnd";
		}
		proc_close($r);

		$logger->logTSn("Done processing ($this->db) in ");

		return $data;
	}
}
