<?php

require_once('Logger.php');

class ProcessWhoisTask extends Threaded {
	private $db;
  private $logger;
	//private $state;
	private $data = [];
	private $completed = false;
	private $collector;
	//protected $complete;

	public function __construct($db, $collector){
		$this->db = $db;
		$this->collector = $collector;
		//$this->data[$db] = [];
		//$this->complete = false;
		//$this->datain = $data['datain'];
		//$this->dataout = $data['dataout'];
	}

	public function run() {
		//$collector = $this->worker->getCollector();

		$this->logger = new Logger;
		$this->logger->logn("Start processing ($this->db)");
		$commandLine = 'grep -e "^inetnum:" -e "^country:" -e "^netname:" '.$this->db;

		$pipes = array();
		$descriptorspec = array(
			0 => array("pipe", "r"),
			1 => array("pipe", "w"),
			//2 => array("file", ".error-log", "a"),
		);
		$state = $this->defaultstate();
		$r = proc_open($commandLine, $descriptorspec, $pipes);

		while(!feof($pipes[1])){
			$state = $this->processor($state, fgets($pipes[1]));
		}
		proc_close($r);

		# save last state
		$this->save_state($state);

		$this->logger->logTSn("Done processing ($this->db) in ");
		$this->collector->addData($this->db, $this->data);
		$this->completed = true;
		/*
		$collector->synchronized(function($collector, $key, $data) {
			 $collector->addData($key, $data);
		}, $collector, $this->db, $this->data);
		*/
	}

	function getResult(){
		return $this->data;
	}

	function isCompleted(){
		return $this->completed;
	}

	function save_state($state){
		if($state['error'] || $state['saved']){
			return;
		}

		$skipBlocks =
			'AFRINIC-CIDR-BLOCK|APNIC-AP-ERX|ARIN-CIDR-BLOCK|ERX-NETBLOCK|'.
			'IANA-BLOCK|IANA-NETBLOCK|LACNIC-CIDR-BLOCK|RIPE-CIDR-BLOCK|'.
			'IETF-RESERVED-ADDRESS-BLOCK|IANA-BLK|APNIC-LABS';

		if(preg_match("/$skipBlocks/", $state['netname'])){
			print "Skip block ($state[ipStart] - $state[ipEnd])\n";
			//trigger_error("Skipping bad block ($state[ipStart] - $state[ipEnd])");
			return;
		}

		if(count($state['countries']) > 1){
			$state['countries'] = array_unique($state['countries']);
		}

		if(count($state['countries']) > 1){
			//$c = join(",", $state['countries']);
			//trigger_error("Skip countries > 1 ($c) for ($state[ipStart] - $state[ipEnd])");
			return;
		}

		if(!$state['countries']){
			//trigger_error("No countries for ($state[ipStart] - $state[ipEnd])");
			return;
		}

		//foreach($state['countries'] as $c){
		$c = $state['countries'][0];
		if($c == 'EU'){
			return;
		}
		if($c == 'UNITED STATES'){
			$c = 'US';
		}

		$state['saved'] = true;
		$this->data[] = "$c,$state[ipStartLong],$state[ipEndLong]";

		return $state;
		//fputs($f, "$c,$state[ipStartLong],$state[ipEndLong]\n");
	}

	function processor($state, $line){
		if(!$line)
			return $state;

		if(!preg_match('/^(inetnum|country|netname):(.*)$/i', $line, $m)){
			trigger_error("Unexpected format ($line)", E_USER_ERROR);
		}
		$field = trim($m[1]);
		$data = trim($m[2]);

		if($field == 'country'){
			$state['countries'][] = strtoupper(substr($data,0,2));
			return $state;

		}
		if($field == 'netname'){
			$state['netname'] = $data;
			return $state;
		}

		if($field == 'inetnum'){
			if($state['counter']){
				$this->save_state($state);
				$state = $this->defaultstate();
			}
			$state['counter']++;

			$range = array_map('trim', explode('-', trim($data)));
			if(count($range) != 2){
				trigger_error("Unexpected inetnum format ($data)");
				$state['error'] = true;
				return $state;
			}
			list($ip_start, $ip_end) = $range;
			$long_start = ip2long($ip_start);
			$long_end = ip2long($ip_end);

			if(($long_start===FALSE) || ($long_end===FALSE)){
				trigger_error("Incorrect IP address ($ip_start, $ip_end)");
				$state['error'] = true;
				return $state;
			}

			$newState = array(
				'ipStart'=>$ip_start,
				'ipEnd'=>$ip_end,
				'ipStartLong'=>$long_start,
				'ipEndLong'=>$long_end,
			);
			return array_merge($state, $newState);
		}
	}

	public function defaultState(){
		return array(
			'saved'=>false,
			'counter'=>0,
			'error'=>false,
			'countries'=>[],
			'netname'=>'',
			'ipStart'=>'',
			'ipEnd'=>'',
			'ipStartLong'=>0,
			'ipEndLong'=>0,
		);
	}

}
