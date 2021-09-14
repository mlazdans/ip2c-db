<?php

declare(strict_types = 1);

class Logger {
	var $time;

	function __construct(){
		$this->resetTS();
	}

	function resetTS(){
		$this->time = $this->ts();
		return $this;
	}

	function log($msg){
		print $msg;
		return $this;
	}

	function logn($msg = ""){
		return $this->log("$msg\n");
	}

	function logTS($msg = ""){
		return $this->log($msg.$this->formatTS($this->time, $this->ts()));
	}

	function logTSn($msg = ""){
		return $this->logn($msg.$this->formatTS($this->time, $this->ts()));
	}

	function ts(){
		return microtime(true);
	}

	function formatTS($start_time, $end_time){
		return print_time($start_time, $end_time);
	}
}
