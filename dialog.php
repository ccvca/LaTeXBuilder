<?php
/*
 * @copyright 2013
 * @author ccvca https://github.com/ccvca/
 * @licence http://creativecommons.org/licenses/by-nc-sa/3.0/de/ (EN: http://creativecommons.org/licenses/by-nc-sa/3.0/)
*/

require_once "../../common.php";
require_once "class.latexbuild.php";
$latex = new CLatexBuild();

Common::checkSession();

/*if(!file_exists('../../data/latexbuild')){
	if(!mkdir('../../data/latexbuild', 0600, true)){
		die('could not create /data/latexbuild');
	}
}*/

if($mainFile = $latex->getMainFile()){
	echo 'Current mainfile: '.$mainFile."<br/>\n";
	
	//Look for LaTex Errors during the last build
	$laTexErrors = $latex->getErrorsFromLog();
	if($laTexErrors['status'] == 'success'){
		echo 'No LaTeX errors during the last build.'."<br/>\n";
	}else if($laTexErrors['status'] == 'warning'){
		//Display a table for the errors
		echo '<div id="LaTeXerrorDiv"><table>';
			echo '<thead><th>Path</th> <th>Line</th> <th>Error (Doubleclick to go to the file)</th></thead>';
			foreach($laTexErrors['errors'] as $error){
				$path = $error['relPath'] === null ? $error['absPath'] : $error['relPath'];
				$isAbsPath = $error['relPath'] === null;
				//files out of the workspace can't be opend
				if($isAbsPath){
					echo '<tr>';
				}else{
					echo '<tr ondblclick="codiad.latexbuild.gotoFile(\''.$path.'\', '.$error['line'].'); codiad.modal.unload();" >';
				}
					echo '<td>';
						echo htmlentities($path);
					echo '</td>';
					echo '<td>';
						echo $error['line'];
					echo '</td>';
					echo '<td>';
						echo htmlentities($error['errormsg']);
					echo '</td>';
				echo '</tr>';
			}
		echo '</table></div>';
	}
}else{
	echo 'No current mainfile set.'."<br/>\n";
}


?>
<button class="btn-left" onclick="codiad.latexbuild.setMainTex()">Set Current File to main Tex</button>
<button class="btn-mid" onclick="codiad.latexbuild.buildPDF()">BuildPDF</button>
<button class="btn-mid" onclick="codiad.latexbuild.checkRunning()">CheckRunning</button>
<button class="btn-right" onclick="codiad.latexbuild.openPDF()">OpenPDF</button>
<button class="right" onclick="codiad.modal.unload();"><?php i18n("Close"); ?></button>
