<?php

require "class.process.php";

/* An easy way to keep in track of external processes.
 * Ever wanted to execute a process in php, but you still wanted to have somewhat controll of the process ? Well.. This is a way of doing it.
 * @compability: Linux only. (Windows does not work).
 * @author: Peec, revised by Christian von Arnim
 * @source: http://de1.php.net/manual/de/function.exec.php#88704
 */
class CProcessLinux extends CProcess{
    public function __construct($cl=false){
    	if(stripos(PHP_OS, 'win') !== false){
    		throw new Exception('Using Linux process class on Windows.');
    	}
    	
    	$this->pid = false;
        if ($cl != false){
            $this->command = $cl;
            $this->runCom();
        }
    }
    protected function runCom(){
        $command = 'nohup '.$this->command.' > /dev/null 2>&1 & echo $!';
        exec($command ,$op);
        $this->pid = (int)$op[0];
    }

    public function status(){
    	if($this->pid === false){
    		//No PID, so no pos process is running
    		return false;
    	}
    	//Using ps -Flp for more information
        $command = 'ps -p '.$this->pid;
        exec($command,$op);
        if (!isset($op[1])){
        	//Not a valid pid, so it could be removed
        	$this->pid = false;
        	return false;
        }else{
        	return true;
        }
    }

    public function stop(){
        $command = 'kill '.$this->pid;
        exec($command);
        if ($this->status() == false)return true;
        else return false;
    }
}
?>