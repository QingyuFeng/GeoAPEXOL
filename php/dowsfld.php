<?php

// This block of code was called when the user pressed
// the Get Watershed button. 
//
// The code here will be included in the case: dowatershedolt
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

    addToLog('<br>Saving field boundary to geojson<br>');
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
        $error[]="<p>Error Could not save field json file<p>";
        return;
    }

    $_SESSION["SSVAR"]['utmzone'] = floor(((($XLL + $XUR)/2.0)/6) + 31);
    $zone = $_SESSION["SSVAR"]['utmzone'];  
    //echo("<br><br> zone: " . $zone. "<br><br>");
    // Extract NED, reproject and update the map files
    // Extract DEM
    $hasProj = TRUE;

    //echo("<br><br>Extracting NED<br><br>");
    if ($gdalfuncs->extractNED("IN",$workingDir,$imgextsp,$hasProj)==FALSE)
    {
        $error[]="ERROR: Error Building DEM Input !!!";
        return;
    }

    // Reproject NED to UTM coordinates
    //echo("<br>Reprojecting NED<br>");
    if ($gdalfuncs->reprojectNED($workingDir,$zone,$hasProj)==FALSE)
    {
        $error[]="ERROR: Error Reprojecting DEM Input !!!";
        return;
    }

    addToLog("Extracting NASS");
    if ($gdalfuncs->extractNASS($workingDir,$imgextsp, $zone)==FALSE)
    {
        $error[]="ERROR: Error Extracting NASS Input !!!";
        return;
    }

    // Running TauDEM to generate stream network
    addToLog('getting stream network for the extent');
    if ($taudemfuncs->getStreamNetwork($workingDir, $_SESSION["SSVAR"]["criticalarea"], $_SESSION["SSVAR"]["demcellsize"])==FALSE)
    {
        $error[]="ERROR: Running TauDEM to get stream network !!!";
        return;
    }

    addToLog('getting watersheds for field boundary');
    if (false == $taudemfuncs->getWSforExtent($workingDir,$zone)) {
        $error[]="<p>Error getting watersheds for field boundary<p>";
        return;
    }

    addToLog('Convert field boundary from json to shapefile');
    if (false == $gdalfuncs->fldJSONToShp($workingDir,$zone)) {
        $error[]="<p>Convert field boundary from json to shapefile<p>";
        return;
    }

    // Find subareas covered by the field boundary.
    addToLog('Finding subareas covered by the field boundary\n');
    if (false == $gdalfuncs->findFldSubarea($workingDir)) {
        $error[]="ERROR: Running finding subareas covered by the field !!!";
        return;
    }

    // Get the total number of watersheds covered by the field boundary.
    $dirreclademw = $workingDir . "/taudemlayers/reclademw";
    $_SESSION['SSVAR']['fldwsno'] = count(glob($dirreclademw . '/b8recdemw*'));    

    addToLog("Updating map files for watershed with field");
    $scenarioarr = $_SESSION["SSVAR"]["runscenario"];
    $baseMapFile = $globwebdir . "olmap/ol_baseline_google.map";
    $mapfilefuncs->updateMapFileFld($workingDir,"taudem.map",true,false,true,false,false,true,$zone,$baseMapFile, $scenarioarr);








?>
