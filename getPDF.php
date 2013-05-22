<?php
require_once "../../common.php";
require_once 'class.latexbuild.php';

checkSession();

$latex = new CLatexBuild();

$latex->getPDF();


?>