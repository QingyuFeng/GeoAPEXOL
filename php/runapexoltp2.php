<?php
// This block of code was called when the user pressed
// the setup apex model button after fld 
/*
**------------------------------------------------------------------
** Running all scenarios; Run scenario include:
** 1. Get the scenario name.
** 2. Process modification based on the scenario
** 3. Write rest files for apex
** 4. run apex
** 5. get the output files
**------------------------------------------------------------------
 */
$noofsce = count($_SESSION["SSVAR"]["runscenario"])/2;
for($scid=0; $scid<$noofsce; $scid++) {

    $skey = 's'. (string)($scid+1);
    $runscenario = $_SESSION["SSVAR"]["runscenario"][$skey];
    // Do modification based on scenario
    if (!TRUE == $apexfuncs->makeScenarioOPCjson($workingDir, $runscenario)) {
        $error[]="<p>Error writting operation OPS list files<p>";
        return;
    }

    if (false == $apexfuncs->writeOPSCOM($workingDir, $runscenario)) {
        $error[]="<p>Error writting operation OPS list files<p>";
        return;
    }


    // Prepare files for each
    if (false == $apexfuncs->writeRUNFILE($workingDir, $runscenario)) {
        $error[]="<p>Error writting run files<p>";
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

}

// After all runs, get the output from the runs, and generate maps
// There are two steps: 
// 1. determine the output from all scenarios
// 2. determine the levels for reclassification
// 3. reclass the maps to desired levels.
// Determine the output of all scenarios
//
// Save scenario as a new file, which will be taken by a python script
// to read results from runs under all scenarios.
$scenarioarr = $_SESSION["SSVAR"]["runscenario"];

if (!TRUE == $apexfuncs->saveJSON2File($workingDir, $scenarioarr)) {
    $error[]= $apexfuncs->saveJSON2File($workingDir, $scenarioarr);
    return;
}



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
** Store the output for all subareas under all scenarios for table
**------------------------------------------------------------------
*/

$scenarioarr = $_SESSION["SSVAR"]["runscenario"];
$TBQSNPArray = $apexfuncs->getQSNPArrayOlt($workingDir, $scenarioarr);
/*
**------------------------------------------------------------------
** Modify map files for display
**------------------------------------------------------------------
*/
$zone = $_SESSION["SSVAR"]['utmzone'];

addToLog("Updating map files for watershed with outlet");
$baseMapFile = $globwebdir . "olmap/ol_baseline_google.map";
$mapfilefuncs->updateMapFileOlt($workingDir,"taudem.map",true,true,true,false,false,$zone,$baseMapFile, $scenarioarr);






?>
