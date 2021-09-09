<?php

declare(strict_types = 1);

use parallel\Future;

class TJob {
	var $done = false;
	var $running = false;

	/** @var Closure $job */
	var $job;

	/** @var array $job_params */
	var $job_params;

	/** @var Runtime $worker */
	var $worker;

	/** @var Future $future */
	var $future;

	function __construct(Closure $job, array $params = []){
		$this->job = $job;
		$this->job_params = $params;
	}
}
