<?php
//定制错误信息级别
error_reporting(E_ERROR | E_PARSE);
require_once(dirname(dirname(__FILE__)). '/schedules/init.php');
function job_autoload($class) {
	$class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
	if (substr($class, -3) === 'Job') {
		require_once (SITE_PATH."/app/schedules/job/{$class}.php");
	}
}
spl_autoload_register('job_autoload');
if (isset($argv[1]) && strpos($argv[1], 'Job')) {	//单一脚本
	$class = $argv[1];
	if (count($argv) > 3) {
		$options = array();
		$key = '';
		for ($i = 2; $i < count($argv); $i++) {
			if ($i % 2 == 0) {
				$key = ($argv[$i][0] == '-') ? substr($argv[$i], 1) : $argv[$i];
			} else {
				$options[$key] = $argv[$i];
			}
		}
		$job = new $class($options);
	} elseif (count($argv) == 3) {
		$job = new $class($argv[2]);
	} else {
		$job = new $class();
	}
	$gid = getmypid();
	echo $gid ." => {$class}\tStart @ ".date('Y-m-d H:i:s ')."\n";
	if (!$job->lock()) {
		echo $gid ." => ". $class . " is LOCKED now, skipped.\n";
	} else {
		$job->notified();
		$job->unlock();
	}
	echo "\n". $gid ." => {$class}\tStop @ ".date('Y-m-d H:i:s ') . "\tMemory(now/top): " . memory_get_usage() . '/' . memory_get_peak_usage() . "\n";
} else {
	echo 'no arg is wrong!!!';
}

