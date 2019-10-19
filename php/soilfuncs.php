<?php
//
// ssurgo.php
//
// Core routines for accessing SSURGO databases.
//
// Jim Frankenberger
// December 2010
// NSERL
//

require_once('System.php');
require_once 'HTTP/Request2.php';
require_once($globwebdir . 'lib/nusoap/nusoap.php');


class soilfuncs
{



    // This function was not used anymore since the retrieve of soil data 
    // from the NRCS web some times cause problems and the interface
    // is not running while setting up APEX.
    // The gssurgo data for the whole US with 30m resolution was 
    // projected to same projection as that of the DEM and stored in the 
    // /gis/gssurgo folder.
    // getSurgoGeometry()
    //
    // This gets the geometry for a set of mukeys. Once the vertices are found, try to create shape file and then reduce
    // to a raster.
    //
    public function getSurgoGeometry($utmzone,$workingDir)
    {
        // Updated for smaller area
        $fndemwinfojson = $workingDir . '/taudemlayers/demwstats.json';
        $demwinfo = json_decode(file_get_contents($fndemwinfojson), true);
        $wgs84Ext = $demwinfo['wgs84Extent']["coordinates"];
        // Coordinates in the gdaljson:
        // wgs84Extent->coordinates->[[[long, lat][][][]]]
        // there are extent arry for each polygon, each 
        // extent has four corners in the order of:
        // upperLeft, lowerLeft, lowerRight, upperRight.
        // Each corner has longitude and latitude.
        $ll = $wgs84Ext[0][1][0] . ',' . $wgs84Ext[0][1][1];
        $ur = $wgs84Ext[0][3][0] . ',' . $wgs84Ext[0][3][1];

        // This is the UTM version, we use the 4326 geographic coords version, UTM server does not seem to be consistent
        //$site = "http://sdmdataaccess.nrcs.usda.gov/Spatial/SDMNAD83UTM.wfs";
    
        global $globgisdir,$globPythonBin;
        $gmlfile = $workingDir . "/soils/soilsmap.gml";
        if (file_exists($gmlfile))
        {unlink($gmlfile); }

        // NRCS WFS server address
        $site = "https://SDMDataAccess.nrcs.usda.gov/Spatial/SDMWGS84Geographic.wfs";
   
        echo("Checking 1-1!!!");
        // Fill in the request packet
        $soilfilter = $workingDir . "/soils/soilfilter.txt";
        if (file_exists($soilfilter))
        {unlink($soilfilter); }

        $filter = 'FILTER=<Filter><BBOX><PropertyName>Geometry</PropertyName> <Box srsName=\'epsg:4326\'><coordinates>'. $ll . " " . $ur .   '</coordinates> </Box></BBOX></Filter>';
        $fp = fopen($soilfilter,"w");
        fwrite($fp,$filter);
        fclose($fp);

        $r= new Http_Request2($site);
        $url = $r->getUrl();
        $url->setQueryVariables(array('SERVICE' => 'WFS',
            'VERSION' => '1.0.0',
            'REQUEST' => 'GetFeature',
            'TYPENAME' => 'MapunitPoly',
            'FILTER' => $filter,
            'SRSNAME' => 'EPSG:4326',
            'OUTPUTFORMAT' => 'GML2'));
echo("Checking 1-23!!!");
        // Send the request to the NRCS Soil server
        $resp = $r->send ();
echo("Checking 1-22!!!");
        // Read back the response XML 
        $xmlstr = $resp->getBody();

        // Check if the request had an exception
        $pos = strpos($xmlstr,"<ServiceException>");
        if ($pos === false) {
        // Save the response packet to a file
            $fp = fopen($gmlfile, "w");
            fwrite($fp,$xmlstr);
            fclose($fp);
        } else {
            $error[]="<br><span style=\"color: red\">Request for SSURGO Geomtery returned an error. The filter request was: <br>";
            $error[]="<pre>" . $filter . "</pre>";
            $error[]="<br><pre>" . $xmlstr . "</pre><br></span>";
            return;
        }

        // If the shapefile exists, remove it
        $soilshp = $workingDir . "/soils/soils.shp";
        if (file_exists($soilshp))
        {unlink($soilshp); }

        // Convert the GML file into a shapefile, see GDAL library http://www.gdal.org
        putenv("XERCESC_NLS_HOME=/usr/share/xerces-c/msg");
        $proj = "'+proj=utm +zone=" . $utmzone . " +datum=NAD83 +ellps=GRS80' ";

        $cmd = "ogr2ogr -s_srs EPSG:4326 " . " -t_srs " . $proj . " " . $soilshp  . " " . $gmlfile;
        exec($cmd, $output, $rc); 
        if ($rc !== 0) {
              $error[]="<p>***Could not execute: " . $cmd . "***<p>";
        }   

        if (!file_exists($soilshp)) {
        // This is an indication that there are is no SSURGO data, so create a default soil map
            $error[]="<br>Error: could not create shape file soils.shp from soilsmap.gml<br>";
            return;
        } else {
        // Copy demw to soilgrid.tif
            $watershedbdy = $workingDir.'/taudemlayers/demw.tif';    
            $soilgrid = $workingDir.'/soils/soilgrid.tif';
            copy($watershedbdy, $soilgrid);
            // create a tiff template, data will be all undefined, see GDAL library 
            // burn the ssurgo soil ids into the grid, see GDAL library
            $cmd = "gdal_rasterize -l soils -a MUKEY " . $soilshp . " " . $soilgrid;
            exec($cmd, $output, $rc);
            if ($rc !== 0) {
                $error[]="<p>***Could not execute: " . $cmd . "***<p>";
                return;
            }

        }
        
        return TRUE;
    }



    public function extractGSSURGO_old($workingDir,$imgext, $utmzone)
    {
        global $globgisdir;

        $gssurgoFileFull = $globgisdir . "gssurgo30m/gssurgo30m.img";
        
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
        $soilcoords = $workingDir.'/soils/coords.txt';
        if (file_exists($soilcoords))
        {unlink($soilcoords); }

        $fp = fopen($soilcoords, "w");
        if ($fp) {
             fwrite($fp,$XLL2 . " " . $YUR . "\n");
             fwrite($fp,$XUR2 . " " . $YLL . "\n");
             fclose($fp);
        }

        $soilcoordsalb = $workingDir.'/soils/coordsalb.txt';

        $cmd = "gdaltransform -s_srs epsg:4326 -t_srs " . $albersProj . " < " . $soilcoords . " > " . $soilcoordsalb;

        exec($cmd, $output, $rc);
        if ($rc !== 0) {
              $error[]="<p>***Could not execute: " . $cmd . "***<p>";
        }
    // read the new coords
        $fp = fopen($soilcoordsalb,"r");
        if ($fp) {
                $buf = fgets($fp);
                $buf = trim($buf);
                sscanf($buf,"%f %f",$ll_x,$ll_y);
                $buf = fgets($fp);
                $buf = trim($buf);
                sscanf($buf,"%f %f",$ul_x,$ul_y);
                fclose($fp);
        }

        $sliceFile = $workingDir . "/soils/soilslice.tif";
        if (file_exists($sliceFile)) {
           unlink($sliceFile);
        }

        $projwin = $ll_x . " " . $ll_y . " " . $ul_x . " " . $ul_y;

        if (!file_exists($gssurgoFileFull)) {
            $error[]="Error: Landuse tile not found. [" . $nassFileFull . "][" . $globgisdir . "]";
        }

        $cmd = "gdal_translate -projwin " . $projwin . " " . $gssurgoFileFull . " " . $sliceFile;
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
        }

        // Reproject alber to utm
        $soilUtm = $workingDir . "/soils/soilutm.tif";
        if (file_exists($soilUtm)) {
           unlink($soilUtm);
        }

        $proj = "'+proj=utm +zone=" . $utmzone . " +datum=NAD83 +ellps=GRS80' ";
        //echo('<br>' . $proj.'<br>');
        $cmd = "gdalwarp -t_srs " . $proj . "-tr 30 30 -dstnodata -9999 " . $sliceFile . " " . $soilUtm;
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
        }

        return true;
    }


    public function extractSOIL2ext($workingDir,$imgext)
    {

        global $globgisdir, $globPythonBin;

        $gssurgoFull = $globgisdir . "gssurgo30m/gssurgo30m.img";
        $sliceFile = $workingDir . "/soils/soilSlice.tif";

        sscanf($imgext, "%f %f %f %f", $XLL, $YLL, $XUR, $YUR);
        $projwin = $XLL . " " . $YUR . " " . $XUR . " " . $YLL;

        $cmd = $globPythonBin . "gdal_merge.py -o " . $sliceFile . " -ul_lr " . $projwin . " " . $gssurgoFull;

        addToLog("<br>About to run:" . $cmd . "<br>");
        addToLog("\n");
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            addToLog("<p>***Error Could not execute: " . $rc . "***" . $cmd . "***<p>");
            return FALSE;
        }

        return TRUE;

    }

    //
    // reprojectSoil2UTM()
    //
    // This reprojects the landuse chunk into UTM coordinates with the approiate zone.
    // Output is in file: utmSlice.tif
    //
    public function reprojectSoil2UTM($workingDir,$zone,$hasProj)
    {
        $sliceFile = $workingDir . "/soils/soilSlice.tif";
        $utmSliceFile = $workingDir . "/soils/soilUTMext.tif";
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
            addToLog("<p>***Could not execute: " . $cmd . "***<p>");
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
            return FALSE;
        }

        return TRUE;
    }




    public function extractSoil2ws($workingDir)
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
        $soilutm = $workingDir . '/soils/soilUTMext.tif';
        $soildemwext = $workingDir . '/soils/soilutmdemwext.tif';
        if (file_exists($soildemwext))
        {unlink($soildemwext); }

        $cmd = "gdal_translate -projwin " . $projwin . " " . $soilutm . " " . $soildemwext;
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            addToLog("<p>***Could not execute: " . $cmd . "***<p>");
            return False;
        }
        return true;

    }





}



?>
