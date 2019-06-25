<?php
//
// globals.php
// 
// These should be used in php code to make moving to other machines easier.
//
// 
//
//



function setWorkingDir()
{
       global $globWorkRoot, $globwebdir;
       $workingDir = $globWorkRoot . session_id();
       addToLog("Working Directory: '" . $workingDir . "'<br>");
       if (!file_exists($workingDir)) {
           addToLog("Setup Session Directory|" . $workingDir);

           mkdir($workingDir);
        }

        if (!file_exists($workingDir . "/apexruns")) {
           mkdir($workingDir . "/apexruns");}

        if (!file_exists($workingDir . "/soils")) {
           mkdir($workingDir . "/soils");}

	if (file_exists($workingDir . "/landuse") == FALSE)
	{mkdir($workingDir . "/landuse");}




	//if (!file_exists($workingDir . "/output"))
        //    mkdir($workingDir . "/output");

        if (!file_exists($workingDir . "/dbfextract")) {
           copy($globwebdir."gisutils/dbfextract",$workingDir . "/dbfextract");
           chmod($workingDir . "/dbfextract", 0755);
        }

}



function doZoom($zoomLoc,$latitude,$longitude)
{
	global $globgisdir;

    $arr = array($latitude,$longitude);

    $zoomLoc = trim($zoomLoc);
    $found = 0;

    if (strlen($zoomLoc) <= 0)
        return;
 
    $str2 = sprintf(">>%s<<",$zoomLoc);

    if (is_numeric($zoomLoc)) {
		// zip code search
		$zipVal = intval($zoomLoc);
		$zf = fopen($globgisdir . "gps2-zip.txt","r");
		if ($zf) {
			// skip first line (header)
			fgets($zf);
				while (!feof($zf)) 
				{
					$buf = fgets($zf,1024);
					$zc = sscanf($buf,"%d");
					if (count($zc) == 1) 
					{
						if ($zc[0] == $zipVal) 
						{
							$str2 = sprintf("Found Zip Code %d at location: %s<br>",$zipVal,$buf);
							$tok = explode(" ",$buf);
							$len = count($tok);
							$arr[0] = floatval($tok[$len-2]);
							$arr[1] = floatval($tok[$len-3]) * -1;
							//print(":" . $len . ":" .$arr[0] . " ***  " . $arr[1]);
							$found = 1;
							break;
						}
					}
				}
	    fclose($zf);
	    if ($found == 0) {
	       $str2 = sprintf("<br>Zip code %d not found<br>",$zipVal);
	       print($str2);
	    }
		} else {
	    print("<br>gps2-zip.txt not found<br>");
		}
	} 
	else 
	{
        // city state search
		$zoomLoc = strtoupper($zoomLoc);
		$cityState = explode(",",$zoomLoc);
		$len = count($cityState);
      	$state = $cityState[$len-1];
		$state = trim($state);
		if (is_numeric($state)) 
		{
			// assume it is a longitude, latitdue
			$city = trim($cityState[0]);
			if (is_numeric($city)) 
			{
				$arr[1] = floatval($city);
				$arr[0] = floatval($state);
					$found = 1;
			} else
			{print("Could not find longitude, latitude location<b>");}
		} 
		else
		{
			$city = "";
			for ($i=0;$i<($len-1);$i++) 
			$city = $city . $cityState[$i];

			$city = trim($city);
			$state = trim($state);

			$zf = fopen($globgisdir ."gps2-zip.txt","r");
			if ($zf)
			{
				// skip first line (header)
				fgets($zf);
				while (!feof($zf)) 
				{
					$buf = fgets($zf,1024);
					$tok = explode(" ",$buf);
					$state2 = trim($tok[1]);
					if ($state2 == $state) 
					{
						// state matches, check for city
						$len = count($tok);
						$last = $len - 4;
						$start = 2;
						$cityz = "";
						for ($i=$start;$i<=$last;$i++) 
						{
							$cityz = $cityz . " " . $tok[$i];
						}
						$cityz = trim($cityz);
						if ($cityz == $city) 
						{
							$arr[0] = floatval($tok[$len-2]);
							$arr[1] = floatval($tok[$len-3]) * -1;
							$found = 1;
							break;
						} 
					} 
				}
            fclose($zf);
            if ($found == 0) 
			{
				$str2 = sprintf("<br>%s, %s not found<br>",$city,$state);
				print($str2);
            }
		}
	}
	}

    return $arr;
}




function extractNED($state,$workingDir,$imgext,&$hasProj)
{
        global $globgisdir,$globPythonBin, $globwebdir;

        $hasProj = TRUE;
	// Check for Hawaii, if this is the case then we can just get the data from the merged ned's
	if ($state == "HI") {
 	   addToLog("<p>-------Extracting NED----------<p>");
           $nedFile =  $globgisdir . "merge/HI/ned_HI.tif";
           $sliceFile = $workingDir . "/slice_ned.tif";

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
	   $nedFile = $globgisdir . "ned_AKindex.tif";
	else
	   $nedFile = $globgisdir . "ned_index.tif";
	$nedIndexFile = $workingDir . "/nindex.tif";
	
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
	//system($cmd, $rc);
	exec($cmd, $output, $rc);
        if ($rc !== 0) {
           addToLog("<p>***Error Could not execute: " . $rc . "***" . $cmd . "***<p>");
        }

       // Step 2: Convert the ned index map from a tif to ascii so that we can work with it direclty
       $indexFile = $workingDir . "/nindex.tif";
       $indexAsc = $workingDir . "/nindex.asc";
       $cmd = "gdal_translate -of AAIGrid " . $indexFile . " " . $indexAsc;
       addToLog($cmd);
       addToLog("\n");
       //system($cmd, $rc);
       exec($cmd, $output, $rc);
        if ($rc !== 0) {
           addToLog("<p>***Error Could not execute: " . $rc . "***" . $cmd . "***<p>");
        }
       
       // Step 3: Call a C++ program that goes through the grid and pulls unique indices and looks up the 
       // file name in the corresponding dbf file. Output is sent to a file.
       $outFile = $workingDir . "/nindexout.txt";
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
         if (file_exists($workingDir . "/slice_ned.tif"))
	 { unlink($workingDir . "/slice_ned.tif");}

         sscanf($imgext, "%f %f %f %f", $XLL, $YLL, $XUR, $YUR);
         $projwin = $XLL . " " . $YUR . " " . $XUR . " " . $YLL;

	 $cmd = $globPythonBin . "gdal_merge.py -o " . $workingDir . "/slice_ned.tif -ul_lr " . $projwin . " " . $allnedfiles;
	 
        addToLog("<br>" . $cmd . "<br>");
	exec($cmd, $output, $rc);
        if ($rc !== 0) {
        	addToLog("<p>***Error Could not execute: " . $rc . "***" . $cmd . "***<p>");
        }


          // assume that this may not have a projection which could be gdal_merge bug.
           $hasProj = FALSE;

       } else
         addToLog("<p>Can not find " . $outFile . "</p>");

       return TRUE; 
       // End of new procedure to get NED data from raw USGS NED slices.
}
// End of extractNED

function extractNASS($workingDir,$imgext, $utmzone)
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
// reprojectNED()
//
// This reprojects the DEM chunk into UTM coordinates with the approiate zone.
// Output is in file: utmSlice.tif
//
function reprojectNED($workingDir,$zone,$hasProj)
{
     $sliceFile = $workingDir . "/slice_ned.tif";
     $utmSliceFile = $workingDir . "/utmSlice.tif";
     if (file_exists($utmSliceFile))
     { unlink($utmSliceFile);}

     $proj = "'+proj=utm +zone=" . $zone . " +datum=NAD83 +ellps=GRS80' ";
     if ($hasProj == FALSE)
     {$proj = $proj . "-s_srs '+proj=latlong +datum=NAD83 +ellps=GRS80' "; }
     $cmd = "gdalwarp -t_srs " . $proj . "-tr 30 30 -r bilinear " . $sliceFile . " " . $utmSliceFile;
     addToLog($cmd);
     addToLog("\n");
     exec($cmd);
	//system($cmd);
     return TRUE; 
}



// Starting Taudem Functions
//
// runTauDEMPitRemove()
//
function runTauDEMPitRemove($inDem,$outfelDem)
{
        addToLog("TauDEM PitRemove");
        global $globwebdir;
        $cmd = $globwebdir. "gisutils/taudembin/pitremove -z " . $inDem . " -fel " . $outfelDem;

        addToLog("<br>" . $cmd . "<br>");
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
           addToLog("<p>***Error Could not execute: " . $rc . "***" . $cmd . "***<p>");
        }

}



//
// runTauDEMD8FlowDir()
//
//
function runTauDEMD8FlowDir($infelDem,$outpFlowDir, $outsd8Slope)
{
        addToLog("TauDEM D8FlowDir");
        global $globwebdir;
        $cmd = $globwebdir. "gisutils/taudembin/d8flowdir -fel " . $infelDem . " -p " . $outpFlowDir . " -sd8 " . $outsd8Slope;

        addToLog("<br>" . $cmd . "<br>");
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
           addToLog("<p>***Error Could not execute: " . $rc . "***" . $cmd . "***<p>");
        }

}


//
// runTauDEMAreaD8()
//
//
function runTauDEMAreaD8($inpFlowDir,$outad8ContriArea)
{

        // Command line to run
        // aread8 -p < pfile > -ad8 <ad8file> [ -o <outletfile>] [ -wg < wgfile >] [ -nc ] [ -lyrname < layer name >] [ -lyrno < layer number >]
        addToLog("TauDEM AreaD8 without outlet");
        global $globwebdir;
        $cmd = $globwebdir. "gisutils/taudembin/aread8 -p " . $inpFlowDir . " -ad8 " . $outad8ContriArea;

        addToLog("<br>" . $cmd . "<br>");
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
           addToLog("<p>***Error Could not execute: " . $rc . "***" . $cmd . "***<p>");
        }
}


//
// runTauDEMThreshold()
//
//
function runTauDEMThreshold($inad8ContriArea, $outsrcStreamNet, $thresholdno)
{

        // Command line to run
        // Threshold -ssa <ssafile> -src <srcfile> -thresh 100.0 [ -mask maskfile]
        addToLog("TauDEM Threshold");
        global $globwebdir;
        $cmd = $globwebdir. "gisutils/taudembin/threshold -ssa " . $inad8ContriArea . " -src " . $outsrcStreamNet . "-thresh". $thresholdno;

        addToLog("<br>" . $cmd . "<br>");
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
           addToLog("<p>***Error Could not execute: " . $rc . "***" . $cmd . "***<p>");
        }
}




//
// runTauDEMMoveOutletsToStreams()
//
function runTauDEMMoveOutletsToStreams($inp,$insrc, $inoutlet, $outmvoutlet)
{
	addToLog("TauDEM MoveOUtletstoStreams");
	global $globwebdir;

	// MoveOutletsToStreams -p <pfile> -src <srcfile> -o <outletfile> [ -lyrname <layer name>] [ -lyrno <layer number>] -om <movedoutletsfile> [ -omlyr omlayername] [ -md maxdist]
	$cmd = $globwebdir. "gisutils/taudembin/moveoutletstostrm -p " . $inp . " -src " . $insrc . " -o " . $inoutlet . " -om " . $outmvoutlet . " -md 100";
        addToLog("<br>" . $cmd . "<br>");
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
           addToLog("<p>***Error Could not execute: " . $rc . "***" . $cmd . "***<p>");
        }


}


//
// runTauDEMAreaD8OLT()
//
function runTauDEMAreaD8OLT($inpFlowDir, $inOutlet, $outad8ContriArea)
{

        // Command line to run
        // aread8 -p < pfile > -ad8 <ad8file> [ -o <outletfile>] [ -wg < wgfile >] [ -nc ] [ -lyrname < layer name >] [ -lyrno < layer number >]

	addToLog("TauDEM AreaD8 with outlet");
	global $globwebdir;
	$cmd = $globwebdir. "gisutils/taudembin/aread8 -p " . $inpFlowDir . " -o " . $inOutlet . " -ad8 " . $outad8ContriArea;

        addToLog($cmd);
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
           addToLog("<p>***Error Could not execute: " . $rc . "***" . $cmd . "***<p>");
        }


}


//
// runTauDEMGridNetOLT()
//
//
function runTauDEMGridNetOLT($inp,$inOutlet,$outplen, $outtlen, $outgord)
{

        // Command line to run
        // Gridnet -p<pfile> -plen <plenfile> -tlen <tlenfile> -gord <gordfile> [-o <outletfile>] [-lyrname <layer name>] [-lyrno <layer number>] [-mask <maskfile> [-thresh <threshold>]]
        addToLog("TauDEM GridNet with outlet");
        global $globwebdir;
$cmd = $globwebdir. "gisutils/taudembin/gridnet -p " . $inp . " -plen " . $outplen . " -tlen ". $outtlen . " -gord " . $outgord . " -o " . $inOutlet;
        addToLog($cmd);
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
           addToLog("<p>***Error Could not execute: " . $rc . "***" . $cmd . "***<p>");
	}
}



//
// runTauDEMStreamNetOLT()
//
function runTauDEMStreamNetOLT($infel, $inp, $inad8, $insrc,$inoutlet, $outord, $outtree, $outcoord, $outstream, $outw)
{
	// StreamNet -fel <felfile> -p <pfile> -ad8 <ad8file> -src <srcfile> -ord <ordfile> -tree <treefile> -coord<coordfile> -net <netfile> [ -netlyr netlayername] [ -o <outletfile>] [ -lyrname <layer name>] [ -lyrno <layer number>] -w <wfile> [ -sw]
        addToLog("TauDEM StreamNet with Outlet");
	global $globwebdir;
	$cmd = $globwebdir. "gisutils/taudembin/streamnet -fel " . $infel . " -p " . $inp . " -ad8 ". $inad8 . " -src " . $insrc . " -ord " . $outord . " -tree " . $outtree .  " -coord " . $outcoord . " -net " . $outstream . " -o " . $inoutlet . " -w ". $outw;

        addToLog($cmd);
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
           addToLog("<p>***Error Could not execute: " . $rc . "***" . $cmd . "***<p>");
        }

}



//
// gdalinfoStats
//
function gdalinfoStats($in_tif, $outjson)
{
        addToLog("Getting the statstics of the demw.tif raster");

	$cmd = "gdalinfo -stats -json " . $in_tif . " | tee " . $outjson; 

	addToLog($cmd);
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
           addToLog("<p>***Error Could not execute: " . $rc . "***" . $cmd . "***<p>");
        }

}


//
// convertdemw0tomax 
//
function convertdemw0tomax($srctif, $destif, $maxpv)
{
        addToLog("Getting converting values of demw.tif 0 to max + 10 ");

	$newmaxpv = $maxpv + 10; 

        $cmd = "gdal_calc.py -A " . $srctif . " --outfile=" . $destif . " --calc=\"A*(A>0)+" . $newmaxpv . "*(A==0)\"";

        addToLog($cmd);
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
           addToLog("<p>***Error Could not execute: " . $rc . "***" . $cmd . "***<p>");
        }

}

//
// convertdemw0tomax
//
function conv32to8bittif($srctif, $destif)
{
        addToLog("converting 32bit tif to 8 bit tif");

        $cmd = "gdal_translate -ot Byte " . $srctif . " " . $destif;

        addToLog($cmd);
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
           addToLog("<p>***Error Could not execute: " . $rc . "***" . $cmd . "***<p>");
        }

}




//
// convtif2shp
//
function convtif2shp($srctif, $desshp)
{
        addToLog("converting tif to ESRI shapefile");

	$cmd = "gdal_polygonize.py " . $srctif . " -f 'ESRI Shapefile' " . $desshp;

        addToLog($cmd);
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
           addToLog("<p>***Error Could not execute: " . $rc . "***" . $cmd . "***<p>");
        }

}


/* 
 * php delete function that deals with directories recursively
 */
function delete_files($target) {
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



//
// This runs TauDEM in watershed delineation with an watershed outlet
//
function runTauDEMOLT($workingDir)
{

        addToLog("<br><br>Running TauDEM: MoveOutletstoStreams<br>");

        $fnoltjson = $workingDir.'/outletpnt.json';
        $pFlowDir =  $workingDir . "/pFlowDir.tif";
        $fnmvolt = $workingDir.'/mvoutlet.json';
        $srcStrNet =  $workingDir . "/srcStrNet.tif";
        if (file_exists($fnmvolt))
                {unlink($fnmvolt);}

        runTauDEMMoveOutletsToStreams($pFlowDir,$srcStrNet, $fnoltjson, $fnmvolt);

        addToLog("<br><br>Finished running TauDEM: MoveOutletstoStreams<br><br>");

        // Step 2: run aread8 with outlet
        addToLog("<br><br>Running TauDEM: AREAD8 With outlet<br><br>");
        $ad8ContArea =  $workingDir . "/ad8ContAreaOlt.tif";
        if (file_exists($ad8ContArea))
                {unlink($ad8ContArea);}

        runTauDEMAreaD8OLT($pFlowDir, $fnmvolt, $ad8ContArea);


	addToLog("<br>Finished Running TauDEM: AREAD8 With outlet<br><br>");

        // Step 3: Running grid net
        addToLog("<br><br>Running TauDEM: gridnet With outlet <br><br>");
        $plen =  $workingDir . "/plen.tif";
        $tlen =  $workingDir . "/tlen.tif";
        $gord =  $workingDir . "/gord.tif";

        if (file_exists($plen)) {unlink($plen);}
        if (file_exists($tlen)) {unlink($tlen);}
        if (file_exists($gord)) {unlink($gord);}

        runTauDEMGridNetOLT($pFlowDir,$fnmvolt,$plen, $tlen, $gord);
        addToLog("<br>Finished Running TauDEM: gridnet With outlet <br><br>");

	// Convert plen from tif to asc file for later processing.
        $plenasc = $workingDir.'/plen.asc';
        if (file_exists($plenasc))
        {unlink($plenasc); }
        convTif2Asc($plen,$plenasc);

	// Step 4: Running Threshold again
        addToLog("<br><br>Running TauDEM: Threshold <br><br>");
	$srcStrNetOlt =  $workingDir . "/srcStrNetOlt.tif";
	if (file_exists($srcStrNetOlt)) {unlink($srcStrNetOlt);}
        runTauDEMThreshold($ad8ContArea, $srcStrNetOlt, $_SESSION['csa']);

        addToLog("<br>Finished Running TauDEM: Threshold <br><br>");

        // Step 5: Running Threshold again
        addToLog("<br><br>Running TauDEM: StreamNet with outlet <br><br>");
        $demfel = $workingDir . "/demfel.tif";
        $ord =  $workingDir . "/ord.tif";
        $tree =  $workingDir . "/tree.txt";
        $coord =  $workingDir . "/coord.tif";
        $strmshp =  $workingDir . "/stream.shp";
        $demw =  $workingDir . "/demw.tif";

        if (file_exists($ord)) {unlink($ord);}
        if (file_exists($tree)) {unlink($tree);}
        if (file_exists($coord)) {unlink($coord);}
        if (file_exists($strmshp)) {unlink($strmshp);}
        if (file_exists($demw)) {unlink($demw);}

        runTauDEMStreamNetOLT($demfel, $pFlowDir, $ad8ContArea, $srcStrNetOlt,$fnmvolt, $ord, $tree, $coord, $strmshp, $demw);

        addToLog("<br>Finished Running TauDEM: StreamNet with outlet <br><br>");

	// Convert demw from tif to asc file for later processing.
        $demwasc = $workingDir.'/demw.asc';
        if (file_exists($demwasc))
        {unlink($demwasc); }
        convTif2Asc($demw,$demwasc);

        // Convert streamnetOLT from tif to asc file for later processing.
        $srcStrNetOltasc = $workingDir.'/streamnetolt.asc';
        if (file_exists($srcStrNetOltasc))
        {unlink($srcStrNetOltasc); }
        convTif2Asc($srcStrNetOlt,$srcStrNetOltasc);

	// At last, the demw need to be converted to 8 bit from 32 bit.
	// This is because the mapserver supports the 8 bit better.
	// When I was trying to display demw directly, the raster was very
	// slow to display. 
	// Before converting, the demw.tif has a 0 value as the id of the
	// first subarea. When converting directly to 8 bit, the 0 values
	// will be treated as no data. I an not sure why. But I have to 
	// deal with this first.
	// Steps:
	// 1. get the maximum value of the demw.tif with gdalinfo -stats
	// 2. reclassify demw.tif to change 0 to maximum value + 1.
	// All these processing are only for display. The actual calculation
	// will still use the demw.
	// 3. convert the 32 bit map to 8 bit raster using gdal_translate -ot
	// Byte src.tif dest.tif command. 
	$demwstatsjson =  $workingDir . "/demwstats.json";
	if (file_exists($demwstatsjson)) {unlink($demwstatsjson);}

	gdalinfoStats($demw, $demwstatsjson);	

	// Read in statistics json file to get the maximum subarea id
        $maxsubid = json_decode(file_get_contents($demwstatsjson), true)["bands"]["0"]["max"];

	// Reclassify the demw.tif to change 0 values int maxsubid + 10	i

        $demw0tomax =  $workingDir . "/demw0tomax.tif";
        if (file_exists($demw0tomax)) {unlink($demw0tomax);}

	convertdemw0tomax($demw, $demw0tomax, $maxsubid);

	// Convert demw0tomax.tif from 32 to 8 bit raster
	$demwmax8bit = $workingDir . "/demwmax8bit.tif";	
	if(file_exists($demwmax8bit)){unlink($demwmax8bit);}

	conv32to8bittif($demw0tomax, $demwmax8bit);

	// We also would like to show the id of the watershed, thus, a
	// shapefile was generated
	// This conversion will use demw.tif since we still want to use 
	// the original coding.

        $subshpdir = $workingDir . "/esridemw";
        if(is_dir($subshpdir)){delete_files($subshpdir);}
	
	convtif2shp($demw, $subshpdir);	

	// After getting the demw, clip the dem.tif and
        // convert it to asc
	// Clip soil raster to the boundary of watershed
        $utmSlice = $workingDir.'/utmSlice.tif';
	$demwshp = $workingDir.'/esridemw/out.shp';
        $demws = $workingDir.'/demws.tif';
        if (file_exists($demws))
        {unlink($demws); }
        clipRasterbyShp($utmSlice, $demwshp, $demws);
        // Convert tif to asc for processing
        $demwsasc = $workingDir.'/demws.asc';
        if (file_exists($demwsasc))
        {unlink($demwsasc); }
        convTif2Asc($demws,$demwsasc);
	
	return true;
}


//
// runTauDEMGridNetFLD()
//
//
function runTauDEMGridNetFLD($inp,$outplen, $outtlen, $outgord)
{

        // Command line to run
        // Gridnet -p<pfile> -plen <plenfile> -tlen <tlenfile> -gord <gordfile> [-o <outletfile>] [-lyrname <layer name>] [-lyrno <layer number>] [-mask <maskfile> [-thresh <threshold>]]
        addToLog("TauDEM GridNet with outlet");
        global $globwebdir;
	$cmd = $globwebdir. "gisutils/taudembin/gridnet -p " . $inp . " -plen " . $outplen . " -tlen ". $outtlen . " -gord " . $outgord;
        addToLog($cmd."<br>");
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
           addToLog("<p>***Error Could not execute: " . $rc . "***" . $cmd . "***<p>");
	}
}



//
// runTauDEMStreamNetFLD()
//
function runTauDEMStreamNetFLD($infel, $inp, $inad8, $insrc, $outord, $outtree, $outcoord, $outstream, $outw)
{
	// StreamNet -fel <felfile> -p <pfile> -ad8 <ad8file> -src <srcfile> -ord <ordfile> -tree <treefile> -coord<coordfile> -net <netfile> [ -netlyr netlayername] [ -o <outletfile>] [ -lyrname <layer name>] [ -lyrno <layer number>] -w <wfile> [ -sw]
        addToLog("TauDEM StreamNet with Outlet");
	global $globwebdir;
	$cmd = $globwebdir. "gisutils/taudembin/streamnet -fel " . $infel . " -p " . $inp . " -ad8 ". $inad8 . " -src " . $insrc . " -ord " . $outord . " -tree " . $outtree .  " -coord " . $outcoord . " -net " . $outstream . " -w ". $outw;

        addToLog('<br><br>'.$cmd.'<br><br>');
	//system($cmd, $rc);
	exec($cmd, $output, $rc);
        if ($rc !== 0) {
           addToLog("<p>***Error Could not execute: " . $rc . "***" . $cmd . "***<p>");
        }

}


//
// convjson2shp
//
function conjson2shp($srcjson, $desshpdir, $zone)
{
        addToLog("converting geojson to ESRI shapefile");
	$proj = "'+proj=utm +zone=" . $zone . " +datum=NAD83 +ellps=GRS80' ";
	mkdir($desshpdir);
	$fnshp = $desshpdir . "/fldbdy.shp"; 
        $cmd = "ogr2ogr -a_srs " . $proj . " -f 'ESRI Shapefile' " . $fnshp . " " .$srcjson;

        addToLog($cmd);
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
           addToLog("<p>***Error Could not execute: " . $rc . "***" . $cmd . "***<p>");
        }

}



//
// This runs TauDEM in watershed delineation without outlet
//
function runTauDEMFLD($workingDir, $zone)
{
	// The difference of this function and the with outlet
	// one was that, user started with a field boundary.
	// It runs a whole watershed delineation process with TauDEM.
	// So, the steps include:
	// 1. Get the extent of the field boundary.
	// 2. Update the session variable extent to include enough 
	// buffer, that the whole field can be covered by subareas.
	// 3. Then, extract DEM 
	// 4. Reproject dem
	// 5. Generate stream network
	// 6. Generate watersheds and subareas
	// 7. Determine the subareas covered by the field boundary.
	// 8. Determine the subareas and watershed relationships
	// 9. Update map files to display all watersheds
        // Step 3: Running grid net
        addToLog("<br><br>Running TauDEM: gridnet Without outlet <br><br>");
	// pFlowDir was generated in former steps.
	$pFlowDir =  $workingDir . "/pFlowDir.tif";
	
	$plen =  $workingDir . "/plen.tif";
        $tlen =  $workingDir . "/tlen.tif";
        $gord =  $workingDir . "/gord.tif";

        if (file_exists($plen)) {unlink($plen);}
        if (file_exists($tlen)) {unlink($tlen);}
        if (file_exists($gord)) {unlink($gord);}

        runTauDEMGridNetFLD($pFlowDir,$plen, $tlen, $gord);
        addToLog("<br>Finished Running TauDEM: gridnet Without outlet <br><br>");
	
        addToLog("<br><br>Running TauDEM: StreamNet without outlet <br><br>");
        $ad8ContArea =  $workingDir . "/ad8ContArea.tif";
        $srcStrNet =  $workingDir . "/srcStrNet.tif";


	$demfel = $workingDir . "/demfel.tif";
        $ord =  $workingDir . "/ord.tif";
        $tree =  $workingDir . "/tree.txt";
        $coord =  $workingDir . "/coord.tif";
        $strmshp =  $workingDir . "/stream.shp";
        $demw =  $workingDir . "/demw.tif";

        if (file_exists($ord)) {unlink($ord);}
        if (file_exists($tree)) {unlink($tree);}
        if (file_exists($coord)) {unlink($coord);}
        if (file_exists($strmshp)) {unlink($strmshp);}
        if (file_exists($demw)) {unlink($demw);}

        runTauDEMStreamNetFLD($demfel, $pFlowDir, $ad8ContArea, $srcStrNet, $ord, $tree, $coord, $strmshp, $demw);

        addToLog("<br>Finished Running TauDEM: StreamNet without outlet <br><br>");	

	// At last, the demw need to be converted to 8 bit from 32 bit.
	// This is because the mapserver supports the 8 bit better.
	// When I was trying to display demw directly, the raster was very
	// slow to display. 
	// Before converting, the demw.tif has a 0 value as the id of the
	// first subarea. When converting directly to 8 bit, the 0 values
	// will be treated as no data. I an not sure why. But I have to 
	// deal with this first.
	// Steps:
	// 1. get the maximum value of the demw.tif with gdalinfo -stats
	// 2. reclassify demw.tif to change 0 to maximum value + 1.
	// All these processing are only for display. The actual calculation
	// will still use the demw.
	// 3. convert the 32 bit map to 8 bit raster using gdal_translate -ot
	// Byte src.tif dest.tif command. 
	$demwstatsjson =  $workingDir . "/demwstats.json";
	if (file_exists($demwstatsjson)) {unlink($demwstatsjson);}

	gdalinfoStats($demw, $demwstatsjson);

	// Read in statistics json file to get the maximum subarea id
	$maxsubid = json_decode(file_get_contents($demwstatsjson), true)["bands"]["0"]["max"];

	// Reclassify the demw.tif to change 0 values int maxsubid + 10	i
	//$demw0tomax =  $workingDir . "/demw0tomax.tif";
	//if (file_exists($demw0tomax)) {unlink($demw0tomax);}

	//convertdemw0tomax($demw, $demw0tomax, $maxsubid);
	// Convert demw0tomax.tif from 32 to 8 bit raster
	//$demwmax8bit = $workingDir . "/demwmax8bit.tif";	
	//if(file_exists($demwmax8bit)){unlink($demwmax8bit);}

	//conv32to8bittif($demw0tomax, $demwmax8bit);
	// We also would like to show the id of the watershed, thus, a
	// shapefile was generated
	// This conversion will use demw.tif since we still want to use 
	// the original coding.
        //$subshpdir = $workingDir . "/esridemw";
        //if(is_dir($subshpdir)){delete_files($subshpdir);}
	
	//convtif2shp($demw, $subshpdir);	

	// Also convert the field boundary to shapefile for display
        // Convert json to shapefile
        $fldbdyjson = $workingDir . "/fieldbdy.json";
        $fldbdyshpdir = $workingDir . "/fldbdy";

        if (file_exists($fldbdyshpdir)){delete_files($fldbdyshpdir);}

        conjson2shp($fldbdyjson, $fldbdyshpdir, $zone);

	return true;
}

function getTauDEMStreamNetwork($workingDir, $critarea)
{
        $utmSliceFile = $workingDir . "/utmSlice.tif";
        $demfelFile = $workingDir . "/demfel.tif";

        if (file_exists($demfelFile)) {unlink($demfelFile);}

        runTauDEMPitRemove($utmSliceFile,$demfelFile);

        $pFlowDirFile =  $workingDir . "/pFlowDir.tif";
        $sd8SlopeFile =  $workingDir . "/sd8Slope.tif";
        $ad8ContAreaFile =  $workingDir . "/ad8ContArea.tif";
        $srcStrNetFile =  $workingDir . "/srcStrNet.tif";
        if (file_exists($pFlowDirFile)) {unlink($pFlowDirFile);}
        if (file_exists($sd8SlopeFile)) {unlink($sd8SlopeFile);}
        if (file_exists($ad8ContAreaFile)) {unlink($ad8ContAreaFile);}
        if (file_exists($srcStrNetFile)) {unlink($srcStrNetFile);}

        runTauDEMD8FlowDir($demfelFile,$pFlowDirFile, $sd8SlopeFile);
        runTauDEMAreaD8($pFlowDirFile,$ad8ContAreaFile);
        runTauDEMThreshold($ad8ContAreaFile, $srcStrNetFile, $critarea);

        return TRUE;
}


//
//  clipRasterbyShp
//
function clipRasterbyShp($srctif, $clipshp, $destif)
{
        addToLog("clippint tif by ESRI shapefile");

	$cmd = "gdalwarp -cutline " . $clipshp . " " . $srctif . " " . $destif;
        addToLog($cmd);
        exec($cmd,$output, $rc);
        if ($rc !== 0) {
           addToLog("<p>***Error Could not execute: " . $rc . "***" . $cmd . "***<p>");
        }

}

//
//  convTif2Asc($demwfld, $demwfldasc)
//
function convTif2Asc($srctif,$desasc)
{
        addToLog("clippint tif to ASC files");

	$cmd = "gdal_translate -of AAIGrid " . $srctif . " " . $desasc;
        addToLog($cmd);
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
           addToLog("<p>***Error Could not execute: " . $rc . "***" . $cmd . "***<p>");
        }

}



//
// getSubIDinFld($demwfldasc,$subidinfld)
//
function getSubIDinFld($srcasc,$destxt)
{
	global $globwebdir;       
	addToLog("Calculating the subarea IDs covered by Field boundary");
	$cmd = "python " . $globwebdir."gisutils/getUniqueSubNO.py " . $srcasc . " " . $destxt;
        addToLog($cmd);
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
           addToLog("<p>***Error Could not execute: " . $rc . "***" . $cmd . "***<p>");
        }
}



// 
// findFldSubarea($workingDir)
//
// Finding subareas covered by the field boundary
// This program find the subarea IDs covered by the field boundary and 
// create a subarea file (demw) that only contains the field boundary.
// This will take some steps:
// 1. Find the subareas covered by the field boundary: This is easy.
// 2. Find the relationships among subareas: 
//    To get this, the network output provided by TauDEM need to be
//    analyzed and see how the streams are connected.
//    Then, group them into one watershed.
// 3. reclassfiy the watershed based on new code and show them.
// 
//

//
// getSubIDinFld($demwfldasc,$subidinfld)
//
function getwatershedcoveredbyfld($workingDir)
{
        addToLog("Calculating the subarea IDs covered by Field boundary");
	global $globwebdir;
	$cmd = "python " . $globwebdir."gisutils/getWatershedsbyFld.py " . $workingDir . " " . $globwebdir;
        addToLog($cmd);
	exec($cmd, $output, $rc);
	if ($rc !== 0) {
           addToLog("<p>***Error Could not execute: " . $rc . "***" . $cmd . "***<p>");
        }
}



function findFldSubarea($workingDir, $zone){
	addToLog('Finding subareas covered by the field boundary.<br><br>');
	// Clip demw to field boundary 
	$demw = $workingDir.'/demw.tif';
	$demwfld = $workingDir . "/demwfld.tif";
	$fldbdyshp = $workingDir . "/fldbdy/fldbdy.shp";
	if (file_exists($demwfld)) {unlink($demwfld);}
	clipRasterbyShp($demw, $fldbdyshp, $demwfld);
	addToLog('<br>clip done<br>');
	// Then find out the unique subarea values covered by 
	// the field boundary.
	// convert demwfld from til to asc for processing:
	$demwfldasc = $workingDir . "/demwfld.asc";
	if (file_exists($demwfldasc)) {unlink($demwfldasc);}
	convTif2Asc($demwfld, $demwfldasc);

	// Get the unique numbers stored in this asc
        //$subidinfld = $workingDir . "/subidinfld.txt";
        //if (file_exists($subidinfld)) {unlink($subidinfld);}
	//getSubIDinFld($demwfldasc,$subidinfld);

	// Instead running the getSub nO, I developed
	// another program directly find the subareas and 
	// reclassify the demw to individual watersheds.

	// Make a directory to store all reclassified subareas
	// This folder will be used in the python script for getting
	// watershedconveredbyfld. 	
	$dirreclademw = $workingDir . "/reclademw";
	if(is_dir($dirreclademw)){delete_files($dirreclademw);}
	mkdir($dirreclademw, 0755);

	$wssubfld = $workingDir . "/wssubfld.txt";
	if (file_exists($wssubfld)){unlink($wssubfld);}	

        $unisubfld = $workingDir . "/unisubno.txt";
        if (file_exists($unisubfld)){unlink($unisubfld);}

	getwatershedcoveredbyfld($workingDir);

	//$allwsno = file($wssubfld);
	//echo '<br>all subs'. count($allwsno).'<br>';


	# After getting the rec, the layers will be converted to shapefiles
	# and 8bit raster
	$arr_fnreclademw = array_diff(scandir($dirreclademw), array('..', '.'));
	
	foreach ($arr_fnreclademw as &$value) {
		$srcrecdemw = $dirreclademw . '/' . $value;
		$bit8recdemw = $dirreclademw .'/b8' . $value;
		$shprec = $dirreclademw .'/shp' . explode(".", $value)[0];	

        	if(is_dir($shprec)){delete_files($shprec);}
		if (file_exists($bit8recdemw)){unlink($bit8recdemw);}

		// Two operation:
		// convert to b8t 8 and shapefile
		conv32to8bittif($srcrecdemw, $bit8recdemw);
		// Convert to shapefile
		convtif2shp($srcrecdemw, $shprec);
	}
	
	
	return true;

}











function updateMapFileFld($workingDir,$mapFile,$addNet,$addOutlet,$addSubCatch,$addFlow,$addRep,$addFldBdy,$zone,$baseM)
{
     global $globgisdir,$globwebdir;

     $baseMapFile = $baseM;
     $sessionMapFile = $workingDir . "/" . $mapFile;

     if (!copy($baseMapFile,$sessionMapFile))
     {addToLog("copy failed: " . $baseMapFile . " " . $sessionMapFile);}

    // Need to modify the session map file to pick up the approiate files
    $handle = fopen($sessionMapFile,"a");
    if ($handle) {
       if ($addNet) {
          fwrite($handle,"LAYER\nName network\nType RASTER\n");
          fwrite($handle,"DATA \"" . $workingDir . "/srcStrNet.tif\"\n");
          fwrite($handle,"OFFSITE  0 0 0\n");
          fwrite($handle,"STATUS OFF\n");
          fwrite($handle,"PROCESSING \"SCALE_BUCKETS=2\"\n");
          fwrite($handle,"CLASSITEM \"Value\"\n");
          fwrite($handle,"CLASS\nEXPRESSION ([pixel] > 0)\n");
          fwrite($handle,"STYLE\n");
          fwrite($handle,"COLOR 0 0 255\nEND\n");
          fwrite($handle,"END\n");
          fwrite($handle,"PROJECTION\n  \"proj=utm\"\n  \"zone=" . $zone . "\"\n   \"datum=NAD83\"\nEND\nEND\n");
          //fwrite($handle,"PROJECTION\n  \"init=epsg:4326\"\nEND\nEND\n");

       }
       
       if ($addOutlet) {
       
       
       
       }
       if ($addSubCatch) {
	
	// Get the wssubfiles name       
	// # After getting the rec, the layers will be converted to shapefiles
	//# and 8bit raster
	$dirreclademw = $workingDir . "/reclademw";
	//$arr_fnreclademw = array_diff(scandir($dirreclademw), array('..', '.'));
	$arr_d8reclademw = glob($dirreclademw . '/b8*');	
	for ($id8 = 1; $id8 <= count($arr_d8reclademw); $id8++) {
		fwrite($handle,"LAYER\nName watershed" . $id8 . "\nType RASTER\n");
         	fwrite($handle,"DATA \"" . $dirreclademw . "/b8recdemw" . $id8 . ".tif\"\n");
         	fwrite($handle,"TRANSPARENCY 70\n");
         	fwrite($handle,"OFFSITE  0 0 0\n");
         	fwrite($handle,"STATUS OFF\n");
         	fwrite($handle,"PROCESSING \"SCALE=0,255\"\n");
         	fwrite($handle,"CLASSITEM \"[pixel]\"\n");
         	$colors = fopen($globwebdir."olmap/classes.txt","r");
         	if ($colors) {
           		while (!feof($colors)) {
             			$cbuf = fgets($colors,256);
             			fwrite($handle,$cbuf);
           		}
           	fclose($colors);
         	}
         	fwrite($handle,"\nPROJECTION\n  \"proj=utm\"\n  \"zone=" . $zone . "\"\nEND\nEND\n");


		fwrite($handle,"LAYER\nName subareaid" . $id8 . "\nType polygon\n");
		fwrite($handle,"DATA \"" . $dirreclademw . "/shprecdemw" . $id8 . "/out\"\n");
		fwrite($handle,"STATUS OFF\n");
        	fwrite($handle,"LABELITEM \"DN\"\n");
        	fwrite($handle,"CLASS\n");
        	fwrite($handle,"OUTLINECOLOR 255 0 0\n");
        	fwrite($handle,"STYLE\n");
        	fwrite($handle,"OPACITY 20\n");
        	fwrite($handle,"WIDTH 1.0\n");
        	fwrite($handle,"COLOR 0 0 0\n");
        	fwrite($handle,"END\n");
        	fwrite($handle,"LABEL\nCOLOR 255 0 0\nSIZE 8\nEND\nEND\n");
        	fwrite($handle,"PROJECTION\n  \"proj=utm\"\n  \"zone=" . $zone . "\"\nEND\nEND\n");
	}
	       
                   
       
       
       }
       if ($addFlow) {

       }
       if ($addRep) {

       }
       if ($addFldBdy) {
	fwrite($handle,"\nLAYER\nName FldBdy\nType polygon\n");
	fwrite($handle,"DATA \"" . $workingDir . "/fldbdy/fldbdy\"\n");
	fwrite($handle,"STATUS OFF\n");
	fwrite($handle,"CLASS\n");
	fwrite($handle,"OUTLINECOLOR 255 255 0\n");
	fwrite($handle,"STYLE\n");
	fwrite($handle,"OPACITY 30\n");
	fwrite($handle,"WIDTH 3.0\n");
	fwrite($handle,"COLOR 255 255 0\n");
	fwrite($handle,"END\n");
	fwrite($handle,"END\n");
	fwrite($handle,"PROJECTION\n  \"proj=utm\"\n  \"zone=" . $zone . "\"\nEND\nEND\n");
       

       }
       fwrite($handle,"\nEND\n");

       fclose($handle);
    } else
       echo("could not open " . $sessionMapFile . "\n");
}







function updateMapFileOlt($workingDir,$mapFile,$addNet,$addOutlet,$addSubCatch,$addFlow,$addRep,$zone,$baseM)
{
     global $globgisdir,$globwebdir;

     $baseMapFile = $baseM;
     $sessionMapFile = $workingDir . "/" . $mapFile;

     if (!copy($baseMapFile,$sessionMapFile))
     {addToLog("copy failed: " . $baseMapFile . " " . $sessionMapFile);}

    // Need to modify the session map file to pick up the approiate files
    $handle = fopen($sessionMapFile,"a");
    if ($handle) {
       if ($addNet) {
          fwrite($handle,"LAYER\nName network\nType RASTER\n");
          fwrite($handle,"DATA \"" . $workingDir . "/srcStrNet.tif\"\n");
          fwrite($handle,"OFFSITE  0 0 0\n");
          fwrite($handle,"STATUS OFF\n");
          fwrite($handle,"PROCESSING \"SCALE_BUCKETS=2\"\n");
          fwrite($handle,"CLASSITEM \"Value\"\n");
          fwrite($handle,"CLASS\nEXPRESSION ([pixel] > 0)\n");
          fwrite($handle,"STYLE\n");
          fwrite($handle,"COLOR 0 0 255\nEND\n");
          fwrite($handle,"END\n");
          fwrite($handle,"PROJECTION\n  \"proj=utm\"\n  \"zone=" . $zone . "\"\n   \"datum=NAD83\"\nEND\nEND\n");
          //fwrite($handle,"PROJECTION\n  \"init=epsg:4326\"\nEND\nEND\n");

       }
       
       if ($addOutlet) {
       
       
       
       }
       if ($addSubCatch) {
	fwrite($handle,"LAYER\nName watershed\nType RASTER\n");
         fwrite($handle,"DATA \"" . $workingDir . "/demwmax8bit.tif\"\n");
         fwrite($handle,"TRANSPARENCY 70\n");
         fwrite($handle,"OFFSITE  0 0 0\n");
	 fwrite($handle,"STATUS OFF\n");
	 fwrite($handle,"PROCESSING \"SCALE=0,255\"\n");
         fwrite($handle,"CLASSITEM \"[pixel]\"\n");
         $colors = fopen($globwebdir."olmap/classes.txt","r");
         if ($colors) {
           while (!feof($colors)) {
             $cbuf = fgets($colors,256);
             fwrite($handle,$cbuf);
           }
           fclose($colors);
         }
         fwrite($handle,"\nPROJECTION\n  \"proj=utm\"\n  \"zone=" . $zone . "\"\nEND\nEND\n");

                   
        // Label hillslopeIDs
	fwrite($handle,"LAYER\nName SubareaIDs\nType polygon\n");
	fwrite($handle,"DATA \"" . $workingDir . "/esridemw/out\"\n");
	fwrite($handle,"STATUS OFF\n");
	fwrite($handle,"LABELITEM \"DN\"\n");
	fwrite($handle,"CLASS\n");
	fwrite($handle,"OUTLINECOLOR 255 0 0\n");
	fwrite($handle,"LABEL\nCOLOR 255 0 0\nSIZE 8\nEND\nEND\n");
	fwrite($handle,"PROJECTION\n  \"proj=utm\"\n  \"zone=" . $zone . "\"\nEND\nEND\n");
       
       
       }
       if ($addFlow) {

       }
       if ($addRep) {

       }
       fwrite($handle,"\nEND\n");

       fclose($handle);
    } else
       echo("could not open " . $sessionMapFile . "\n");
}













?>
