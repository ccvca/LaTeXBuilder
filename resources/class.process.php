<?php
/*
 * @copyright 2013
 * @author ccvca https://github.com/ccvca/
 * @licence http://creativecommons.org/licenses/by-nc-sa/3.0/de/ (EN: http://creativecommons.org/licenses/by-nc-sa/3.0/)
 * @compare with: http://de1.php.net/manual/de/function.exec.php#88704
 */

abstract class CProcess{
	protected $pid;
	protected $command;

	public function __construct($cl=false){
		$this->pid = false;
		if ($cl != false){
			$this->command = $cl;
			$this->runCom();
		}
	}
	
	abstract protected function runCom();

	public function setPid($pid){
		$this->pid = $pid;
	}

	public function getPid(){
		return $this->pid;
	}

	public function setCommand($command){
		$this->command = $command;
	}

	abstract public function status();

	public function start(){
		if ($this->command != ''){
			$this->runCom();
		}else{
			return true;
		}
	}

	abstract public function stop();
}

?>