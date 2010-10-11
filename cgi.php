#!/usr/bin/php-cgi
<?
ini_set('max_execution_time','172800');
ini_set('memory_limit','2048M');

if(!empty($_SERVER['REMOTE_ADDR'])){
	exit;
}

define("IS_CGI",1);

include("index.php");

?>