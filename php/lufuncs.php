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
    public function extLanduse2ws($workingDir)
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
        $luutm = $workingDir . '/landuse/luUTMext.tif';
        $ludemwext = $workingDir . '/landuse/nassutmdemwext.tif';
        if (file_exists($ludemwext))
        {unlink($ludemwext); }

        $cmd = "gdal_translate -projwin " . $projwin . " " . $luutm . " " . $ludemwext;
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            addToLog("<p>***Could not execute: " . $cmd . "***<p>");
            return False;
        }
        

        return true;

    }   



    public function extractLU2ext($workingDir,$imgext)
    {
        
        global $globgisdir;
    
        $nassFile = $globgisdir . "nass2016/2016_30m_cdls.img";
        $sliceFile = $workingDir . "/landuse/nassSlice.tif";

        // Defint output extent
        sscanf($imgext, "%f %f %f %f", $XLL, $YLL, $XUR, $YUR);
        $projwin = $XLL . " " . $YUR . " " . $XUR . " " . $YLL;

        $cmd = $globPythonBin . "gdal_merge.py -o " . $sliceFile . " -ul_lr " . $projwin . " " . $nassFile;
        addToLog("<br>About to run:" . $cmd . "<br>");
        addToLog("\n");
        exec($cmd, $output, $rc);
        
        if ($rc !== 0) {
            addToLog("<p>***Error Could not execute: " . $rc . "***" . $cmd . "***<p>");
            return FALSE;
        } 
        
        return TRUE;

    }

    // reprojectLU2UTM()
    //
    // This reprojects the land use chunk into UTM coordinates with the approiate zone.
    // Output is in file: nassUTMext.tif
    //
    public function reprojectLU2UTM($workingDir,$zone,$hasProj)
    {
        $sliceFile = $workingDir . "/landuse/nassSlice.tif";
        $utmSliceFile = $workingDir . "/landuse/luUTMext.tif";
        if (file_exists($utmSliceFile))
        { unlink($utmSliceFile);}

        $proj = "'+proj=utm +zone=" . $zone . " +datum=NAD83 +ellps=GRS80' ";
        if ($hasProj == FALSE)
        {$proj = $proj . "-s_srs '+proj=latlong +datum=NAD83 +ellps=GRS80' "; }

        $srcproj = "-s_srs '+proj=latlong +datum=NAD83 +ellps=GRS80' ";
        
        $cmd = "gdalwarp " . $srcproj . " -t_srs " . $proj . "-tr 30 30 -r near -dstnodata -99999999.0 -of GTiff -co COMPRESS=NONE -co BIGTIFF=IF_NEEDED " . $sliceFile . " " . $utmSliceFile;
        addToLog($cmd);
        addToLog("\n");
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            addToLog("<p>***Could not execute: " . $cmd . "***<p>");
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
            return FALSE;
        }

        return TRUE;
    }
















}
?>
