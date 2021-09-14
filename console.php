<?php

$CONSOLE_START = mt();

function __console_shutdown(){
	fprintf(STDERR, "\nTotal run time: %s\n", print_time($GLOBALS['CONSOLE_START'], mt()));
	fprintf(STDERR, "Total memory usage: %.2fMB\n", print_memory(memory_get_peak_usage(true)));
}

//if(!empty($PRINT_STATS))
register_shutdown_function('__console_shutdown');
