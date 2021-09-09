<?php

declare(strict_types = 1);

use parallel\Runtime;

class TPool {
	var $thread_count;

	/** @var Runtime[] $workers */
	var $workers = [];

	/** @var Runtime[] $workers_avail */
	var $workers_avail = [];

	/** @var TJob[] $jobs */
	var $jobs = [];

	var $job_counter = 0;

	function __construct(int $thread_count, string $bootstrap = null){
		$this->thread_count = $thread_count;

		for($i = 0; $i < $thread_count; $i++){
			$this->workers[$i] = new Runtime($bootstrap);
			// $this->set_avail_worker($this->workers[$i]);
			$this->set_avail_worker($i);
		}
	}

	function get_avail_worker(){
		return array_shift($this->workers_avail);
	}

	function set_avail_worker($worker){
		return array_push($this->workers_avail, $worker);
	}

	function submit(Closure $f, array $o = []){
		// $this->jobs[] = $this->workers[0]->run($f, $o);
		// $this->jobs[$this->job_counter++] = [$f, $o];
		$this->jobs[$this->job_counter++] = new TJob($f, $o);
	}

	function shutdown(){
		do {
			$jobs = 0;
			foreach($this->jobs as &$job){
				if($job->done){
					// print "Is done\n";
					continue;
				}

				$jobs++;

				if($job->running){
					// print "Is running\n";
					if($job->future->done()){
						// print "Done job\n";
						$job->done = true;
						$job->running = false;
						$this->set_avail_worker($job->worker);
					}
				} elseif(($workerId = $this->get_avail_worker()) !== null){
					// print "Got worker!\n";
					$job->running = true;
					$job->worker = $workerId;
					$job->future = $this->workers[$workerId]->run($job->job, $job->job_params);
				} else {
					// print "No avail workers\n";
					break;
				}
			}
			// print "Sleep\n";
			sleep(1);
		} while($jobs);
	}
}
