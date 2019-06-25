<?php
//
// log.php
// 
// Add information to wepp log file.
//
require_once("config.php");

function addToLog($msg)
{
	global $globwebdir, $globWorkRoot;
       $logfile = $globwebdir."log/log-" . date("Y-m-d") . ".txt";
       $fp = fopen($logfile,"a");
       if ($fp) {
          $date = strftime('%c');
          fwrite($fp,$date . "|" . session_id() . "|" . $msg . "\n");
          fclose($fp);
       }


       $logfile2 = $globWorkRoot.session_id()."/log-" . date("Y-m-d") . ".txt";
       $fp2 = fopen($logfile2,"a");
       if ($fp2) {
          $date2 = strftime('%c');
          fwrite($fp2,$date2 . "|" . session_id() . "|" . $msg . "\n");
          fclose($fp2);
       }




}
?>

