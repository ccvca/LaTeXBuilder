<?php
/* @copyright 2013
 * @author ccvca https://github.com/ccvca/
 * @licence http://creativecommons.org/licenses/by-nc-sa/3.0/de/ (EN: http://creativecommons.org/licenses/by-nc-sa/3.0/) 
 */

//The subdir in the Project were buildfiles are stored
DEFINE('BUILD_DIR', '.build');

$filepath = dirname(__FILE__);

require_once "config.php";
require_once COMPONENTS.'/filemanager/class.filemanager.php';
require_once $filepath.'/resources/class.processLinux.php';
require_once $filepath.'/resources/class.processWindows.php';

//TODO Don't store pid in SESSION, use something "global" for it, for different users



/**
 * @author ccvca https://github.com/ccvca/
 *
 */
class CLatexBuild{
	
	/**
	 * @var CProcess
	 */
	private $process;
	
	public function __construct(){
		
		if(stripos(PHP_OS, 'win') === false){
			//Unix System
			$this->process = new CProcessLinux();
			
		}else{
			$this->process = new CProcessWindows();
		}
		
		if(isset($_SESSION['latexbuild']) && isset($_SESSION['latexbuild']['pid'])){
			$this->process->setPid($_SESSION['latexbuild']['pid']);
		}
		
	}
	
	/**
	 * Build the PDF based on the main-file setting
	 * @return multitype:string 
	 */
	public function buildPDF(){
		if(!isAvailable('exec')){
			return array('status' => 'error', 'msg' => 'no exec() Command availiable.');
		}
		
		if(!($mainFile = $this->getMainFile())){
			return array('status' => 'error', 'msg' => 'No main file set.');
		}
		
		if(!$this->createBuildDir()){
			return array('status' => 'error', 'msg' => 'Could not create build directory.');
		}
		
		//check if process already running
		if($this->checkRunning() !== false){
			return array('status' => 'error', 'msg' => 'There is already a build running.');
		}
		
		chdir(WORKSPACE.'/'.$_SESSION['project'].'/'.BUILD_DIR);
		$pid = false;
		//need --c-style-errors for windows and -file-line-error for linux
		//--c-style-errors -file-line-error
		$cmd = 'pdflatex -synctex=1 --c-style-errors -file-line-error ';
		$cmd .= PDFLATEX_ARGS.' ';
		if(ENABLE_SHELL_ESCAPE){
			$cmd .= '--shell-escape --enable-write18 ';
		}
		$cmd .= '-output-directory '.escapeshellarg(WORKSPACE.'/'.$_SESSION['project'].'/'.BUILD_DIR);
		$cmd .= ' -interaction=nonstopmode '.escapeshellarg(escapeshellcmd('../'.$mainFile));
		
		$this->process->setCommand($cmd);
		
		$this->process->start();
		if($this->process->getPid() === false){
			return array('status' => 'error', 'msg' => 'Could not start process.');
		}
		$_SESSION['latexbuild']['pid'] = $this->process->getPid();
		
		return array('status' => 'success', 'msg' => 'Start Building PDF. Pid:'.$this->process->getPid());
	}
	
	
	/**
	 * check if there is currently a build-process runnig
	 * @return boolean
	 */
	public function checkRunning(){
		
		if($this->process->status()){
			//running
			return true;
		}else{
			//not running.
			//remove pid because it's not valid anymore
			unset($_SESSION['latexbuild']['pid']);
			return false;
		}
		
	}
	
	/**
	 * Set the given filename as main-File from where the build starts
	 * @param string $filename
	 * @return multitype:string 
	 */
	public function setMainTex($filename){
		
		$path_parts = explode('/', $filename);
		
		//array_shift($path_parts);
		//remove Project dir
		array_shift($path_parts);
		
		$mainPath = implode('/', $path_parts);
		
		//Read old files
		$mainFiles = @Common::getJSON(FILENAME_MAINTEXTFILES, 'latexbuild');
		$mainFiles[$_SESSION['project']] = $mainPath;
		Common::saveJSON(FILENAME_MAINTEXTFILES, $mainFiles, 'latexbuild');
		return array('status' => 'success', 'msg' => 'Set main file.');
	}
	
	/**
	 * Send the created PDF-file to the browser, on Error String is send to the browser.
	 */
	public function getPDF(){
		$mainFiles = @getJSON(FILENAME_MAINTEXTFILES, 'latexbuild');
		if(!isset($mainFiles[$_SESSION['project']])){
			//$this->sendPDF(COMPONENTS.'/latexbuild/nopdf.pdf');
			echo 'No main file.';
			return;
		}
		$fileName = pathinfo($mainFiles[$_SESSION['project']], PATHINFO_FILENAME);
		if(!file_exists(WORKSPACE.'/'.$_SESSION['project'].'/'.BUILD_DIR.'/'.$fileName.'.pdf')){
			//$this->sendPDF(COMPONENTS.'/latexbuild/nopdf.pdf');
			echo 'Not exist.'.WORKSPACE.'/'.$_SESSION['project'].'/'.BUILD_DIR.'/'.$fileName.'.pdf';
			return;
		}
		$this->sendFile(WORKSPACE.'/'.$_SESSION['project'].'/'.BUILD_DIR.'/'.$fileName.'.pdf');
	}
	
	/**
	 * Sends the file from $path and it's mime-type to the browser
	 * @param string $path
	 */
	private function sendFile($path){
		$finfo = new finfo(FILEINFO_MIME_TYPE);
		//var_dump($finfo);
		
		header("Content-type: ".$finfo->file($path));
		//echo file_get_contents(DOWNLOAD_DIR.$rFile);
		$fp = fopen($path, 'r');
		if($fp)
		{
			while(!feof($fp))
			{
				echo fread($fp, 8192);
			}
		}
	}
	
	/**
	 * Get the source-file and the line to a given 72dpi point in a page
	 * @param int $pageNum
	 * @param float $x
	 * @param float $y
	 * @return multitype:string
	 */
	public function getSyncTexFile($pageNum, $x, $y){
		if(!isAvailable('exec')){
			return array('status' => 'error', 'msg' => 'no exec() Command availiable.');
		}
		
		if(!($mainFile = $this->getMainFile())){
			return array('status' => 'error', 'msg' => 'No main file set.');
		}
		
		$fileName = pathinfo($mainFile, PATHINFO_FILENAME);
		if(!file_exists(WORKSPACE.'/'.$_SESSION['project'].'/'.BUILD_DIR.'/'.$fileName.'.pdf')){
			return array('status' => 'error', 'msg' => 'No pdf file found.');
		}
		
		chdir(WORKSPACE.'/'.$_SESSION['project'].'/'.BUILD_DIR);
		$lines = array();
		exec('synctex edit -o '.$pageNum.':'.$x.':'.$y.':'.$fileName.'.pdf', $lines);
		$parts = array();

		if(count($lines) < 8){
			return array('status' => 'error', 'msg' => 'Bad SyncTeX result:'.implode('\n', $lines));
		}
		/*
		This is SyncTeX command line utility, version 1.2
		SyncTeX result begin
		Output:main.pdf
		Input:C:/portable\xampp\htdocs\CodiadLaTeX\workspace\test\main.tex
		Line:91
		Column:-1
		Offset:0
		Context:
		Output:main.pdf
		Input:C:/portable\xampp\htdocs\CodiadLaTeX\workspace\test\main.tex
		Line:90
		Column:-1
		Offset:0
		Context:
		SyncTeX result end
		 */
		$part['output'] = $this->getParamFromSyncTexLine($lines[2]);
		$part['input'] = $this->getParamFromSyncTexLine($lines[3]);
		$part['line'] = $this->getParamFromSyncTexLine($lines[4]);
		$part['column'] = $this->getParamFromSyncTexLine($lines[5]);
		$part['offset'] = $this->getParamFromSyncTexLine($lines[6]);
		$part['context'] = $this->getParamFromSyncTexLine($lines[7]);
		
		$part['relative'] = $this->absPathToRelative($part['input'], WORKSPACE);
		
		return array_merge(array('status' => 'success', 'msg' => 'Get SyncTeX data successfull'), $part );
	}
	
	/**
	 * Convert a absPath to a relative one based on reference, 
	 * if absPath is not inside reference, NULL wil be returned.
	 * @param string $path
	 * @param string $reference
	 * @return mixed|NULL
	 */
	private function absPathToRelative($path, $reference){
		$referencePath = realpath($reference);
		$inputPath = realpath($path);
		
		$firstPart = substr($inputPath, 0, strlen($referencePath));
		
		if($firstPart == $referencePath){
			//strlen + 1 to remove the / at the beginning
			return str_replace('\\', '/', substr($inputPath, strlen($firstPart) + 1));
		}else{
			return null;
		}
	}
	
	/**
	 * Extract the Value from a SyncTeX line
	 * @param string $line
	 * @return Ambigous <>
	 */
	private function getParamFromSyncTexLine($line){
		$ex = explode(':', $line, 2);
		return $ex[1];
	}
	
	/**
	 * Get the errors and warnings occure during the last build.
	 * @return multitype:string |multitype:string multitype:multitype:string
	 */
	public function getErrorsFromLog(){
		if(!($mainFile = $this->getMainFile())){
			return array('status' => 'error', 'msg' => 'No main file set.');
		}
		
		$fileName = pathinfo($mainFile, PATHINFO_FILENAME);
		if(!file_exists(WORKSPACE.'/'.$_SESSION['project'].'/'.BUILD_DIR.'/'.$fileName.'.log')){
			return array('status' => 'error', 'msg' => 'No log file found.');
		}
		
		$lines = file(WORKSPACE.'/'.$_SESSION['project'].'/'.BUILD_DIR.'/'.$fileName.'.log');
		$lines = $this->repairLineEndings($lines);
		
		$found = array();
		$matches = array();
		foreach($lines as $line){
			if(preg_match('#^(.+):([0-9]+):(.+)$#', $line, $matches)){
				$found[] = $matches;
			}
		}
		
		$ret = array();
		foreach($found as $fo){
			$ret[] = array( 'absPath' => $fo[1],
					'line' => $fo[2],
					'errormsg' => $fo[3],
					'relPath' => $this->absPathToRelative($fo[1], WORKSPACE));
		}
		if(count($ret) == 0){
			return array('status' => 'success', 'msg' => 'No LaTeX errors found.');
		}else{
			return array('status' => 'warning', 'msg' => 'There are LaTeX errors', 'errors' => $ret);
		}
	}
	
	/**
	 * This function try to fix all the line breaks after $width chars
	 * @param multitype:string $lines
	 * @param int $width
	 * @return multitype:string 
	 */
	private function repairLineEndings($lines, $width = DEFAULT_LINE_WIDTH){
		$culine = null;
		$i = 0;
		$ret = array();
		foreach($lines as $line){
			$line = trim($line);
			if($culine === null){
				$culine = $line;
			}else{
				$culine .= $line;
			}
			if(strlen($line) < ($width - 1)){
				$ret[] = $culine;
				$culine = null; 
			}
		}
		
		if($culine !== null){
			$ret[] = $culine;
		}
		
		return $ret;
	}
	
	/**
	 * Get the main file from the stored file, false on error
	 * @return boolean|string
	 */
	public function getMainFile(){
		if(!$this->createBuildDir()){
			return false;
		}
		
		$mainFiles = @Common::getJSON(FILENAME_MAINTEXTFILES, 'latexbuild');
		if(!isset($mainFiles[$_SESSION['project']])){
			return false;
		}
		
		return $mainFiles[$_SESSION['project']];
	}
	
	/**
	 * Check if the build-dir is availiable if not it will be created
	 * @return boolean
	 */
	private function createBuildDir(){
		if(!file_exists(WORKSPACE.'/'.$_SESSION['project'].'/'.BUILD_DIR)
				&& (is_dir(WORKSPACE.'/'.$_SESSION['project'].'/'.BUILD_DIR)
						|| !mkdir(WORKSPACE.'/'.$_SESSION['project'].'/'.BUILD_DIR))){
			return false;
		}else{
			return true;
		}
		
	}
}

?>