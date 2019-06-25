<?php
// This block of code was called when the user pressed
// the setup apex model button after olt
/*
**----------------------------------------------------
** Processing subarea files
**----------------------------------------------------
*/
// Generating soil for a watershed is different from
// for a single field. For a single field, there is only
// one soil. For a watershed, soils covered by an area need
// to be get.
// NRCS has a function to do this. The code was written by
// Jim Frankenberger and it was used here.
//
$zone = $_SESSION["SSVAR"]['utmzone'];

addToLog('Extracting soils for the watershed');
if (false == $soilfuncs->getSurgoGeometry($zone,$workingDir)) {
    $error[]="<p>Error getting ssurgo soil for the watershed <p>";
    return;
}

// Land use layer for the APEX model
addToLog('Extracting landuse for the watershed');
if (false == $lufuncs->getLanduse($workingDir)) {
    $error[]="<p>Error extracting land use for the watershed <p>";
    return;
}

// Convert tif to ASC for processing 
addToLog('Converting TIF to ASC');
if (false == $gdalfuncs->procTifs2Ascs($workingDir)) {
    $error[]="<p>Error converting TIF to ASC <p>";
    return;
}
if (false == $gdalfuncs->streamTif2Ascolt($workingDir)) {
    $error[]="<p>Error converting stream  to ASC <p>";
    return;
}

// Running python script to modify json files
if (false == $apexfuncs->preAPEXJSONOlt($workingDir)) {
    $error[]="<p> Error processing ASC files to watershed subarea information and modify json file<p>";
    return;
}


?>
