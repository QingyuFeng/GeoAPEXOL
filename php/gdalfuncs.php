<?php
//
// globals.php
// 
// These should be used in php code to make moving to other machines easier.
//
// 

class gdalfuncs
{

    public function extractNED($state,
        $workingDir,
        $imgext,
        &$hasProj)
    {
        global $globgisdir,$globPythonBin, $globwebdir;

        $hasProj = TRUE;
        // Check for Hawaii, if this is the case then we can just get the data from the merged ned's
        if ($state == "HI") {
            addToLog("<p>-------Extracting NED----------<p>");
            $nedFile =  $globgisdir . "merge/HI/ned_HI.tif";
            $sliceFile = $workingDir . "/gislayers/slice_ned.tif";

            sscanf($imgext, "%f %f %f %f", $XLL, $YLL, $XUR, $YUR);
            $projwin = $XLL . " " . $YUR . " " . $XUR . " " . $YLL;

            $cmd = "gdal_translate -projwin " . $projwin . " " . $nedFile . " " . $sliceFile;
            addToLog($cmd);
            addToLog("\n");
            //system($cmd, $rc);
            exec($cmd, $output, $rc);
            if ($rc !== 0) {
                addToLog("<p>***Could not execute: " . $rc . "***<p>");
            }
            return;
        }

        // New procedure to work directly with the raw NED slices
        // Step 1: consult the ned_index tif to get a grid of ned indexes which will map to file names
        if ($state == "AK")
        {$nedFile = $globgisdir . "ned_AKindex.tif";}
        else
        {$nedFile = $globgisdir . "ned_index.tif";}
        $nedIndexFile = $workingDir . "/gislayers/nindex.tif";

        sscanf($imgext, "%f %f %f %f", $XLL, $YLL, $XUR, $YUR);
        $XLL2 = $XLL-0.05;
        $YUR2 = $YUR+0.05;
        $XUR2 = $XUR+0.05;
        $YLL2 = $YLL-0.05;
        $projwin = $XLL2 . " " . $YUR2 . " " . $XUR2 . " " . $YLL2;
        $projwinExt = $projwin;
        $cmd = "gdal_translate -projwin " . $projwin . " " . $nedFile . " " . $nedIndexFile;

        addToLog("<br>About to run:" . $cmd . "<br>");
        addToLog("\n");
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
           addToLog("<p>***Error Could not execute: " . $rc . "***" . $cmd . "***<p>");
        }

        // Step 2: Convert the ned index map from a tif to ascii so that we can work with it direclty
        $indexFile = $workingDir . "/gislayers/nindex.tif";
        $indexAsc = $workingDir . "/gislayers/nindex.asc";
        $cmd = "gdal_translate -of AAIGrid " . $indexFile . " " . $indexAsc;
        addToLog($cmd);
        addToLog("\n");

        exec($cmd, $output, $rc);
        if ($rc !== 0) {
           addToLog("<p>***Error Could not execute: " . $rc . "***" . $cmd . "***<p>");
        }

        // Step 3: Call a C++ program that goes through the grid and pulls unique indices and looks up the
        // file name in the corresponding dbf file. Output is sent to a file.
        $outFile = $workingDir . "/gislayers/nindexout.txt";
        if ($state == "AK") {
            $dbfFile = $globgisdir . "ned_AKindex2.dbf";
            $inxFile = $globgisdir . "ned_AKindex2.ndx";
        } else {
            $dbfFile = $globgisdir . "ned_index2.dbf";
            $inxFile = $globgisdir . "ned_index2.ndx";
            $csvFile = $globgisdir . "ned_index2.csv";
        }

        $cmd = $globwebdir."gisutils/getNEDFilesCSV " . $csvFile . " " . $indexAsc . " " . $outFile;

        addToLog("<br>" . $cmd . "<br><br>");
        //system($cmd, $rc);
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
           addToLog("<p>***Error Could not execute: " . $rc . "***" . $cmd . "***<p>");
        }

        // see how many chunks we need to process
        $nedPartCount = 0;
        $fp = fopen($outFile,"r");
        if ($fp) {
            $buf = fgets($fp,1024);
            while (!feof($fp)) {
                $nedPartCount = $nedPartCount + 1;
                $buf = fgets($fp,1024);
            }
            fclose($fp);
        }

        // Step 4: Now the list of files are in the $outFile, we now need to extract each of the areas from the neds.
        // Almost all the time we will have only 1 ned to look it is only when the watershed boundary encompasses several
        // neds (at most 4) that we would need to extract more.
        $allnedfiles = "";
        $fp = fopen($outFile,"r");
        if ($fp) {
        $ind=0;
        $buf = fgets($fp,1024);
            while (!feof($fp)) {
            $ind++;
            $nedFile = $globgisdir;
            $nedFile = $nedFile . trim($buf);
            $allnedfiles = $allnedfiles . " " . $nedFile;

            $buf = fgets($fp,1024);

            }
            fclose($fp);

        // Step 5: For each of the areas that were extracted merge them into a single tif
        // Files need to be merged before continuing into a file called slice_ned.tif
        if (file_exists($workingDir . "/gislayers/slice_ned.tif"))
        { unlink($workingDir . "/gislayers/slice_ned.tif");}

        sscanf($imgext, "%f %f %f %f", $XLL, $YLL, $XUR, $YUR);
        $projwin = $XLL . " " . $YUR . " " . $XUR . " " . $YLL;
        $cmd = $globPythonBin . "gdal_merge.py -o " . $workingDir . "/gislayers/slice_ned.tif -ul_lr " . $projwin . " " . $allnedfiles;

        addToLog("<br>" . $cmd . "<br>");
    exec($cmd, $output, $rc);
        if ($rc !== 0) {
            addToLog("<p>***Error Could not execute: " . $rc . "***" . $cmd . "***<p>");
        }


        // assume that this may not have a projection which could be gdal_merge bug.
        $hasProj = FALSE;

        } else
        {addToLog("<p>Can not find " . $outFile . "</p>");}

       return TRUE;
       // End of new procedure to get NED data from raw USGS NED slices.

    }
    // End of extractNED


    //
    // reprojectNED()
    //
    // This reprojects the DEM chunk into UTM coordinates with the approiate zone.
    // Output is in file: utmSlice.tif
    //
    public function reprojectNED($workingDir,$zone,$hasProj)
    {
        $sliceFile = $workingDir . "/gislayers/slice_ned.tif";
        $utmSliceFile = $workingDir . "/gislayers/utmSlice.tif";
        if (file_exists($utmSliceFile))
        { unlink($utmSliceFile);}

        $proj = "'+proj=utm +zone=" . $zone . " +datum=NAD83 +ellps=GRS80' ";
        if ($hasProj == FALSE)
        {$proj = $proj . "-s_srs '+proj=latlong +datum=NAD83 +ellps=GRS80' "; }
        $cmd = "gdalwarp -t_srs " . $proj . "-tr 30 30 -r near -dstnodata -999999 " . $sliceFile . " " . $utmSliceFile;
        addToLog($cmd);
        addToLog("\n");
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
        }
        
        return TRUE;
    }


    // This function was moved to lufuncs.php. It was not used anymore since I reprojected the
    // land use (nass2016)map to the same as the DEM and we do not to reproject
    // the map to and back from alber
    public function extractNASS_oldnotused($workingDir,$imgext, $utmzone)
    {
        global $globgisdir;

        $nassFileFull = $globgisdir . "nass2016/2016_30m_cdls.img";
        $tile = "";
        $XLL = -1;
        $YLL = -1;
        $XUR = -1;
        $YUR = -1;
        $albersProj = "'+proj=aea +lat_1=29.5 +lat_2=45.5 +lat_0=23.0 +lon_0=-96.0 +x_0=0.0 +y_0=0.0 +units=m +datum=NAD83 +ellps=GRS80 +no_defs '";

        sscanf($imgext, "%f %f %f %f", $XLL, $YLL, $XUR, $YUR);
        $XLL2 = $XLL;
        $XUR2 = $XUR;

        // Write a coords for
        // save the bounding extent to a file
        $lucoords = $workingDir.'/landuse/coords.txt';
        if (file_exists($lucoords))
        {unlink($lucoords); }

        $fp = fopen($lucoords, "w");
        if ($fp) {
             fwrite($fp,$XLL2 . " " . $YUR . "\n");
             fwrite($fp,$XUR2 . " " . $YLL . "\n");
             fclose($fp);
        }

        $lucoordsalb = $workingDir.'/landuse/coordsalb.txt';

        $cmd = "gdaltransform -s_srs epsg:4326 -t_srs " . $albersProj . " < " . $lucoords . " > " . $lucoordsalb;


        exec($cmd, $output, $rc);
        if ($rc !== 0) {
              $error[]="<p>***Could not execute: " . $cmd . "***<p>";
        }

    // read the new coords
        $fp = fopen($lucoordsalb,"r");
        if ($fp) {
                $buf = fgets($fp);
                $buf = trim($buf);
                sscanf($buf,"%f %f",$ll_x,$ll_y);
                $buf = fgets($fp);
                $buf = trim($buf);
                sscanf($buf,"%f %f",$ul_x,$ul_y);
                fclose($fp);
        }

        $sliceFile = $workingDir . "/landuse/nassSlice.tif";
        if (file_exists($sliceFile)) {
           unlink($sliceFile);
        }

        $projwin = $ll_x . " " . $ll_y . " " . $ul_x . " " . $ul_y;


        if (!file_exists($nassFileFull)) {
            $error[]="Error: Landuse tile not found. [" . $nassFileFull . "][" . $globgisdir . "]";
        }
        
        $cmd = "gdal_translate -projwin " . $projwin . " " . $nassFileFull . " " . $sliceFile;
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
        }

        // Reproject alber to utm
        $nassUtm = $workingDir . "/landuse/nassutm.tif";
        if (file_exists($nassUtm)) {
           unlink($nassUtm);
        }

        $proj = "'+proj=utm +zone=" . $utmzone . " +datum=NAD83 +ellps=GRS80' ";
        //echo('<br>' . $proj.'<br>');
        $cmd = "gdalwarp -t_srs " . $proj . "-tr 30 30 -dstnodata -9999 " . $sliceFile . " " . $nassUtm;
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
        }

        return true;

    }

    //
    // gdalinfoStats
    //
    public function gdalinfoStats($in_tif, $outjson)
    {
        $cmd = "gdalinfo -stats -json " . $in_tif . " | tee " . $outjson;
        addToLog($cmd);
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
            return;
        }
    }


    //
    // convertdemw0tomax
    //
    public function convertdemw0tomax($srctif, $destif, $maxpv)
    {
        addToLog("Getting converting values of demw.tif 0 to max + 10 ");
        $newmaxpv = $maxpv + 10;
        $cmd = "gdal_calc.py -A " . $srctif . " --outfile=" . $destif . " --calc=\"A*(A>0)+" . $newmaxpv . "*(A==0)\"";
        addToLog($cmd);
        exec($cmd, $output, $rc);
        echo($cmd);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
            return;
        }

    }   


    //
    // convertdemw0tomax
    //
    public function conv32to8bittif($srctif, $destif)
    {
        addToLog("converting 32bit tif to 8 bit tif");
        $cmd = "gdal_translate -ot Byte " . $srctif . " " . $destif;
        addToLog($cmd);
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
            return;
        }
    }


    //
    // convtif2shp
    //
    public function convtif2shp($srctif, $desshp)
    {
        addToLog("converting tif to ESRI shapefile");

        $cmd = "gdal_polygonize.py " . $srctif . " -f 'ESRI Shapefile' " . $desshp;

        addToLog($cmd);
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
            return;
        }

    }   



    //
    //  clipRasterbyShp
    //
    public function clipRasterbyShp($srctif, $clipshp, $destif)
    {
        $cmd = "gdalwarp -cutline " . $clipshp . " " . $srctif . " " . $destif;
        addToLog($cmd);
        exec($cmd,$output, $rc);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
            return;
        }

    }


    //
    //  convTif2Asc($demwfld, $demwfldasc)
    //
    public function convTif2Asc($srctif,$desasc)
    {
        $cmd = "gdal_translate -of AAIGrid " . $srctif . " " . $desasc;
        addToLog($cmd);
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
            return;
        }

    }



    public function demwTif2Shp($workingDir)
    {
        // The shapefile of watershed is needed in clipping
        // the soil, landuse, dem, slope, plen for the
        // watershed boundary. In field simulation mode
        // this need to be called.

        $demw =  $workingDir . "/taudemlayers/demw.tif";
        $subshpdir = $workingDir . "/taudemlayers/esridemw";
        if(is_dir($subshpdir)){$this->delete_files($subshpdir);}

        $this->convtif2shp($demw, $subshpdir);
        return TRUE;
    }


    public function procDemwDispay($workingDir)
    {
        // At last, the demw need to be converted to 8 bit from 32 bit.
        // This is because the mapserver supports the 8 bit better.
        // When I was trying to display demw directly,
        // the raster was very slow to display.
        // Before converting, the demw.tif has a 0 value as the id of the
        // first subarea. When converting directly to 8 bit, the 0 values
        // will be treated as no data. I an not sure why. But I have to
        // deal with this first.
        // Steps:
        // 1. Get demw asc file for: get uniq subnos for max sub nos.
        //
        $demw =  $workingDir . "/taudemlayers/demw.tif";
        $demwasc = $workingDir . "/taudemlayers/demw.asc";
        if (file_exists($demwasc)) {unlink($demwasc);}
        $this->convTif2Asc($demw, $demwasc);
        // Make a directory to store all reclassified subareas
        // This folder will be used in the python script for getting
        // watershedconveredbyfld.
        $tdmlayerfd = $workingDir . "/taudemlayers";
        $dirreclademw = $tdmlayerfd . "/reclademw";
        if(is_dir($dirreclademw)){$this->delete_files($dirreclademw);}
        mkdir($dirreclademw, 0755);
        // Reclassify demw to a new layer to change 0 to max value +1
        $reclasspair = $dirreclademw . "/demwreclasspair.json";
        if (file_exists($reclasspair)){unlink($reclasspair);}

        $this->reclassDemw0toMax($workingDir);

        // After getting the rec, the layers with new numbers
        // will be converted to shapefiles, the layers
        // with 1 numbers will be converted to 8 bit raster.
        $arr_recnewno = glob($dirreclademw.'/recdemw*');
        foreach ($arr_recnewno as &$value) {
            $srcrecdemw = $dirreclademw . '/' . basename($value);
            $shprec = $dirreclademw .'/shp' . basename($value, '.tif'); 
            if(is_dir($shprec)){$this->delete_files($shprec);}
            // Convert to shapefile
            $this->convtif2shp($srcrecdemw, $shprec);
            // convert to b8t 8 and shapefile
            $bit8recdemw = $dirreclademw .'/b8' . basename($value);
            if (file_exists($bit8recdemw)){unlink($bit8recdemw);}
            $this->conv32to8bittif($srcrecdemw, $bit8recdemw);
        }

        // Get the demw information
        addToLog("Getting the gdalinfo of demw: this is used to extract extent");
        $demwstatsjson =  $workingDir . "/taudemlayers/demwstats.json";
        if (file_exists($demwstatsjson))
        {unlink($demwstatsjson);}
        
        $this->gdalinfoStats($demw, $demwstatsjson);

        // Shapefile from demw is needed to get the lat and long of subarea
        // centroid.
        $subshpdir = $workingDir . "/taudemlayers/esridemw";
        if(is_dir($subshpdir)){$this->delete_files($subshpdir);}

        $this->convtif2shp($demw, $subshpdir);

        return TRUE;
    }


    //
    // convjson2shp
    //
    function conjson2shp($srcjson, $desshp, $zone)
    {
        $proj = "'+proj=utm +zone=" . $zone . " +datum=NAD83 +ellps=GRS80' ";
       
        $cmd = "ogr2ogr -a_srs " . $proj . " -f 'ESRI Shapefile' " . $desshp . " " .$srcjson;

        addToLog($cmd);
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
            return;
        }
    }


    public function fldJSONToShp($workingDir, $zone)
    {
        // Convert json to shapefile
        $fldbdyjson = $workingDir . "/fieldbdy.json";
        $fldbdyshpdir = $workingDir . "/gislayers/fldbdy";

        if (file_exists($fldbdyshpdir)){$this->delete_files($fldbdyshpdir);}
        mkdir($fldbdyshpdir, 0755);

        $fnshp = $fldbdyshpdir . "/fldbdy.shp";
        $this->conjson2shp($fldbdyjson, $fnshp, $zone);
 
        return TRUE;
    }

    public function oltJSONToShp($workingDir, $zone)
    {
        // Convert json to shapefile
        $oltjson = $workingDir . "/taudemlayers/mvoutlet.json";
        $shpdir = $workingDir . "/gislayers/mvoutletshp";

        if (file_exists($shpdir)){$this->delete_files($shpdir);}
        mkdir($shpdir, 0755);

        $fnshp = $shpdir . "/mvoutlet.shp";
        $this->conjson2shp($oltjson, $fnshp, $zone);

        return TRUE;
    }





    public function findFldSubarea($workingDir)
    {
        // Find subareas covered by the field boundary and
        // identify watersheds from the subareas:
        // the logics are:
        // 1. Prepare necessary files: demw clip by field
        // boundary to get demwfld.asc, which is used to
        // get unique numbers of subarea
        // 2. Find the subarea that serve as outlet:
        // the condition is the subarea has downstream subarea
        // that are outside the subarea.
        // 3. Using depth-first search method to find all
        // subareas of the watershed who use outlet subarea.  

        addToLog('Finding subareas covered by the field boundary');
        // Clip demw to field boundary
        $demw = $workingDir.'/taudemlayers/demw.tif';
        $demwfld = $workingDir . "/taudemlayers/demwfld.tif";
        $fldbdyshp = $workingDir . "/gislayers/fldbdy/fldbdy.shp";
        if (file_exists($demwfld)) {unlink($demwfld);}
        $this->clipRasterbyShp($demw, $fldbdyshp, $demwfld);
        
        // Get DEMW.tif gdalinfo for later processing
        $demwstatsjson =  $workingDir . "/taudemlayers/demwstats.json";
        if (file_exists($demwstatsjson))
        {unlink($demwstatsjson);}

        $this->gdalinfoStats($demw, $demwstatsjson);
        
        // Convert demwfld from til to asc for processing:
        // This asc will be used to get the unique numbers
        // of subareas covered by field boundary.
        $demwfldasc = $workingDir . "/taudemlayers/demwfld.asc";
        if (file_exists($demwfldasc)) {unlink($demwfldasc);}
        $this->convTif2Asc($demwfld, $demwfldasc);
        
        // Make a directory to store all reclassified subareas
        // This folder will be used in the python script for getting
        // watershedconveredbyfld.
        $tdmlayerfd = $workingDir . "/taudemlayers";
        $dirreclademw = $tdmlayerfd . "/reclademw";
        if(is_dir($dirreclademw)){$this->delete_files($dirreclademw);}
        mkdir($dirreclademw, 0755);

        $reclasspair = $dirreclademw . "/demwreclasspair.json";
        if (file_exists($reclasspair)){unlink($reclasspair);}

        $unisubfld = $tdmlayerfd . "/unisubno.txt";
        if (file_exists($unisubfld)){unlink($unisubfld);}

        #Convert demw to asc, needed to process the tree file for ex
        # tra subarea which do not exist in demw file.
        $demwasc = $workingDir . "/taudemlayers/demw.asc";
        if (file_exists($demwasc)) {unlink($demwasc);}
        $this->convTif2Asc($demw, $demwasc); 
        
        # Then, get the watershed covered by the field
        $this->getwscoveredbyfld($workingDir);

        // After getting the rec, the layers with new numbers
        // will be converted to shapefiles, the layers
        // with 1 numbers will be converted to 8 bit raster.
        $arr_recnewno = glob($dirreclademw.'/recdemw*');
        foreach ($arr_recnewno as &$value) {
            $srcrecdemw = $dirreclademw . '/' . basename($value);
            $shprec = $dirreclademw .'/shp' . basename($value, '.tif'); 
            if(is_dir($shprec)){$this->delete_files($shprec);}

            // Convert to shapefile
            $this->convtif2shp($srcrecdemw, $shprec);
           
            $b8recdemw = $dirreclademw .'/b8' . basename($value);
            if (file_exists($b8recdemw)){unlink($b8recdemw);}

            // convert to b8t 8 and shapefile
            $this->conv32to8bittif($srcrecdemw, $b8recdemw);


        }

        $arr_reconeno = glob($dirreclademw.'/wsnorecws*');
        foreach ($arr_reconeno as &$value) {
            $srcwsdemw = $dirreclademw . '/' . basename($value);
            $b8wsdemw = $dirreclademw .'/b8' . basename($value);
            if (file_exists($b8wsdemw)){unlink($b8wsdemw);}

            // convert to b8t 8 and shapefile
            $this->conv32to8bittif($srcwsdemw, $b8wsdemw);
        }
   
        return TRUE;
    } 

    public function reclassDemw0toMax($workingDir)
    {
        global $globwebdir;

        $cmd = "python3 ".$globwebdir."gisutils/rclWS0toMaxolt.py " . $workingDir . " " . $globwebdir;
        addToLog($cmd);
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
            return;
        }

    }



    public function getwscoveredbyfld($workingDir)
    {
        global $globwebdir;
        
        $cmd = "python3 ".$globwebdir."gisutils/getWatershedsbyField.py " . $workingDir . " " . $globwebdir;
        addToLog($cmd);
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
            return;
        }        
    
    }



    public function procTifs2Ascs($workingDir)
    {
        /*
         * There are several files to be converted from tif
         * to ASC for background processing using python.
         * 1. watershed: demw.tif: for subarea nos
         * 2. elevation: utmSlice.tif:
         * 3. slope: sd8Slopews.tif
         * 4. channel/reach length: plen.tif/also needs streamnet.shp
         * 5. StreamNet marker: streamnet.tif: for distinguising channel vs non-channel cells.
         * 6. soil map
         * 7. land use map
         * */

        // Convert demw from tif to asc file for later processing.
        $demw = $workingDir.'/taudemlayers/demw.tif';
        $demwasc = $workingDir.'/taudemlayers/demw.asc';
        #if (file_exists($demwasc))
        #{unlink($demwasc); }
        #$this->convTif2Asc($demw,$demwasc);
        //$taudemlayersfd = $workingDir . "/taudemlayers";
        //$dirreclademw = $taudemlayersfd . "/reclademw";
        //$arr_recnewno = glob($dirreclademw.'/recdemw*');
        //foreach ($arr_recnewno as &$value) {
        //    $demw = $dirreclademw . '/' . basename($value);
        //    $demwasc = $dirreclademw .'/' . basename($value, '.tif'). '.asc';
        //    if(is_dir($demwasc)){$this->delete_files($demwasc);}
        //    $this->convTif2Asc($demw,$demwasc);
        //}


        // Convert plen from tif to asc file for later processing.
        $plen =  $workingDir . "/taudemlayers/plen.tif";
        $plenasc = $workingDir.'/taudemlayers/plen.asc';
        if (file_exists($plenasc))
        {unlink($plenasc); }
        $this->convTif2Asc($plen,$plenasc);

        // Convert utm to asc for future processing
        $utmSlice = $workingDir.'/gislayers/utmSlice.tif';
        $utmasc = $workingDir.'/gislayers/elev.asc';
        if (file_exists($utmasc))
        {unlink($utmasc); }
        $this->convTif2Asc($utmSlice,$utmasc);

        // Convert land use tif to asc file.
        $lu = $workingDir . '/landuse/nassutmdemwext.tif';
        $luasc = $workingDir.'/landuse/nass.asc';
        if (file_exists($luasc))
        {unlink($luasc); }
        $this->convTif2Asc($lu,$luasc);

        // Convert soil tif to asc file.
        $soilgrid = $workingDir.'/soils/soilutmdemwext.tif';
        $soilgridasc = $workingDir.'/soils/soil.asc';
        if (file_exists($soilgridasc))
        {unlink($soilgridasc); }
        $this->convTif2Asc($soilgrid,$soilgridasc);

        // Convert slope tif to asc file.
        $slope = $workingDir . '/taudemlayers/sd8Slope.tif';
        $slopeasc = $workingDir.'/taudemlayers/sd8Slope.asc';
        if (file_exists($slopeasc))
        {unlink($slopeasc); }
        $this->convTif2Asc($slope,$slopeasc);
        
        return TRUE;

    }



    public function streamTif2Ascolt($workingDir)
    {
        // Convert streamnetOLT from tif to asc file for later processing.
        $srcStrNetOlt =  $workingDir . "/taudemlayers/srcStrNetOlt.tif";
        $srcStrNetOltasc = $workingDir.'/taudemlayers/streamnetolt.asc';
        if (file_exists($srcStrNetOltasc))
        {unlink($srcStrNetOltasc); }
        $this->convTif2Asc($srcStrNetOlt,$srcStrNetOltasc);

        return TRUE;
    }

    public function streamTif2Ascfld($workingDir)
    {
        // Convert streamnetOLT from tif to asc file for later processing.
        $srcStrNetOlt =  $workingDir . "/taudemlayers/srcStrNet.tif";
        $srcStrNetOltasc = $workingDir.'/taudemlayers/streamnet.asc';
        if (file_exists($srcStrNetOltasc))
        {unlink($srcStrNetOltasc); }
        $this->convTif2Asc($srcStrNetOlt,$srcStrNetOltasc);
        return TRUE;
    }



    /*
    * php delete function that deals with directories recursively
    */
    public function delete_files($target)
    {
        if(is_dir($target)){
            $files = glob( $target . '*', GLOB_MARK ); //GLOB_MARK adds a slash to directories returned
            foreach( $files as $file ){
                delete_files( $file );
            }

            rmdir( $target );
        } elseif(is_file($target)) {
            unlink( $target );
        }
    }


    public function mergecutQSNPmaps($workingDir, $scenario)
    {
        global $globwebdir;

        // This will get all names of the QSNP files, create four 
        // layers from them for all watersheds covered by the 
        // field boundary. Then, they will be cut by the field
        // boundary.
        $allaaq = "";
        $allaarsl2 = "";
        $allaatn = "";
        $allaatp = "";
        
        $fd_aaqrnp = $workingDir . "/apexoutjsonmap";
        $arr_aa = glob($fd_aaqrnp.'/aaq*_' . $scenario. '.tif');
        foreach ($arr_aa as &$value) {
            $allaaq = $allaaq . " " . $value;     
        }

        $arr_tn = glob($fd_aaqrnp.'/aatn*_'. $scenario. '.tif');
        foreach ($arr_tn as &$valtn) {
            $allaatn = $allaatn . " " . $valtn;
        }

        $arr_tp = glob($fd_aaqrnp.'/aatp*_'. $scenario. '.tif');
        foreach ($arr_tp as &$valtp) {
            $allaatp = $allaatp . " " . $valtp;
        }

        $arr_rsl2 = glob($fd_aaqrnp.'/aarsl2*_'. $scenario. '.tif');
        foreach ($arr_rsl2 as &$valrsl2) {
            $allaarsl2 = $allaarsl2 . " " . $valrsl2;
        }

        $fnwsaaq = $fd_aaqrnp . "/" . $scenario . "wsaaq.tif";
        $fnwsaarsl2 = $fd_aaqrnp . "/" . $scenario . "wsaarsl2.tif";
        $fnwsaatn = $fd_aaqrnp . "/" . $scenario . "wsaatn.tif";
        $fnwsaatp = $fd_aaqrnp . "/" . $scenario . "wsaatp.tif";

        if (file_exists($fnwsaaq)){unlink($fnwsaaq);}
        if (file_exists($fnwsaarsl2)){unlink($fnwsaarsl2);}
        if (file_exists($fnwsaatn)){unlink($fnwsaatn);}
        if (file_exists($fnwsaatp)){unlink($fnwsaatp);}

        $this->gdalMerge($fnwsaaq, $allaaq);
        $this->gdalMerge($fnwsaarsl2, $allaarsl2);
        $this->gdalMerge($fnwsaatn, $allaatn);
        $this->gdalMerge($fnwsaatp, $allaatp);

        // Cut the field layer by field boundary
        $fnfldaaq = $fd_aaqrnp . "/" . $scenario . "fldaaq.tif";
        $fnfldaarsl2 = $fd_aaqrnp . "/" . $scenario . "fldaarsl2.tif";
        $fnfldaatn = $fd_aaqrnp . "/" . $scenario . "fldaatn.tif";
        $fnfldaatp = $fd_aaqrnp . "/" . $scenario . "fldaatp.tif";

        if (file_exists($fnfldaaq)){unlink($fnfldaaq);}
        if (file_exists($fnfldaarsl2)){unlink($fnfldaarsl2);}
        if (file_exists($fnfldaatn)){unlink($fnfldaatn);}
        if (file_exists($fnfldaatp)){unlink($fnfldaatp);}

        // Field shapefile
        $fieldbdy = $workingDir . "/gislayers/fldbdy/fldbdy.shp";

        $this->clipRasterbyShp($fnwsaaq, $fieldbdy, $fnfldaaq);
        $this->clipRasterbyShp($fnwsaarsl2, $fieldbdy, $fnfldaarsl2);
        $this->clipRasterbyShp($fnwsaatn, $fieldbdy, $fnfldaatn);
        $this->clipRasterbyShp($fnwsaatp, $fieldbdy, $fnfldaatp);

        return TRUE;    

    }


    public function gdalMerge($fnouttif, $infiles)
    {
   
        global $globPythonBin;
        $cmd = $globPythonBin . "gdal_merge.py -o " . $fnouttif . " -of GTiff " . $infiles;
        addToLog($cmd);
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
            return FALSE;
        }
    }


    public function ws2fldtif2asc($workingDir)
    {
        // Four layers to be converted 
        $fdrecla = $workingDir . "/taudemlayers/reclademw";
        
        $arr_recnewno = glob($fdrecla.'/b8recdemw*');

        $fldbdy = $workingDir . "/gislayers/fldbdy/fldbdy.shp";

        foreach ($arr_recnewno as &$value) {
            $fldws = $fdrecla .'/fld' . basename($value); 
            $fldwsasc = $fdrecla .'/fld' . basename($value, '.tif') . '.asc';
            if (file_exists($fldws)){unlink($fldws);}
            if (file_exists($fldwsasc)){unlink($fldwsasc);}
            
            $this->clipRasterbyShp($value, $fldbdy, $fldws);
            $this->convTif2Asc($fldws,$fldwsasc);
        }
        
        return TRUE;
    }






}


?>
