<?php

require('Logger.php');
require('RangeDB.php');

class CompactDBTask extends Threaded {
	private $db;
	private $datain;
	private $dataout;
	private $logger;

	public function __construct($data){
			$this->db = $data['db'];
			$this->datain = $data['datain'];
			$this->dataout = $data['dataout'];
	}

	public function run() {
		$c = new RangeDB($this->db);
		$this->logger = new Logger;
		$this->logger->logn("Start processing ($this->db)");
		$c->load($this->datain);
		while($c->overlap());
		//$c->save($this->dataout);
		$c->saveWithMerges($this->dataout);
		$this->logger->logTSn("Done processing ($this->db) in ")->resetTS();
	}
}
