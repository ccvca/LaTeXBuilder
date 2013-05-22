<?php
/*
 * @copyright 2013
 * @author ccvca https://github.com/ccvca/
 * @licence http://creativecommons.org/licenses/by-nc-sa/3.0/de/ (EN: http://creativecommons.org/licenses/by-nc-sa/3.0/)
 */
require_once "../../common.php";
require_once 'class.latexbuild.php';

Common::checkSession();


$latex = new CLatexBuild();

if(!isset($_POST['action']) || !isset($_SESSION['project']) || trim($_SESSION['project']) == ''){
	die(json_encode(array('status' => 'false') ));
}
switch($_POST['action']){
	case 'setMainTex':{
		if(!isset($_POST['filename']) || trim($_POST['filename']) == ''){
			die(json_encode(array('status' => 'error', 'msg' => 'Missing Filename') ));
		}
		if($_POST['filename'] == 'null'){
			die(json_encode(array('status' => 'error', 'msg' => 'No file active.') ));
		}
		$filename = trim($_POST['filename']);
		//Remove .. 
		$filename = Filemanager::cleanPath($filename);
		
		//check if filename starts with the ProjectDir
		$pos = stripos($filename, $_SESSION['project']);
		if($pos === false || $pos > 1){
			die(json_encode(array('status' => 'error', 'msg' => 'Wrong Filename') ));
		}
		
		if(!file_exists(WORKSPACE.'/'.$filename)){
			die(json_encode(array('status' => 'error', 'msg' => 'File doesn\'t exists.') ));
		}
		
		echo json_encode($latex->setMainTex($filename));
		break;
	}
	case 'buildPDF':
		echo json_encode($latex->buildPDF());
		break;
	case 'checkRunning':
		if($latex->checkRunning()){
			//Running
			echo json_encode(array('status' => 'success', 'msg' => 'There is still a buildprocess running.'));
		}else{
			//not running
			echo json_encode(array('status' => 'notice', 'msg' => 'No Process running.'));
		}
		break;
	case 'SyncTeXGetFile':
		/*
			'action' : 'SyncTeXGetFile',
			'realX' : realX,
			'realY' : realY,
			'pageNum'  : pageNum
		*/
		//TODO SyncTex request
		if(!isset($_POST['realX']) || !isset($_POST['realY']) || !isset($_POST['pageNum'])){
			die(json_encode(array('status' => 'error', 'msg' => 'Unknown misssing parameters for Synctex.') ));
		}
		//convert the $_POST to a injection-save format
		$pageNum = (int) $_POST['pageNum'];
		$realX = (float) $_POST['realX'];
		$realY = (float) $_POST['realX'];
		
		echo json_encode($latex->getSyncTexFile($pageNum, $realX, $realY));
		break;
	case 'getLaTeXErrors':
		echo json_encode($latex->getErrorsFromLog());
		break;
	default:{
		die(json_encode(array('status' => 'error', 'msg' => 'Unknown action.') ));
	}
}
?>