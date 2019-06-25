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
        addToLog('Json was wrong for outlet\n');
        //$error[]="<p>Could not save json for outlet<p>";
    }

    addToLog('Running TauDEM to get watershed with selected outlet');
    if (false == $taudemfuncs->getWSfromOutlet($workingDir)) {
        addToLog('Error generating watershed with outlet');
        //$error[]="<p>Error generating watershed with outlet <p>";
    }

    addToLog('Processing demw to for display');
    if (false == $gdalfuncs->procDemwDispay($workingDir)) {
        addToLog("Error processing demw for display");
    }
    $zone = $_SESSION["SSVAR"]['utmzone'];  

    $scenarioarr = $_SESSION["SSVAR"]["runscenario"];    
    addToLog("Updating map files for watershed with outlet");
    $baseMapFile = $globwebdir . "olmap/ol_baseline_google.map";
    $mapfilefuncs->updateMapFileOlt($workingDir,"taudem.map",true,false,true,false,false,$zone,$baseMapFile, $scenarioarr);












?>
