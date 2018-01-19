<?php

abstract class Job {
	private $lockFileName;
	private $lockfd;

	public $file;
	
	protected $needLocked = false;
	
	protected $link = false;
	
	protected $ociLink = false;
	
	protected $argV;
	
	function __construct($argV = '') {
		$class = get_class($this);
		$this->file = APP_PATH."/schedules/logs/" . $class . "_" . date('Y-m-d') . ".log";
		$lockFilePrefix = array_filter(array($class, $argV ? md5(serialize($argV)) : '', '123', "job"));
		$this->lockFileName = APP_PATH."/schedules/tmp/" . implode('_', $lockFilePrefix). ".locker";
		$this->lockfd = fopen($this->lockFileName, 'w+');
		$this->argV = $argV;
                //
	}

	function log($message) {
		file_put_contents($this->file, getmypid() . " " . date('Y-m-d H:i:s') . $message . PHP_EOL, FILE_APPEND);
	}

	abstract function notified();
		
	function lock() {	//return：true——可执行，false——被锁
		if (!$this->needLocked) return true;
		return flock($this->lockfd, LOCK_EX|LOCK_NB);
	}
	
	function unlock() {
		fclose($this->lockfd);
		@unlink($this->lockFileName);
	}
}

?>