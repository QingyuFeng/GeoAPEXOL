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



//
// getSurgoGeometry()
//
// This gets the geometry for a set of mukeys. Once the vertices are found, try to create shape file and then reduce
// to a raster.
//
function getSurgoGeometry($ll,$ur,$utmzone,$workingDir)
{

	// This is the UTM version, we use the 4326 geographic coords version, UTM server does not seem to be consistent
	//$site = "http://sdmdataaccess.nrcs.usda.gov/Spatial/SDMNAD83UTM.wfs";
	
	global $globgisdir,$globPythonBin;
	$gmlfile = $workingDir . "/soils/soilsmap.gml";
	if (file_exists($gmlfile))
      	{unlink($gmlfile); }

 	// NRCS WFS server address
	$site = "https://SDMDataAccess.nrcs.usda.gov/Spatial/SDMWGS84Geographic.wfs";
   

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

	// Send the request to the NRCS Soil server
	$resp = $r->send ();

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
		$demw = $workingDir.'/demw.tif';	
		$soilgrid = $workingDir.'/soils/soilgrid.tif';
		copy($demw, $soilgrid);


		// create a tiff template, data will be all undefined, see GDAL library 
		// burn the ssurgo soil ids into the grid, see GDAL library
		$cmd = "gdal_rasterize -l soils -a MUKEY " . $soilshp . " " . $soilgrid;
		exec($cmd, $output, $rc);
		if ($rc !== 0) {
              		$error[]="<p>***Could not execute: " . $cmd . "***<p>";
        	}

		// Clip soil raster to the boundary of watershed
		$demwshp = $workingDir.'/esridemw/out.shp';
		$soilgridwsclip = $workingDir.'/soils/soilws.tif';
		if (file_exists($soilgridwsclip))
        	{unlink($soilgridwsclip); }
		clipRasterbyShp($soilgrid, $demwshp, $soilgridwsclip);
		// Convert tif to asc for processing
		$soilgridwsasc = $workingDir.'/soils/soilws.asc';
                if (file_exists($soilgridwsasc))
                {unlink($soilgridwsasc); }
		convTif2Asc($soilgridwsclip,$soilgridwsasc);

	}



	return true;
}














?>
