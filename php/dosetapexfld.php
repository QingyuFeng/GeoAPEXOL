<?php
// This block of code was called when the user pressed
// the setup apex model button after fld 
$fdapexruns = $workingDir . '/apexruns/';
/*
**-----------------------------------------------------
** Processing information of subarea files
**-----------------------------------------------------
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

// Convert demw to shapefile, which will be used to get centroid.
addToLog('Processing demw to shapefile for centroid');
if (false == $gdalfuncs->demwTif2Shp($workingDir)) {
    $error[]="<p>Error converting the watershed(demw) from tif to shapefile <p>";
    return;
}

// Convert tif to ASC for processing
addToLog('Converting TIF to ASC');
if (false == $gdalfuncs->procTifs2Ascs($workingDir)) {
    $error[]="<p>Error converting TIF to ASC <p>";
    return;
}
if (false == $gdalfuncs->streamTif2Ascfld($workingDir)) {
    $error[]="<p>Error converting stream tif to ASC <p>";
    return;
}

// Running python script to store watershed information
// into json files: mainly sub, site
if (false == $apexfuncs->preAPEXJSONFld($workingDir)) {
    $error[]="<p> Error processing ASC files to watershed subarea information and modify json file<p>";
    return;
}







?>
