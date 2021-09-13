<?php

declare(strict_types = 1);

class TPool {
	var $thread_count;
	var $threads_running = 0;

	/** @var Runtime[] $workers */
	var $workers = [];

	/** @var Runtime[] $workers_avail */
	var $workers_avail = [];

	/** @var TJob[] $jobs */
	var $jobs = [];

	var $job_counter = 0;

	function __construct(int $thread_count, string $bootstrap = null){
		$this->thread_count = $thread_count;
		if($bootstrap)
			\parallel\bootstrap($bootstrap);
		// for($i = 0; $i < $thread_count; $i++){
		// 	$this->workers[$i] = new Runtime($bootstrap);
		// 	// $this->set_avail_worker($this->workers[$i]);
		// 	$this->set_avail_worker($i);
		// }
	}

	// function get_avail_worker(){
	// 	return array_shift($this->workers_avail);
	// }

	// function set_avail_worker($worker){
	// 	return array_push($this->workers_avail, $worker);
	// }

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
						$this->threads_running--;
						// $this->set_avail_worker($job->worker);
					}
				} elseif($this->threads_running < $this->thread_count){
					$this->threads_running++;
					// print "Got worker $this->threads_running!\n";
					$job->running = true;
					$job->worker = $this->threads_running;
					$job->future = \parallel\run($job->job, $job->job_params);
				} else {
					// print "No avail workers\n";
					break;
				}
			}
			// print "Sleep\n";
			usleep(500000); // 0.5 sec
		} while($jobs);
	}
}
