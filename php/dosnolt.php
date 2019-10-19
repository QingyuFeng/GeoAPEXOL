<?php

// This block of code was called when the user pressed
// the Stream Network button. 
//
// The code here will be included in the case: dostreamnet
//
//
    // The comma need to be replaced by space
    // as required by running the getNEDFilesCSV
    // program. At this time, session extent is updated 
    // via post method.
	$imgextsp = $_SESSION["SSVAR"]["extent"];
    $imgextsp = str_replace(",", " ", $imgextsp);
    
    //echo('<br>Beginning of dosnole: '.$imgextsp."<br>");
	//echo("<br>within dosnolt.php<br>".$imgextsp."<br><br>");
	sscanf($imgextsp, "%f %f %f %f", $XLL, $YLL, $XUR, $YUR);
	//echo("XLL ".$XLL);
   	addToLog("Extracting DEM at extent: " . $imgextsp);
	//echo("<br><br>stream network: " . $imgextsp ."<br><br>");
   	//echo("Image Extent: " . $_SESSION["SSVAR"]["extent"] . "<br>");
   	//echo("Critical Source Area (ha): " . $_SESSION["SSVAR"]["criticalarea"] . "<br>");

	$_SESSION["SSVAR"]['extxll'] = $XLL;
	$_SESSION["SSVAR"]['extyll'] = $YLL;
	$_SESSION["SSVAR"]['extxur'] = $XUR;
	$_SESSION["SSVAR"]['extyur'] = $YUR;

	$XEXT = abs($XLL - $XUR);
	$YEXT = abs($YUR - $YLL);

	$_SESSION["SSVAR"]['utmzone'] = floor(((($XLL + $XUR)/2.0)/6) + 31);
	$zone = $_SESSION["SSVAR"]['utmzone'];

	// If the user directly zoom to without using go button
	// calculate the longitude and latitude as the center
	// of the extent
    if ($_SESSION["SSVAR"]["izoom"] == 0)
    {
        $_SESSION["SSVAR"]["latitude"] = $YLL + ($YUR - $YLL)/2.0;
        $_SESSION["SSVAR"]["longitude"] =$XLL + ($XUR - $XLL)/2.0;
    }
    // Extract DEM
    addToLog("Extracting DEM: start!");
	$hasProj = TRUE;
	if ($gdalfuncs->extractNED("IN",$workingDir,$imgextsp,$hasProj)==FALSE)
	{
        $_SESSION["SSVAR"]["ierrstep"] = 1;
        $_SESSION["SSVAR"]["msgerrstep"] = "ERROR: Error Building DEM Input !!!";
      	return;
	}
    addToLog("Extracting DEM: done!");

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

	// Reproject NED to UTM coordinates 
	addToLog("Reprojecting NED: start"); 
	if ($gdalfuncs->reprojectNED($workingDir,$zone,$hasProj)==FALSE)
    {
        $_SESSION["SSVAR"]["ierrstep"] = 1;
        $_SESSION["SSVAR"]["msgerrstep"] = "ERROR: Error reprojecting DEM Input !!!";
        return;
    }
    addToLog("Reprojecting NED: done");


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
    // Run pitremove 
	//echo("<br><br>Getting TauDEM Stream Network<br><br>");
    addToLog("Generating stream network: start");
    if ($taudemfuncs->getStreamNetwork($workingDir, $_SESSION["SSVAR"]["criticalarea"],$_SESSION["SSVAR"]["demcellsize"])==FALSE)
    {
        $_SESSION["SSVAR"]["ierrstep"] = 1;
        $_SESSION["SSVAR"]["msgerrstep"] = "ERROR: failed generating streamnet work !!!";
        return;
    }
    addToLog("Generating stream network: done");

	// Update map files
	// Define map file:
    $scenarioarr = $_SESSION["SSVAR"]["runscenario"];
    $baseMapFile = $globwebdir . "olmap/ol_baseline_google.map";
	$mapfilefuncs->updateMapFileOlt($workingDir,"taudem.map",true,false,false,false,false,$zone,$baseMapFile, $scenarioarr);	
	


?>
