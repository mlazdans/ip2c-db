<?php

error_reporting(E_ALL);

if(PHP_INT_SIZE < 8){
	trigger_error("64-bit PHP required", E_USER_ERROR);
}

$debug = 1;
$threads = 7;
set_include_path(get_include_path().PATH_SEPARATOR.dirname(__FILE__));

require_once('stdlib.inc.php');

$CONSOLE_START = mt();

function __console_shutdown(){
	fprintf(STDERR, "\nTotal run time: %s\n", print_time($GLOBALS['CONSOLE_START'], mt()));
	fprintf(STDERR, "Total memory usage: %.2fMB\n", print_memory(memory_get_peak_usage(true)));
}

ini_set('display_errors', 'stderr');
ini_set('error_prepend_string', '');
ini_set('error_append_string', '');
ini_set('html_errors', false);
ini_set('max_execution_time', 0);
ini_set('output_buffering', 0);
ini_set('memory_limit', '2048M');

//if(!empty($PRINT_STATS))
register_shutdown_function('__console_shutdown');
