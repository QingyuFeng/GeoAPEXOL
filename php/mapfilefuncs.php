<?php
//
// globals.php
// 
// These should be used in php code to make moving to other machines easier.
//
// 
//
//


class mapfilefuncs 
{
    public function updateMapFileOlt($workingDir,$mapFile,$addNet,$addQSNP,$addSubCatch,$addFlow,$addRep,$zone,$baseM, $scenariolst)
    {
        global $globgisdir,$globwebdir;

        $baseMapFile = $baseM;
        $sessionMapFile = $workingDir . "/" . $mapFile;

        if (!copy($baseMapFile,$sessionMapFile))
        {addToLog("copy failed: " . $baseMapFile . " " . $sessionMapFile);}

        // Need to modify the session map file to pick up the approiate files
        $handle = fopen($sessionMapFile,"a");
        if ($handle) {
            if ($addNet) 
            {
            fwrite($handle,"LAYER\nName network\nType RASTER\n");
            fwrite($handle,"DATA \"" . $workingDir . "/taudemlayers/srcStrNet.tif\"\n");
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


        if ($addQSNP) {

        $fdapexoutlay = $workingDir . "/apexoutjsonmap";
        $numofscen = count($scenariolst)/2;
        for ($sid = 0; $sid<$numofscen; $sid++)
        {
            $sid2 = $sid+1;
            $scenname = $scenariolst["s". (string)$sid2]; 
        fwrite($handle,"LAYER\nName " . $scenname  . "runoff\nType RASTER\n");
        fwrite($handle,"DATA \"" . $fdapexoutlay . "/aaq1_" . $scenname  . ".tif\"\n");
        fwrite($handle,"TRANSPARENCY 70\n");
        fwrite($handle,"OFFSITE  0 0 0\n");
        fwrite($handle,"STATUS OFF\n");
        fwrite($handle,"PROCESSING \"SCALE=0,255\"\n");
        fwrite($handle,"CLASSITEM \"[pixel]\"\n");
        $colors = fopen($globwebdir."olmap/qclass.txt","r");
        if ($colors) {
           while (!feof($colors)) {
             $cbuf = fgets($colors,256);
             fwrite($handle,$cbuf);
           }
           fclose($colors);
         }
         fwrite($handle,"\nPROJECTION\n  \"proj=utm\"\n  \"zone=" . $zone . "\"\nEND\nEND\n");

        fwrite($handle,"LAYER\nName " . $scenname  . "erosion\nType RASTER\n");
        fwrite($handle,"DATA \"" . $fdapexoutlay . "/aarsl21_" . $scenname  . ".tif\"\n");
        fwrite($handle,"TRANSPARENCY 70\n");
        fwrite($handle,"OFFSITE  0 0 0\n");
        fwrite($handle,"STATUS OFF\n");
        fwrite($handle,"PROCESSING \"SCALE=0,255\"\n");
        fwrite($handle,"CLASSITEM \"[pixel]\"\n");
        $colors = fopen($globwebdir."olmap/seclass.txt","r");
        if ($colors) {
           while (!feof($colors)) {
             $cbuf = fgets($colors,256);
             fwrite($handle,$cbuf);
           }
           fclose($colors);
         }
         fwrite($handle,"\nPROJECTION\n  \"proj=utm\"\n  \"zone=" . $zone . "\"\nEND\nEND\n");

        fwrite($handle,"LAYER\nName " . $scenname  . "tn\nType RASTER\n");
        fwrite($handle,"DATA \"" . $fdapexoutlay . "/aatn1_" . $scenname  . ".tif\"\n");
        fwrite($handle,"TRANSPARENCY 70\n");
        fwrite($handle,"OFFSITE  0 0 0\n");
        fwrite($handle,"STATUS OFF\n");
        fwrite($handle,"PROCESSING \"SCALE=0,255\"\n");
        fwrite($handle,"CLASSITEM \"[pixel]\"\n");
        $colors = fopen($globwebdir."olmap/nclass.txt","r");
        if ($colors) {
           while (!feof($colors)) {
             $cbuf = fgets($colors,256);
             fwrite($handle,$cbuf);
           }
           fclose($colors);
         }
         fwrite($handle,"\nPROJECTION\n  \"proj=utm\"\n  \"zone=" . $zone . "\"\nEND\nEND\n");

        fwrite($handle,"LAYER\nName " . $scenname  . "tp\nType RASTER\n");
        fwrite($handle,"DATA \"" . $fdapexoutlay . "/aatp1_" . $scenname  . ".tif\"\n");
        fwrite($handle,"TRANSPARENCY 70\n");
        fwrite($handle,"OFFSITE  0 0 0\n");
        fwrite($handle,"STATUS OFF\n");
        fwrite($handle,"PROCESSING \"SCALE=0,255\"\n");
        fwrite($handle,"CLASSITEM \"[pixel]\"\n");
        $colors = fopen($globwebdir."olmap/pclass.txt","r");
        if ($colors) {
           while (!feof($colors)) {
             $cbuf = fgets($colors,256);
             fwrite($handle,$cbuf);
           }
           fclose($colors);
         }
         fwrite($handle,"\nPROJECTION\n  \"proj=utm\"\n  \"zone=" . $zone . "\"\nEND\nEND\n");
        
        }// End of for loop for scenarios

        }



        if ($addSubCatch) {
        $taudemlayersfd = $workingDir . "/taudemlayers";
        $dirreclademw = $taudemlayersfd . "/reclademw";
        fwrite($handle,"LAYER\nName watershed\nType RASTER\n");
        fwrite($handle,"DATA \"" . $dirreclademw . "/b8recdemw1.tif\"\n");
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
        fwrite($handle,"DATA \"" . $dirreclademw . "/shprecdemw1/out\"\n");
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
        {echo("could not open " . $sessionMapFile . "\n");}
}




    public function updateMapFileFld($workingDir,$mapFile,$addNet,$addQSNP,$addSubCatch,$addFldQSNP,$addRep,$addFldBdy,$zone,$baseM, $scenariolst)
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
          fwrite($handle,"DATA \"" . $workingDir . "/taudemlayers/srcStrNet.tif\"\n");
          fwrite($handle,"OFFSITE  0 0 0\n");
          fwrite($handle,"STATUS OFF\n");
          fwrite($handle,"PROCESSING \"SCALE_BUCKETS=2\"\n");
          fwrite($handle,"CLASSITEM \"Value\"\n");
          fwrite($handle,"CLASS\nEXPRESSION ([pixel] > 0)\n");
          fwrite($handle,"STYLE\n");
          fwrite($handle,"COLOR 0 0 255\nEND\n");
          fwrite($handle,"END\n");
          fwrite($handle,"PROJECTION\n  \"proj=utm\"\n  \"zone=" . $zone . "\"\n   \"datum=NAD83\"\nEND\nEND\n");

       }

          if ($addQSNP) {
       
        $fdapexoutlay = $workingDir . "/apexoutjsonmap";
		$numofscen = count($scenariolst)/2;
        for ($sid = 0; $sid<$numofscen; $sid++)
        {
            $sid2 = $sid+1;
            $scenname = $scenariolst["s". (string)$sid2];
		
			$arrws = glob($fdapexoutlay . '/aaq*_' . $scenname);
			for ($idws = 1; $idws <= count($arrws); $idws++) {
				fwrite($handle,"LAYER\nName " . $scenname . "aaq" . $idws . "\nType RASTER\n");
				fwrite($handle,"DATA \"" . $fdapexoutlay . "/aaq" . $idws . "_" . $scenname . ".tif\"\n");
				fwrite($handle,"TRANSPARENCY 70\n");
				fwrite($handle,"OFFSITE  0 0 0\n");
				fwrite($handle,"STATUS OFF\n");
				fwrite($handle,"PROCESSING \"SCALE=0,255\"\n");
				fwrite($handle,"CLASSITEM \"[pixel]\"\n");
				$colors = fopen($globwebdir."olmap/qclass.txt","r");
				if ($colors) {
					while (!feof($colors)) {
						$cbuf = fgets($colors,256);
						fwrite($handle,$cbuf);
					}
				fclose($colors);
				}
				fwrite($handle,"\nPROJECTION\n  \"proj=utm\"\n  \"zone=" . $zone . "\"\nEND\nEND\n");
				fwrite($handle,"LAYER\nName " . $scenname . "aarsl2" . $idws . "\nType RASTER\n");
                fwrite($handle,"DATA \"" . $fdapexoutlay . "/aarsl2" . $idws . "_" . $scenname . ".tif\"\n");
                fwrite($handle,"TRANSPARENCY 70\n");
				fwrite($handle,"OFFSITE  0 0 0\n");
				fwrite($handle,"STATUS OFF\n");
				fwrite($handle,"PROCESSING \"SCALE=0,255\"\n");
				fwrite($handle,"CLASSITEM \"[pixel]\"\n");
				$colors = fopen($globwebdir."olmap/seclass.txt","r");
				if ($colors) {
					while (!feof($colors)) {
						$cbuf = fgets($colors,256);
						fwrite($handle,$cbuf);
					}
				fclose($colors);
				}
				fwrite($handle,"\nPROJECTION\n  \"proj=utm\"\n  \"zone=" . $zone . "\"\nEND\nEND\n");

				fwrite($handle,"LAYER\nName " . $scenname . "aatn" . $idws . "\nType RASTER\n");
                fwrite($handle,"DATA \"" . $fdapexoutlay . "/aatn" . $idws . "_" . $scenname . ".tif\"\n");
                fwrite($handle,"TRANSPARENCY 70\n");
				fwrite($handle,"OFFSITE  0 0 0\n");
				fwrite($handle,"STATUS OFF\n");
				fwrite($handle,"PROCESSING \"SCALE=0,255\"\n");
				fwrite($handle,"CLASSITEM \"[pixel]\"\n");
				$colors = fopen($globwebdir."olmap/nclass.txt","r");
				if ($colors) {
					while (!feof($colors)) {
						$cbuf = fgets($colors,256);
						fwrite($handle,$cbuf);
					}
				fclose($colors);
				}
				fwrite($handle,"\nPROJECTION\n  \"proj=utm\"\n  \"zone=" . $zone . "\"\nEND\nEND\n");

				fwrite($handle,"LAYER\nName " . $scenname . "aatp" . $idws . "\nType RASTER\n");
                fwrite($handle,"DATA \"" . $fdapexoutlay . "/aatp" . $idws . "_" . $scenname . ".tif\"\n");
                fwrite($handle,"TRANSPARENCY 70\n");
				fwrite($handle,"OFFSITE  0 0 0\n");
				fwrite($handle,"STATUS OFF\n");
				fwrite($handle,"PROCESSING \"SCALE=0,255\"\n");
				fwrite($handle,"CLASSITEM \"[pixel]\"\n");
				$colors = fopen($globwebdir."olmap/pnlass.txt","r");
				if ($colors) {
					while (!feof($colors)) {
						$cbuf = fgets($colors,256);
						fwrite($handle,$cbuf);
					}
				fclose($colors);
				}
				fwrite($handle,"\nPROJECTION\n  \"proj=utm\"\n  \"zone=" . $zone . "\"\nEND\nEND\n");

				}// End of ws loop
		}// End of scenario loop
	}// End of adding QSNP map

       
       
       
       
       
       if ($addSubCatch) {

    // Get the wssubfiles name
    // # After getting the rec, the layers will be converted to shapefiles
    //# and 8bit raster
    $taudemlayersfd = $workingDir . "/taudemlayers";
    $dirreclademw = $taudemlayersfd . "/reclademw";
    //$arr_fnreclademw = array_diff(scandir($dirreclademw), array('..', '.'));
    $arr_d8reclademw = glob($dirreclademw . '/b8wsnorecws*');
    for ($id8 = 1; $id8 <= count($arr_d8reclademw); $id8++) {
        fwrite($handle,"LAYER\nName watershed" . $id8 . "\nType RASTER\n");
            fwrite($handle,"DATA \"" . $dirreclademw . "/b8wsnorecws" . $id8 . ".tif\"\n");
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
       
        if ($addFldQSNP) {

        $fdapexoutlay = $workingDir . "/apexoutjsonmap";
        
		$numofscen2 = count($scenariolst)/2;
        for ($sid = 0; $sid<$numofscen2; $sid++)
        {
            $sid2 = $sid+1;
            $scenname = $scenariolst["s". (string)$sid2];
			fwrite($handle,"LAYER\nName " . $scenname  . "fldaaq\nType RASTER\n");
			fwrite($handle,"DATA \"" . $fdapexoutlay . "/" . $scenname . "fldaaq.tif\"\n");
			fwrite($handle,"TRANSPARENCY 70\n");
			fwrite($handle,"OFFSITE  0 0 0\n");
			fwrite($handle,"STATUS OFF\n");
			fwrite($handle,"PROCESSING \"SCALE=0,255\"\n");
			fwrite($handle,"CLASSITEM \"[pixel]\"\n");
			$colors = fopen($globwebdir."olmap/qclass.txt","r");
			if ($colors) {
				while (!feof($colors)) {
					$cbuf = fgets($colors,256);
					fwrite($handle,$cbuf);
				}
			fclose($colors);
			}
			fwrite($handle,"\nPROJECTION\n  \"proj=utm\"\n  \"zone=" . $zone . "\"\nEND\nEND\n");
			

			fwrite($handle,"LAYER\nName " . $scenname . "fldaarsl2 \nType RASTER\n");
			fwrite($handle,"DATA \"" . $fdapexoutlay . "/" . $scenname . "fldaarsl2.tif\"\n");
			fwrite($handle,"TRANSPARENCY 70\n");
			fwrite($handle,"OFFSITE  0 0 0\n");
			fwrite($handle,"STATUS OFF\n");
			fwrite($handle,"PROCESSING \"SCALE=0,255\"\n");
			fwrite($handle,"CLASSITEM \"[pixel]\"\n");
			$colors = fopen($globwebdir."olmap/seclass.txt","r");
			if ($colors) {
				while (!feof($colors)) {
					$cbuf = fgets($colors,256);
					fwrite($handle,$cbuf);
				}
			fclose($colors);
			}
			fwrite($handle,"\nPROJECTION\n  \"proj=utm\"\n  \"zone=" . $zone . "\"\nEND\nEND\n");

			fwrite($handle,"LAYER\nName " . $scenname . "fldaatn \nType RASTER\n");
			fwrite($handle,"DATA \"" . $fdapexoutlay . "/" . $scenname . "fldaatn.tif\"\n");
			fwrite($handle,"TRANSPARENCY 70\n");
			fwrite($handle,"OFFSITE  0 0 0\n");
			fwrite($handle,"STATUS OFF\n");
			fwrite($handle,"PROCESSING \"SCALE=0,255\"\n");
			fwrite($handle,"CLASSITEM \"[pixel]\"\n");
			$colors = fopen($globwebdir."olmap/nclass.txt","r");
			if ($colors) {
				while (!feof($colors)) {
					$cbuf = fgets($colors,256);
					fwrite($handle,$cbuf);
				}
			fclose($colors);
			}
			fwrite($handle,"\nPROJECTION\n  \"proj=utm\"\n  \"zone=" . $zone . "\"\nEND\nEND\n");

			fwrite($handle,"LAYER\nName " . $scenname . "fldaatp \nType RASTER\n");
			fwrite($handle,"DATA \"" . $fdapexoutlay . "/" . $scenname . "fldaatp.tif\"\n");
			fwrite($handle,"TRANSPARENCY 70\n");
			fwrite($handle,"OFFSITE  0 0 0\n");
			fwrite($handle,"STATUS OFF\n");
			fwrite($handle,"PROCESSING \"SCALE=0,255\"\n");
			fwrite($handle,"CLASSITEM \"[pixel]\"\n");
			$colors = fopen($globwebdir."olmap/pnlass.txt","r");
			if ($colors) {
				while (!feof($colors)) {
					$cbuf = fgets($colors,256);
					fwrite($handle,$cbuf);
				}
			fclose($colors);
			}
			fwrite($handle,"\nPROJECTION\n  \"proj=utm\"\n  \"zone=" . $zone . "\"\nEND\nEND\n");
        }// End of scenario loop
       }
       
       
       
       if ($addRep) {

       }


    if ($addFldBdy) {
    fwrite($handle,"\nLAYER\nName FldBdy\nType polygon\n");
    fwrite($handle,"DATA \"" . $workingDir . "/gislayers/fldbdy/fldbdy\"\n");
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






}

?>
