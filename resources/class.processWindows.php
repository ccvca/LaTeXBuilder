<?php

/*
 * @copyright 2013
 * @author ccvca https://github.com/ccvca/
 * @licence http://creativecommons.org/licenses/by-nc-sa/3.0/de/ (EN: http://creativecommons.org/licenses/by-nc-sa/3.0/)
 * @compare with: http://de1.php.net/manual/de/function.exec.php#88704
 */

require_once 'class.process.php';

class CProcessWindows extends CProcess{

	public function __construct($cl=false){
		if(stripos(PHP_OS, 'win') === false){
			throw new Exception('Using Windows process class on Linux.');
		}
		 
		$this->pid = false;
		if ($cl != false){
			$this->command = $cl;
			$this->runCom();
		}
	}
	protected function runCom(){
		exec('wmic process call create '.escapeshellarg($this->command), $output);
		
		//search for this line
		//        ProcessId = 9032;
		foreach($output as $line){
			if(stripos($line, 'ProcessId = ') !== false){
				$part = strstr($line, '= ');
				$this->pid = substr($part, 2, -1);
			}
		}
		
	}

	public function status(){
		if($this->pid === false){
			//No PID, so no pos process is running
			return false;
		}
		
		//tasklist /FI "PID eq 3864" /FO CSV
		exec('tasklist /FI '.escapeshellarg('PID eq '.$this->pid).' /FO CSV', $output);
			
		if(count($output) < 2){
			//Only the line that the process isn't running is in output
			//remove pid, because it's not valid anymore
			$this->pid = false;
			
			return false;
		}else{
			//Information about the process
			/*$desc = str_getcsv($output[0], ',');
			$cont = str_getcsv($output[1], ',');
			$ret = '';
			foreach($desc as $key => $val){
				$ret .= $desc[$key].': '.$cont[$key].'  ';
			}*/
			return true;
		}
	}

	public function stop(){
		//TASKKILL /PID 1230 /PID 1241 /PID 1253 /T
		$command = 'taskkill /PID'.escapeshellarg($this->pid).' /T';
		exec($command);
		if ($this->status() == false)return true;
		else return false;
	}
}

?>