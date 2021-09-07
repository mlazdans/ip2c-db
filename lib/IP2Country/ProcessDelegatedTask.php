<?php

require_once('Logger.php');

class ProcessDelegatedTask extends Threaded {
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
		$this->logger = new Logger;
		$this->logger->logn("Start processing ($this->db)");
		//$commandLine = 'grep -e "^[^#].*|ipv4|.*|assigned\|allocated" '.$this->db;
		$commandLine = 'grep -e "^[^#].*|ipv4|.*|\(allocated\|assigned\).*$" '.$this->db;

		$pipes = array();
		$descriptorspec = array(
			0 => array("pipe", "r"),
			1 => array("pipe", "w"),
			//2 => array("file", ".error-log", "a"),
		);
		$r = proc_open($commandLine, $descriptorspec, $pipes);

		while(!feof($pipes[1])){
			$parts = explode("|", trim(fgets($pipes[1])));
			if(count($parts) < 7){
				continue;
			}
			$country = $parts[1];
			// if(!$country){
			// 	continue;
			// }
			$ipStart = ip2long($parts[3]);
			$ipCount = $parts[4];
			$ipEnd = $ipStart + $ipCount - 1;
			$this->data[] = "$country,$ipStart,$ipEnd";
		}
		proc_close($r);
		$this->logger->logTSn("Done processing ($this->db) in ");
		$this->collector->addData($this->db, $this->data);
		$this->completed = true;
	}

	function getResult(){
		return $this->data;
	}

	function isCompleted(){
		return $this->completed;
	}
}
