<?php
//
// landuse.php
//
// functions to get land use for the watershed..
//
//



//
// getLanduse()
// 
// This clip the land use to the watershed boundary
//


function getLanduse($workingDir)
{


	// In order to get the same extent to demw, we will
	// get its extent first
	$fndemwinfojson = $workingDir . '/demwstats.json';
	$demwinfo = json_decode(file_get_contents($fndemwinfojson), true);	
	$UL = $demwinfo["cornerCoordinates"]["upperLeft"];
	$LR = $demwinfo["cornerCoordinates"]["lowerRight"];
	//echo('<br>' .var_dump($UL) . ' ' .var_dump($LR) . '<br>');
	// For gdalwarp, the projwin should be ulx uly lrx lry
	$projwin = $UL[0] . " " . $UL[1] . " " . $LR[0] . " " . $LR[1];
	echo('<br>' . $projwin  . '<br>');

	// Change the extent of the nassutm to that of demw
	$luutm = $workingDir . '/landuse/nassutm.tif';
	$ludemwext = $workingDir . '/landuse/nassutmdemwext.tif';
	if (file_exists($ludemwext))
        {unlink($ludemwext); }

	$cmd = "gdal_translate -projwin " . $projwin . " " . $luutm . " " . $ludemwext;
	exec($cmd, $output, $rc);
        if ($rc !== 0) {
              $error[]="<p>***Could not execute: " . $cmd . "***<p>";
        }

	// Clip land use from extent to watershed boundary
	$demwshp = $workingDir.'/esridemw/out.shp';
	$luws = $workingDir.'/landuse/nassws.tif';
	if (file_exists($luws))
	{unlink($luws); }
	clipRasterbyShp($ludemwext, $demwshp, $luws);
	// Convert land use tif to asc file.	
	$luwsasc = $workingDir.'/landuse/nassws.asc';
        if (file_exists($luwsasc))
        {unlink($luwsasc); }
	convTif2Asc($luws,$luwsasc);

	return true;

}














?>
