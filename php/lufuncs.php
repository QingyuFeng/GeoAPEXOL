<?php
//
// landuse.php
//
// functions to get land use for the watershed..
//
//


class lufuncs
{
    //
    // getLanduse()
    // 
    // This clip the land use to the watershed boundary
    //
    public function getLanduse($workingDir)
    {
        // In order to get the same extent to demw, we will
        // get its extent first
        $fndemwinfojson = $workingDir . '/taudemlayers/demwstats.json';
        $demwinfo = json_decode(file_get_contents($fndemwinfojson), true);  
        $UL = $demwinfo["cornerCoordinates"]["upperLeft"];
        $LR = $demwinfo["cornerCoordinates"]["lowerRight"];
        // For gdalwarp, the projwin should be ulx uly lrx lry
        $projwin = $UL[0] . " " . $UL[1] . " " . $LR[0] . " " . $LR[1];

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
        

        return true;

    }   








}





?>
