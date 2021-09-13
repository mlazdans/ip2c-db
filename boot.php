<?php

error_reporting(E_ALL);

if(PHP_INT_SIZE < 8){
	trigger_error("64-bit PHP required", E_USER_ERROR);
}

ini_set('display_errors', 'stderr');
ini_set('error_prepend_string', '');
ini_set('error_append_string', '');
ini_set('html_errors', false);
ini_set('max_execution_time', 0);
ini_set('output_buffering', 0);
ini_set('memory_limit', '2048M');

spl_autoload_register();

$root = realpath(__DIR__);

# Include paths
$LIBS = [
	$root,
	$root.DIRECTORY_SEPARATOR.'lib'
];
$include_path = array_unique(array_merge($LIBS, explode(PATH_SEPARATOR, ini_get('include_path'))));
ini_set('include_path', join(PATH_SEPARATOR, $include_path));

require_once('lib/stdlib.php');

