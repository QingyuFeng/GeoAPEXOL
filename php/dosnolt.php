<?php

// This block of code was called when the user pressed
// the Stream Network button. 
//
// The code here will be included in the case: dostreamnet
//
//
    // The comma need to be replaced by space
    // as required by running the getNEDFilesCSV
    // program
	$imgextsp = $_SESSION["SSVAR"]["extent"];
    $imgextsp = str_replace(",", " ", $imgextsp);
    
    echo('<br>Beginning of dosnole: '.$imgextsp."<br>");

	//echo("<br>within dosnolt.php<br>".$imgextsp."<br><br>");
	sscanf($imgextsp, "%f %f %f %f", $XLL, $YLL, $XUR, $YUR);
	//echo("XLL ".$XLL);
   	addToLog("Extract DEM at extent: " . $imgextsp);
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
	$hasProj = TRUE;
	if ($gdalfuncs->extractNED("IN",$workingDir,$imgextsp,$hasProj)==FALSE)
	{
		$error[]="ERROR: Error Building DEM Input !!!";

        	return;
	}


	addToLog("Extracting NASS");
	if ($gdalfuncs->extractNASS($workingDir,$imgextsp, $zone)==FALSE)
    {
        $error[]="ERROR: Error Extracting NASS Input !!!";
        return;
    }

	// Reproject NED to UTM coordinates 
	addToLog("Reprojecting NED"); 
	if ($gdalfuncs->reprojectNED($workingDir,$zone,$hasProj)==FALSE)
    {
        $error[]="ERROR: Error Reprojecting DEM Input !!!";
        return;
    }

    // Running TauDEM to generate stream network
    // Run pitremove 
    
	//echo("<br><br>Getting TauDEM Stream Network<br><br>");
    if ($taudemfuncs->getStreamNetwork($workingDir, $_SESSION["SSVAR"]["criticalarea"],$_SESSION["SSVAR"]["demcellsize"])==FALSE)
    {
        $error[]="ERROR: Running TauDEM to get stream network !!!";
        return;
    }
	
	// Update map files
	// Define map file:

    $scenarioarr = $_SESSION["SSVAR"]["runscenario"];
    $baseMapFile = $globwebdir . "olmap/ol_baseline_google.map";
	$mapfilefuncs->updateMapFileOlt($workingDir,"taudem.map",true,false,false,false,false,$zone,$baseMapFile, $scenarioarr);	
	
	


?>
