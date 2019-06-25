<?php
//
// globals.php
// 
// These should be used in php code to make moving to other machines easier.
//
// 
//$myip = $_SERVER['REMOTE_ADDR'];
//$globip = $_SERVER['SERVER_ADDR'];
//$website = "NSERL";

// Tmp/session folder
// This folder: tmp and sessions need to be owned by www-data
// if not use: sudo chown -R www-data:www-data tmp to change if needed
defined("FD_TMPSESSION")
or define("FD_TMPSESSION", realpath('/var/www/html/' . FDAPP . '/tmp/sessions'));

// Define default json project file
defined("JS_SSVARJSON")
or define("JS_SSVARJSON", '/var/www/html/' . FDAPP . '/json/sessionvars.json');






if (isset($_SESSION['USE_ENG_UNITS']))
{$globEngUnits = 1;}
else
{$globEngUnits = 0;}

if (isset($_SESSION['USE_FOREST_SOILS']))
{$globForestSoils = 1;}
else
{$globForestSoils = 0;}

if (isset($_SESSION['USE_FOREST_LANDUSE']))
{$globForestLanduse = 1;}
else
{$globForestLanduse = 0;}

$globtmpdir = "/home/apex/tmp/";
$globgisdir = "/home/gis/";
$globwebdir = "/var/www/html/". FDAPP ."/";
$mapserv = "http://127.0.0.1:8000/cgi-bin/mapserv?";
$globConnectString = "host=localhost dbname=apexwebinput user=postgres password=nserl";
$globPythonBin = "/usr/bin/";
$globUnits = 0;
$globWorkRoot = "/home/apex/usrruns_webgis/";
$globStaticMap = "/var/www/html/" . FDAPP . "/olmap/ol_static_google.map";



?>
