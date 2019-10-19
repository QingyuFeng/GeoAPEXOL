<?php
// This block of code was called when the user pressed
// the setup apex model button after fld 
/*
**------------------------------------------------------------------
** Running all scenarios; Run scenario include:
** Updated Oct 1, 2019
** Process the gis layers, and then prepare the files based on 
** the scenarios. The reason was that, originally, I prepared 
** the scenarios by only modifying the land use. This caused 
** some problem since some parameters like Manning N and LUN 
** are determined based on land use, these need to be modified 
** correspondingly. So the original setup button was removed 
** and the code was merged into the run button.
** 1. Get the scenario name.
** 2. Process modification based on the scenario
** 3. Write rest files for apex
** 4. run apex
** 5. get the output files
**------------------------------------------------------------------
*/

$zone = $_SESSION["SSVAR"]['utmzone'];

// Land use layer for the APEX model
addToLog('Extracting landuse for the watershed: start');
if (false == $lufuncs->extLanduse2ws($workingDir)) {
    $_SESSION["SSVAR"]["ierrstep"] = 1;
    $_SESSION["SSVAR"]["msgerrstep"] = "ERROR: failed extracting landuse for the watershed!!!";
    return;
}
addToLog('Extracting landuse for the watershed: done');

// Land use layer for the APEX model
addToLog('Extracting Soil for the watershed: start');
if (false == $soilfuncs->extractSoil2ws($workingDir)) {
    $_SESSION["SSVAR"]["ierrstep"] = 1;
    $_SESSION["SSVAR"]["msgerrstep"] = "ERROR: failed extracting soil for the watershed!!!";
    return;
}
addToLog('Extracting soil for the watershed: done');

// Convert demw to shapefile, which will be used to get centroid.
addToLog('Processing demw to shapefile for centroid: start');
if (false == $gdalfuncs->demwTif2Shp($workingDir)) {
    $_SESSION["SSVAR"]["ierrstep"] = 1;
    $_SESSION["SSVAR"]["msgerrstep"] = "ERROR: failed processing demw to shapefile for centroid !!!";
    return;
}
addToLog('Processing demw to shapefile for centroid: done');

// Convert tif to ASC for processing
addToLog('Converting TIF to ASC: start');
if (false == $gdalfuncs->procTifs2Ascs($workingDir)){
    $_SESSION["SSVAR"]["ierrstep"] = 1;
    $_SESSION["SSVAR"]["msgerrstep"] = "ERROR: failed converting TIF to ASC!!!";
    return;
}
addToLog('Converting TIF to ASC: done');


if (false == $gdalfuncs->streamTif2Ascfld($workingDir)) {
    $_SESSION["SSVAR"]["ierrstep"] = 1;
    $_SESSION["SSVAR"]["msgerrstep"] = "ERROR: failed converting stream TIF to ASC!!!";
    return;
}


// Save scenario as a new file, which will be taken by a python script
// to read results from runs under all scenarios.
$scenarioarr = Null;
$scenarioarr = $_SESSION["SSVAR"]["runscenario"];

if (!TRUE == $apexfuncs->saveJSON2File($workingDir, $scenarioarr)) {
    $error[]= $apexfuncs->saveJSON2File($workingDir, $scenarioarr);
    return;
}

// Creating a new folder, generate all files, run the model, 
// and process the results to get tables and maps.
$noofsce = count($_SESSION["SSVAR"]["runscenario"])/2;

for($scid=0; $scid<$noofsce; $scid++) {
    $skey = 's'. (string)($scid+1);
    $runscenario = $_SESSION["SSVAR"]["runscenario"][$skey];

    // Create a folder for each scenario run
    $scerunfdname = "sce" . $runscenario;
    $scenrunfd = $workingDir . "/apexruns/" . $scerunfdname;
    if (!file_exists($scenrunfd)) {
        $genefuncs->deletefiles($scenrunfd);
    }

    mkdir($scenrunfd);

    // Running python script to store watershed information
    // into json files: mainly sub, site
    addToLog('processing ASC files to watershed subarea information and modify json file: start');
    if (false == $apexfuncs->preAPEXJSONFld($workingDir, $scerunfdname)) {
        $_SESSION["SSVAR"]["ierrstep"] = 1;
        $_SESSION["SSVAR"]["msgerrstep"] = "ERROR: failed processing ASC files to watershed subarea information and modify json file!!!";
        return;
    }
    addToLog('processing ASC files to watershed subarea information and modify json file: done');

    // Copy some json files under apexruns to the scenario folder
    // These include:
    // runsite.json
    // var1wsssub.json
    // wssubsollulatlon.json
    // runsub.json
    //if (false == $apexfuncs->copyJSONFILEStoSceFD($workingDir, $scerunfdname)) {
    //    $error[]="<p>Error copying json files to scenario folder<p>";
    //    return;
    //}


    if (false == $apexfuncs->writeSOL($workingDir, $Connection,$scerunfdname)) {
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
        if (false == $apexfuncs->installTile($workingDir, $tild,$scerunfdname)) {
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
    if (false == $apexfuncs->writeCLICOMs($workingDir,$scerunfdname)) {
        $error[]="<p>Error writting climate list files<p>";
        return;
    }

    if (false == $apexfuncs->writeCLIFILEs($workingDir, $Connection,$scerunfdname)) {
        $error[]="<p>Error writting climate list files<p>";
        return;
    }

    /*
    **------------------------------------------------------------------
    ** Generate site input: sitecom and site file
    **------------------------------------------------------------------
    */
    if (false == $apexfuncs->writeSITFLCOM($workingDir,$scerunfdname)) {
        $error[]="<p>Error writting site and list files<p>";
        return;
    }

    /* 
    **------------------------------------------------------------------
    ** Generate OPSCCOM.dat: list of management files
    **------------------------------------------------------------------
    */
    if (false == $apexfuncs->copyOPSFILE($workingDir,$scerunfdname)) {
        $error[]="<p>Error copying OPS files<p>";
        return;
    }

    /*
    **------------------------------------------------------------------
    ** Generate subarea input: subcom and sub file
    **------------------------------------------------------------------
    */
    if (false == $apexfuncs->writeSUBFLCOM($workingDir,$scerunfdname)) {
        $error[]="<p>Error writting sub and list files<p>";
        return;
    }

    /*
    **------------------------------------------------------------------
    ** Generate control file input
    **------------------------------------------------------------------
    */
    if (false == $apexfuncs->writeCONTFILE($workingDir,$scerunfdname)) {
        $error[]="<p>Error writting control files<p>";
        return;
    }


    // Do modification based on scenario
    if (!TRUE == $apexfuncs->makeScenarioOPCjson($workingDir, $scerunfdname)) {
        $error[]="<p>Error writting operation OPS list files<p>";
        return;
    }

    if (false == $apexfuncs->writeOPSCOM($workingDir, $scerunfdname)) {
        $error[]="<p>Error writting operation OPS list files<p>";
        return;
    }


    // Prepare files for each
    if (false == $apexfuncs->writeRUNFILE($workingDir, $scerunfdname)) {
        $error[]="<p>Error writting run files<p>";
        return;
    }

    /*
    **------------------------------------------------------------------
    ** Copy other necessary files
    **------------------------------------------------------------------
    */
    if (false == $apexfuncs->copyOTHERFILE($workingDir, $scerunfdname)) {
        $error[]="<p>Error copying other files<p>";
        return;
    }



    /*
    **------------------------------------------------------------------
    ** Run APEX model
    **------------------------------------------------------------------
    */
    if (false == $apexfuncs->runAPEX1501($workingDir, $scerunfdname)) {
        $error[]="<p>Error running the APEX model<p>";
        return;
    }
}

// After all runs, get the output from the runs, and generate maps
// There are two steps:
// 1. determine the output from all scenarios
// 2. determine the levels for reclassification
// 3. reclass the maps to desired levels.
// Determine the output of all scenarios

$funcreturn = $apexfuncs->getQSNPRECLevels($workingDir);
if (!TRUE == $funcreturn) {
        $error[]=$funcreturn;
        return;
    }

$funcreturn = $apexfuncs->reclaQSNPMaps($workingDir);
if (!TRUE == $funcreturn) {
        $error[]=$funcreturn;
        return;
    }

/*
**------------------------------------------------------------------
** Get the area weighted average values of q, se, n and p.
**------------------------------------------------------------------
*/

if (false == $gdalfuncs->ws2fldtif2asc($workingDir)) {
    $error[]="<p>Error Cutting watershed to field boundary and convert to asc.<p>";
    return;
}



if (!True == $apexfuncs->getWeightedQSNP($workingDir)) {
    $error[]="<p>Error Calculating the area weighted average qsnp values.<p>";
    return;
}


$scenarioarr = $_SESSION["SSVAR"]["runscenario"];
$TBQSNPArray = $apexfuncs->getQSNPArrayFld($workingDir, $scenarioarr);

/*
**------------------------------------------------------------------
** Get the output layers for the field area
**------------------------------------------------------------------
*/
$noofsce = count($_SESSION["SSVAR"]["runscenario"])/2;
for($scid=0; $scid<$noofsce; $scid++) {

    $skey = 's'. (string)($scid+1);
    $runscenario = $_SESSION["SSVAR"]["runscenario"][$skey];
    if (false == $gdalfuncs->mergecutQSNPmaps($workingDir, $runscenario)) {
        $error[]="<p>Error create output mapes for field area.<p>";
        return;
    }


}
/*
**------------------------------------------------------------------
** Modify map files for display
**------------------------------------------------------------------
*/

addToLog("Updating map files for watershed with field");
$baseMapFile = $globwebdir . "olmap/ol_baseline_google.map";
$mapfilefuncs->updateMapFileFld($workingDir,"taudem.map",true,true,true,true,false,true,$zone,$baseMapFile, $scenarioarr);







?>
