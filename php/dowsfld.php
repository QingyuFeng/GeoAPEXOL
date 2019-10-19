<?php

// This block of code was called when the user pressed
// the Get Watershed button. 
//
// The code here will be included in the case: dowatershedfld
//
    $imgextsp = $_SESSION["SSVAR"]["extent"];
    $imgextsp = str_replace(",", " ", $imgextsp);
    sscanf($imgextsp, "%f %f %f %f", $XLL, $YLL, $XUR, $YUR);
    
    $_SESSION["SSVAR"]['extxll'] = $XLL;
    $_SESSION["SSVAR"]['extyll'] = $YLL;
    $_SESSION["SSVAR"]['extxur'] = $XUR;
    $_SESSION["SSVAR"]['extyur'] = $YUR;
  
    // If the user directly zoom to without using go button
    // calculate the longitude and latitude as the center
    // of the extent
    if ($_SESSION["SSVAR"]["izoom"] == 0)
    {
        $_SESSION["SSVAR"]["latitude"] = $YLL + ($YUR - $YLL)/2.0;
        $_SESSION["SSVAR"]["longitude"] =$XLL + ($XUR - $XLL)/2.0;
    }
    
    // Adding a buffer
    $bufferdegree = $_SESSION["SSVAR"]["fldbfr"];
    $XLLBFR = $XLL - $bufferdegree; 
    $YLLBFR = $YLL - $bufferdegree;
    $XURBFR = $XUR + $bufferdegree;
    $YURBFR = $YUR + $bufferdegree;
    //echo('<br><br>');
    //  echo('xll: '.$XLL.' yll: '.$YLL.' xur: '.$XUR.' yur'.$YUR);
//  echo('<br><br>');
//  echo('xll: '.$XLLBFR.' yll: '.$YLLBFR.' xur: '.$XURBFR.' yur'.$YURBFR);
//  echo('<br><br>');

    // Update the extent to view the new extent
    $_SESSION["SSVAR"]["extent"] = $XLLBFR.','.$YLLBFR.','.$XURBFR.','.$YURBFR;
    $imgextsp = $_SESSION["SSVAR"]["extent"];
    $imgextsp = str_replace(",", " ", $imgextsp);
    //echo($_SESSION["SSVAR"]["extent"]);

    

    addToLog('Saving field boundary to geojson');
    $fnfldjson = $workingDir.'/fieldbdy.json';
    $jsonutmfld = $_SESSION["SSVAR"]["jsfd"]; 
    if (!is_null($jsonutmfld))
    {
        $file = fopen($fnfldjson,'w');
                fwrite($file, $jsonutmfld);
                fclose($file);
    }
    else
    {
        $_SESSION["SSVAR"]["ierrstep"] = 1;
        $_SESSION["SSVAR"]["msgerrstep"] = "ERROR: Could not save field json file!!!";
        return;
    }

    $_SESSION["SSVAR"]['utmzone'] = floor(((($XLL + $XUR)/2.0)/6) + 31);
    $zone = $_SESSION["SSVAR"]['utmzone'];  
    //echo("<br><br> zone: " . $zone. "<br><br>");
    // Extract NED, reproject and update the map files
    // Extract DEM
    $hasProj = TRUE;

    addToLog("Extracting DEM: start!");
    if ($gdalfuncs->extractNED("IN",$workingDir,$imgextsp,$hasProj)==FALSE)
    {
        $_SESSION["SSVAR"]["ierrstep"] = 1;
        $_SESSION["SSVAR"]["msgerrstep"] = "ERROR: failed building DEM Input !!!";
        return;
    }
    addToLog("Extracting DEM: done!");
    // Reproject NED to UTM coordinates

    addToLog("Extracting NASS: start!");
    if ($lufuncs->extractLU2ext($workingDir,$imgextsp)==FALSE)
    {
        $_SESSION["SSVAR"]["ierrstep"] = 1;
        $_SESSION["SSVAR"]["msgerrstep"] = "ERROR: Error Building NASS Input !!!";
        return;
    }
    addToLog("Extracting NASS: done!");

    addToLog("Extracting SSURGO: start!");
    if ($soilfuncs->extractSOIL2ext($workingDir,$imgextsp, $zone)==FALSE)
    {
        $_SESSION["SSVAR"]["ierrstep"] = 1;
        $_SESSION["SSVAR"]["msgerrstep"] = "ERROR: Error Building SOIL Input !!!";
        return;
    }
    addToLog("Extracting SSURGO: done!");
    
    addToLog("Reprojecting DEM: start!");
    if ($gdalfuncs->reprojectNED($workingDir,$zone,$hasProj)==FALSE)
    {
        $_SESSION["SSVAR"]["ierrstep"] = 1;
        $_SESSION["SSVAR"]["msgerrstep"] = "ERROR: reprojecting DEM !!!";
        return;
    }
    addToLog("Reprojecting DEM: done!");

    // Reproject land use to UTM coordinates
    addToLog("Reprojecting landuse: start");
    if ($lufuncs->reprojectLU2UTM($workingDir,$zone,$hasProj)==FALSE)
    {
        $_SESSION["SSVAR"]["ierrstep"] = 1;
        $_SESSION["SSVAR"]["msgerrstep"] = "ERROR: Error reprojecting landuse Input !!!";
        return;
    }
    addToLog("Reprojecting landuse: done");


    // Reproject soil to UTM coordinates
    addToLog("Reprojecting soil: start");
    if ($soilfuncs->reprojectSoil2UTM($workingDir,$zone,$hasProj)==FALSE)
    {
        $_SESSION["SSVAR"]["ierrstep"] = 1;
        $_SESSION["SSVAR"]["msgerrstep"] = "ERROR: Error reprojecting soil Input !!!";
        return;
    }
    addToLog("Reprojecting soil: done");

    // Running TauDEM to generate stream network
    addToLog("Generating stream network: start");
    if ($taudemfuncs->getStreamNetwork($workingDir, $_SESSION["SSVAR"]["criticalarea"], $_SESSION["SSVAR"]["demcellsize"])==FALSE)
    {
        $_SESSION["SSVAR"]["ierrstep"] = 1;
        $_SESSION["SSVAR"]["msgerrstep"] = "ERROR: Error failed generating streamnet work !!!";
        return;
    }
    addToLog("Generating stream network: done");

    addToLog('getting watersheds for field boundary: start');
    if (false == $taudemfuncs->getWSforExtent($workingDir,$zone)) {
        $_SESSION["SSVAR"]["ierrstep"] = 1;
        $_SESSION["SSVAR"]["msgerrstep"] = "ERROR: failed generating watershed for field boundary !!!";
        return;
    }
    addToLog('getting watersheds for field boundary: done');


    addToLog('Convert field boundary from json to shapefile: start');
    if (false == $gdalfuncs->fldJSONToShp($workingDir,$zone)) {
        $_SESSION["SSVAR"]["ierrstep"] = 1;
        $_SESSION["SSVAR"]["msgerrstep"] = "ERROR: failed converting field boundary from json to shapefile !!!";
        return;
    }
    addToLog('Convert field boundary from json to shapefile: done');

    // Find subareas covered by the field boundary.
    addToLog('Finding subareas covered by the field boundary: start');
    if (false == $gdalfuncs->findFldSubarea($workingDir)) {
        $_SESSION["SSVAR"]["ierrstep"] = 1;
        $_SESSION["SSVAR"]["msgerrstep"] = "ERROR: failed finding subareas covered by the field !!!";
        return;
    }
    addToLog('Finding subareas covered by the field boundary: done');

    // Get the total number of watersheds covered by the field boundary.
    $dirreclademw = $workingDir . "/taudemlayers/reclademw";
    $_SESSION['SSVAR']['fldwsno'] = count(glob($dirreclademw . '/b8recdemw*'));    

    addToLog("Updating map files for watershed with field");
    $scenarioarr = $_SESSION["SSVAR"]["runscenario"];
    $baseMapFile = $globwebdir . "olmap/ol_baseline_google.map";
    $mapfilefuncs->updateMapFileFld($workingDir,"taudem.map",true,false,true,false,false,true,$zone,$baseMapFile, $scenarioarr);








?>
