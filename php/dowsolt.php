<?php

// This block of code was called when the user pressed
// the Get Watershed button. 
//
// The code here will be included in the case: dowatershedolt
//
//

    addToLog('Saving outlet to geojson');

    $jsonutmolt = $_SESSION["SSVAR"]["jsot"];
    $fnoltjson = $workingDir.'/taudemlayers/outletpnt.json';
    if(file_exists($fnoltjson))
    {unlink($fnoltjson);}

    if (!is_null($jsonutmolt))
    {
        $file = fopen($fnoltjson,'w');
        fwrite($file, $jsonutmolt);
        fclose($file);
    }
    else
    {
        $_SESSION["SSVAR"]["ierrstep"] = 1;
        $_SESSION["SSVAR"]["msgerrstep"] = "ERROR: error generating json for outlet !!!";
        addToLog('Json was wrong for outlet\n');
        return;
    }

    addToLog('Running TauDEM to get watershed with selected outlet: start');
    if (false == $taudemfuncs->getWSfromOutlet($workingDir)) {
        $_SESSION["SSVAR"]["ierrstep"] = 1;
        $_SESSION["SSVAR"]["msgerrstep"] = "ERROR: failed generating watershed for outlet !!!";
        return;
    }
    addToLog('Running TauDEM to get watershed with selected outlet: done');

    addToLog('Processing demw to for display: start');
    if (false == $gdalfuncs->procDemwDispay($workingDir)) {
        $_SESSION["SSVAR"]["ierrstep"] = 1;
        $_SESSION["SSVAR"]["msgerrstep"] = "ERROR: failed processing dem for display !!!";
        return;
    }
    addToLog('Processing demw to for display: done');
    
    $zone = $_SESSION["SSVAR"]['utmzone'];  
    $scenarioarr = $_SESSION["SSVAR"]["runscenario"];    
    addToLog("Updating map files for watershed with outlet");
    $baseMapFile = $globwebdir . "olmap/ol_baseline_google.map";
    $mapfilefuncs->updateMapFileOlt($workingDir,"taudem.map",true,false,true,false,false,$zone,$baseMapFile, $scenarioarr);




?>
