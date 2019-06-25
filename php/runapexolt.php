<?php
// This block of code was called when the user pressed
// the setup apex model button after fld 

$zone = $_SESSION["SSVAR"]['utmzone'];
/*
**-----------------------------------------------------
** Process the soil files: this write first since we need
** the information to modify the sub json for tile drainage
** installation
**-----------------------------------------------------
*/

addToLog('Writing soil files for running APEX model');
if (false == $apexfuncs->writeSOL($workingDir, $Connection)) {
    $error[]="<p>Error Writing soil files for running APEX model<p>";
    return;
}

// Tile drainage installation is based on hydrological 
// soil group of the soil in the subarea. If it is c or d,
// a tile will be installed according to the user inputed
// value.
// TODO: The tile value need to be checked and should be
// between 0 and 2m.
$tild = $_SESSION["SSVAR"]["tiledep"];
if (strcmp($tild, 'Tile_not_installed') != 0)
{
    echo('installing tile drainage');
    if (false == $apexfuncs->installTile($workingDir, $tild)) {
    $error[]="<p>Error installing tile drainage<p>";
    return;
    }
}


/*
**------------------------------------------------------------------
** Generate weather input user input
**------------------------------------------------------------------
*/	
//There are several files to be prepared.
// 1. Monthly weather datafile. This have to be prepared
// 2. Wind files, prepared
// 3. Daily observed files: depends on user selection
// All data are now in the database. The job here include:
// a. Decide the station name based on latitude and longitude.
// b. extract data and write them into format required by APEX.
if (false == $apexfuncs->writeCLICOMs($workingDir)) {
    $error[]="<p>Error writting climate list files<p>";
    return;
}

if (false == $apexfuncs->writeCLIFILEs($workingDir, $Connection)) {
    $error[]="<p>Error writting climate list files<p>";
    return;
}


/*
**------------------------------------------------------------------
** Generate site input: sitecom and site file
**------------------------------------------------------------------
*/
if (false == $apexfuncs->writeSITFLCOM($workingDir)) {
    $error[]="<p>Error writting site and list files<p>";
    return;
}

/*
**------------------------------------------------------------------
** Generate OPSCCOM.dat: list of management files 
**------------------------------------------------------------------
 */
if (false == $apexfuncs->writeOPSCOM($workingDir)) {
    $error[]="<p>Error writting operation OPS list files<p>";
    return;
}

if (false == $apexfuncs->copyOPSFILE($workingDir)) {
    $error[]="<p>Error copying OPS files<p>";
    return;
}

/*
**------------------------------------------------------------------
** Generate subarea input: subcom and sub file
**------------------------------------------------------------------
*/
if (false == $apexfuncs->writeSUBFLCOM($workingDir)) {
    $error[]="<p>Error writting sub and list files<p>";
    return;
}


/*
**------------------------------------------------------------------
** Generate run file input
**------------------------------------------------------------------
 */
// The runs are differentiated by run name.
$runscenario = $_SESSION["SSVAR"]["runscenario"]["s1"];

if (false == $apexfuncs->writeRUNFILE($workingDir, $runscenario)) {
    $error[]="<p>Error writting run files<p>";
    return;
}

/*
**------------------------------------------------------------------
** Generate control file input
**------------------------------------------------------------------
*/
if (false == $apexfuncs->writeCONTFILE($workingDir)) {
    $error[]="<p>Error writting control files<p>";
    return;
}


/*
**------------------------------------------------------------------
** Copy other necessary files 
**------------------------------------------------------------------
*/
if (false == $apexfuncs->copyOTHERFILE($workingDir)) {
    $error[]="<p>Error copying other files<p>";
    return;
}


/*
**------------------------------------------------------------------
** Run APEX model 
**------------------------------------------------------------------
*/
if (false == $apexfuncs->runAPEX1501($workingDir)) {
    $error[]="<p>Error running the APEX model<p>";
    return;
}

/*
**------------------------------------------------------------------
** Get ASA (Annual subarea file) and make maps for surface runoff (q)
** soil erosion (rsl2), total nitrogen, total phosphorus.
**------------------------------------------------------------------
*/

if (false == $apexfuncs->makeASA2map($workingDir, $runscenario)) {
    $error[]="<p>Error retrieving output from ASA and generating maps.<p>";
    return;
}


/*
**------------------------------------------------------------------
** Store the output for all subareas under all scenarios for table
**------------------------------------------------------------------
*/

$scenarioarr = $_SESSION["SSVAR"]["runscenario"];
$TBQSNPArray = $apexfuncs->getQSNPArrayOlt($workingDir, $scenarioarr);


/*
**------------------------------------------------------------------
** Create legend figure
**------------------------------------------------------------------
*/

if (false == $apexfuncs->createLgdFig($workingDir)) {
    $error[]="<p>Error Generating Legend Figure.<p>";
    return;
}




/*
**------------------------------------------------------------------
** Modify map files for display
**------------------------------------------------------------------
*/


addToLog("Updating map files for watershed with outlet");
$baseMapFile = $globwebdir . "olmap/ol_baseline_google.map";
$mapfilefuncs->updateMapFileOlt($workingDir,"taudem.map",true,true,true,false,false,$zone,$baseMapFile, $scenarioarr);







?>
