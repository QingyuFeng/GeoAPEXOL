<?php
//
// APEX WEB GIS interface
//
// This uses the Openlayers Javascript library to setup and display WEPP GIS runs. A few things are
// required:
//   2. OpenLayers javascript library. (openlayers.org)
//   3. Mapserver install. (mapserver.org)
//   4. apex databases, DEM, WEPP support php pages and programs.
//
// September 2018 
// Qingyu Feng 
// Department of Agricultural and Biological Engineering
// Purdue University
// 225 South University Street
// West Lafayette, IN47907
//
require_once("config.php");
	
?>



<!DOCTYPE html>
<html class="no-js">

<head>
	<title>Geo APEX Online</title>
    <meta charset='UTF-8'>


    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="lib/custom/style.css" />
    <link rel="stylesheet" type="text/css" href="lib/custom/legendcell.css" />
	<link rel="stylesheet" href="lib/ol.css" type="text/css" />
	<link rel="stylesheet" href="lib/ol-layerswitcher.css" type="text/css" />
	<link rel="stylesheet" href="lib/ol-popup.css" type="text/css" />

	<script language="JavaScript" src ="lib/proj4js.js"></script>
	<script language="JavaScript" src="lib/ol.js"></script>
	<script language="JavaScript" src="lib/ol-layerswitcher.js"></script>
	<script language="JavaScript" src="lib/ol-popup.js"></script>
	<script language="JavaScript" src="javascripts/panes.js"></script>
	<script language="Javascript" src="lib/proj4.js"></script>
	<script language="javascript" src="lib/proj4-src.js"></script>
	<script language="Javascript" src="lib/jquery-3.3.1.js"></script>

<?php
   
   include('php/olmap.php');
?>

</head>


<body onload="initol()">
        <?php
        //check for any errors
        if(isset($error)){
            echo("<h2>");
            echo('Error messages: <br>');
            	foreach($error as $err){
                	echo '<p class="bg-danger">'.$err.'</p>';
                }
            echo("</h2>");
            
        }
        
        ?>


<form method="POST" name="mapserv" action="index.php">

<input type="hidden" name="DOZOOM" value="<?=$_SESSION["SSVAR"]["izoom"]?>">
<input type="hidden" name="DOSTVR" value="0">
<input type="hidden" name="DOSTRM" value="0">
<input type="hidden" name="EXTENT" value="0">
<input type="hidden" name="DOWSOT" value="0">
<input type="hidden" name="JSONOT" value="0">
<input type="hidden" name="JSO3857" value="0">

<input type="hidden" name="EXTENTVIEW" value="0">


<input type="hidden" name="DOWSFD" value="0">
<input type="hidden" name="JSONFD" value="0">
<input type="hidden" name="JSF3857" value="0">

<input type="hidden" name="DORAPEX" value="0">
<!--Start of zoom control containerr  -->
<div id="zoomctnr">
	<table style="width:100%">
	<!-- First table: zoom to user location-->
    <tr>        
        <td  <?php if (($_SESSION["SSVAR"]["fldstep"] > 1) or ($_SESSION["SSVAR"]["oltstep"]> 2)) {echo " colspan = \"2\""; }?> style="width:100%">
            Zoom to Zip Code or City,State: (Example: 47906 or Pullman,WA) <br>
<input class="button" type="button" value="Start Over" name="startoverbtn" onClick="javascript:doStartOver();">
        <input type="text" name="ZLOC" id="zoomLocation" onkeypress="javascript:checkEnter(event);" size="50">
			<input class="button" type="button" value="Go" name="btnZoom" onClick="javascript:doSearch();">
		</td>

	</tr>
        
    <tr>
        <td <?php if (($_SESSION["SSVAR"]["fldstep"] > 1) or ($_SESSION["SSVAR"]["oltstep"]> 2)) {echo " colspan = \"1\" style=\"width:60%\" display:inline-block "; }?> style="width:60%">
        <div id="tags"></div>
  		<div id="shortdesc"></div>
		<div id="map" class="smallmap"></div>
        </td>              		
            <?php if (($_SESSION["SSVAR"]["fldstep"] > 1) or ($_SESSION["SSVAR"]["oltstep"]> 2)) { 

            // Appear a new td
            echo("<td  colspan = \"1\" style=\"width:40%\" display:inline-block>");

            // Get the legend file
            
            $fdapexoutlay = $workingDir . "/apexoutjsonmap";
        $fnlgd = $fdapexoutlay . "/susrsltclassesglobal.json";
        $jslgd = json_decode(file_get_contents($fnlgd), true);

        echo("<table display:inline-block>");
        echo("<tr> <td style=\"width:10%\" colspan= \"2\">Runoff (mm)</td>");
        echo("<td style=\"width:10%\" colspan= \"2\">Erosion (t/ha)</td>");
        echo("<td style=\"width:10%\" colspan= \"2\">Total N (kg/ha)</td>");
        echo("<td style=\"width:10%\" colspan= \"2\">Total P (kg/ha)</td>");
        echo("<tr>");
        for ($lidx=1;$lidx<=10; $lidx++ )
        {
            $lv = "level" . $lidx;
            echo("<tr>");
            $rmin = number_format((float)$jslgd["runoff"][$lv]["min"], 0, '.', '');
            $rmax = number_format((float)$jslgd["runoff"][$lv]["max"], 0, '.', '');    
             
            $cssid = "rlvl" . $lidx;
            echo("<td style=\"width:5%\" display:inline-block id= \"".$cssid."\"></td>");
            echo("<td style=\"width:5%\" display:inline-block>" . $rmin. " to " . $rmax . "</td> ");

            $emin = number_format((float)$jslgd["soilerosion"][$lv]["min"], 2, '.', '');
            $emax = number_format((float)$jslgd["soilerosion"][$lv]["max"], 2, '.', '');
            $cssid = "selvl" . $lidx;
            echo("<td style=\"width:5%\" display:inline-block id= \"".$cssid."\"></td>");
            echo("<td style=\"width:5%\" display:inline-block>" . $emin. " to " . $emax . "</td> ");


            $nmin = number_format((float)$jslgd["nitrogen"][$lv]["min"], 2, '.', '');
            $nmax = number_format((float)$jslgd["nitrogen"][$lv]["max"], 2, '.', '');
            $cssid = "nlvl" . $lidx;
            echo("<td style=\"width:5%\" display:inline-block id= \"".$cssid."\"></td>");
            echo("<td style=\"width:5%\" display:inline-block>" . $nmin. " to " . $nmax . "</td> ");

            $pmin = number_format((float)$jslgd["phosphorus"][$lv]["min"], 2, '.', '');
            $pmax = number_format((float)$jslgd["phosphorus"][$lv]["max"], 2, '.', '');
            
            $cssid = "plvl" . $lidx;
            echo("<td style=\"width:5%\" display:inline-block id= \"".$cssid."\"></td>");
            echo("<td style=\"width:5%\" display:inline-block>" . $pmin. " to " . $pmax . "</td> ");

            echo("</tr>");
        }

        echo("</table>");

        echo("</td>"); 
        
             } ?>

			</tr>

                <tr>
                       <td<?php if (($_SESSION["SSVAR"]["fldstep"] > 1) or ($_SESSION["SSVAR"]["oltstep"]> 2)) {echo " colspan = \"2\""; }?>>
                       Critical Source Area (<?=$areaStr?>):&nbsp;<input type="text" name="CRIT" size="10" value=<?=$_SESSION["SSVAR"]["criticalarea"]?>>  <a href="javascript:helpCSA();"><img alt="Help!"  src="/images/help-icon.jpg" /></a>
Tile drainge depth (<?=$lenStr?>):<input type="text" name="TILED" size="15" value=<?=$_SESSION["SSVAR"]["tiledep"]?>>
                        </td>
                </tr>
                <tr>
                        <td<?php if (($_SESSION["SSVAR"]["fldstep"] > 1) or ($_SESSION["SSVAR"]["oltstep"]> 2)) {echo " colspan = \"2\""; }?>>
                                <p>Simulate a watershed</p>
                        </td>
                </tr>
                <tr>
                        <td<?php if (($_SESSION["SSVAR"]["fldstep"] > 1) or ($_SESSION["SSVAR"]["oltstep"]> 2)) {echo " colspan = \"2\""; }?>>
                               <input class="button" type="button" value="Stream Network" name="strnetButton"  <?php if ($_SESSION["SSVAR"]["fldstep"] > 0){ ?> disabled <?php   } ?> onClick="javascript:doStreamNet();">
                                <input class="button" type="button" value="Set Outlet" name="outletButton"  <?php if ($_SESSION["SSVAR"]["oltstep"] < 1){ ?> disabled <?php   } ?>  onClick="javascript:enableDrawOutlet();">
                                <input class="button" type="button" value="Get watersheds" name="wsoltButton"   <?php if ($_SESSION["SSVAR"]["oltstep"] < 2){ ?> disabled <?php   } ?>  onClick="javascript:doWatershedOlt();">
                        </td>
                </tr>

        <!-- Third Table: Watershed Delineation with field boundary-->
                <tr>
                    <td<?php if (($_SESSION["SSVAR"]["fldstep"] > 1) or ($_SESSION["SSVAR"]["oltstep"]> 2)) {echo " colspan = \"2\""; }?>>
                         <p>Simulate a Field</p>
                    </td>
                </tr>
                <tr>
                    <td<?php if (($_SESSION["SSVAR"]["fldstep"] > 1) or ($_SESSION["SSVAR"]["oltstep"]> 2)) {echo " colspan = \"2\""; }?>>
                         <input class="button" type="button" value="Draw a field boundary" name="fldButton"  <?php if ($_SESSION["SSVAR"]["oltstep"] > 0){ ?> disabled <?php   } ?> onClick="javascript:enableDrawBoundary();">
                         <input class="button" type="button" value="Get watersheds" name="wsfldbButton"  <?php if ($_SESSION["SSVAR"]["fldstep"] < 1){ ?> disabled <?php   } ?>  onClick="javascript:doWatershedFld();">
                    </td>
               </tr>

                <tr>
                    <td <?php if (($_SESSION["SSVAR"]["fldstep"] > 1) or ($_SESSION["SSVAR"]["oltstep"]> 2)) {echo " colspan = \"2\""; }?>>
                    <?php if (($_SESSION["SSVAR"]["fldstep"] > 0) or ($_SESSION["SSVAR"]["oltstep"]> 1)) { ?>
                    <input type="checkbox" name="scenariolst[]" value="nass2016" checked > NASS 2016
                    <input type="checkbox" name="scenariolst[]" value="fallow"> Fallow
                    <input type="checkbox" name="scenariolst[]" value="peregrass"> Perennial Grass
                    <input type="checkbox" name="scenariolst[]" value="trees"> Tree
                    <input class="button" type="button" value="Run APEX Model" name="runAPFldButton" onClick="javascript:runAPEX();">            
                        <?php } ?>

                    </td>
                </tr>

        </table>





</div> 
<!-- End of contol container: ctlctnr -->

<br>
<!-- Start of results for watershed div -->
<div id="resulttable" style="display:<?php echo $idsp_fldtb;?>">
<!-- Start of results for watershed div -->
<?php
if ($_SESSION["SSVAR"]["oltstep"] > 2)
{
    // Get the number of subareas
    $totalsubno = count($TBQSNPArray["s1"]["1"]);
    echo("<p align=center>Annual Average Surface Runoff (mm)</p>");
    echo("<table style=\"width:100%\">"); 
    // Create the table head
    echo("<tr><td style=\"width:10%\">Subarea</td>");
    for ($subid = 0; $subid <$totalsubno;$subid++){
        $subno = $subid+1;
        echo("<td style=\"width:5%\">" . $subno  . "</td>");
    }
    echo("</tr>");
    // One row for one scenario
    $scenariolst = $_SESSION["SSVAR"]["runscenario"];
    $numofscen2 = count($scenariolst)/2;
    for ($sid = 0; $sid<$numofscen2; $sid++)
    {
        $sid2 = $sid+1;
        $scenkey = "s". (string)$sid2;
        $scennamefull = $scenariolst[$scenkey. "_full"];
        $scenname = $scenariolst[$scenkey];
        echo("<tr><td style=\"width:10%\">". $scennamefull ."</td>");
        for ($subid = 0; $subid <$totalsubno;$subid++){
            $subno = $subid+1;
            $subqsnparray = $TBQSNPArray[$scenkey]["1"];
            $oltrunoff = number_format($subqsnparray[$subno][0][0], 1, '.', '');
            echo("<td style=\"width:5%\">" . $oltrunoff . "</td>");
        }
        echo("</tr>");
    }
    echo("</table>");

    echo("<p align=center>Annual Average Soil Erosion (ton/ha)</p>");
    echo("<table style=\"width:100%\">");
    // Create the table head
    echo("<tr><td style=\"width:10%\">Subarea</td>");
    for ($subid = 0; $subid <$totalsubno;$subid++){
        $subno = $subid+1;
        echo("<td style=\"width:5%\">" . $subno  . "</td>");
    }
    echo("</tr>");
    // One row for one scenario
    $scenariolst = $_SESSION["SSVAR"]["runscenario"];
    $numofscen2 = count($scenariolst)/2;
    for ($sid = 0; $sid<$numofscen2; $sid++)
    {
        $sid2 = $sid+1;
        $scenkey = "s". (string)$sid2;
        $scennamefull = $scenariolst[$scenkey. "_full"];
        $scenname = $scenariolst[$scenkey];
        echo("<tr><td style=\"width:10%\">". $scennamefull ."</td>");
        for ($subid = 0; $subid <$totalsubno;$subid++){
            $subno = $subid+1;
            $subqsnparray = $TBQSNPArray[$scenkey]["1"];
            $olterosion = number_format($subqsnparray[$subno][0][1], 1, '.', '');
            echo("<td style=\"width:5%\">" . $olterosion . "</td>");
        }
        echo("</tr>");
    }
    echo("</table>");

    echo("<p align=center>Annual Average Total Nitrogen (kg/ha)</p>");
    echo("<table style=\"width:100%\">");
    // Create the table head
    echo("<tr><td style=\"width:10%\">Subarea</td>");
    for ($subid = 0; $subid <$totalsubno;$subid++){
        $subno = $subid+1;
        echo("<td style=\"width:5%\">" . $subno  . "</td>");
    }
    echo("</tr>");
    // One row for one scenario
    $scenariolst = $_SESSION["SSVAR"]["runscenario"];
    $numofscen2 = count($scenariolst)/2;
    for ($sid = 0; $sid<$numofscen2; $sid++)
    {
        $sid2 = $sid+1;
        $scenkey = "s". (string)$sid2;
        $scennamefull = $scenariolst[$scenkey. "_full"];
        $scenname = $scenariolst[$scenkey];
        echo("<tr><td style=\"width:10%\">". $scennamefull ."</td>");
        for ($subid = 0; $subid <$totalsubno;$subid++){
            $subno = $subid+1;
            $subqsnparray = $TBQSNPArray[$scenkey]["1"];
            $olttn = number_format($subqsnparray[$subno][0][2], 1, '.', '');
            echo("<td style=\"width:5%\">" . $olttn . "</td>");
        }
        echo("</tr>");
    }
    echo("</table>");

    echo("<p align=center>Annual Average Total Phosphorus (kg/ha)</p>");
    echo("<table style=\"width:100%\">");
    // Create the table head
    echo("<tr><td style=\"width:10%\">Subarea</td>");
    for ($subid = 0; $subid <$totalsubno;$subid++){
        $subno = $subid+1;
        echo("<td style=\"width:5%\">" . $subno  . "</td>");
    }
    echo("</tr>");
    // One row for one scenario
    $scenariolst = $_SESSION["SSVAR"]["runscenario"];
    $numofscen2 = count($scenariolst)/2;
    for ($sid = 0; $sid<$numofscen2; $sid++)
    {
        $sid2 = $sid+1;
        $scenkey = "s". (string)$sid2;
        $scennamefull = $scenariolst[$scenkey. "_full"];
        $scenname = $scenariolst[$scenkey];
        echo("<tr><td style=\"width:10%\">". $scennamefull ."</td>");
        for ($subid = 0; $subid <$totalsubno;$subid++){
            $subno = $subid+1;
            $subqsnparray = $TBQSNPArray[$scenkey]["1"];
            $olttp = number_format($subqsnparray[$subno][0][3], 1, '.', '');
            echo("<td style=\"width:5%\">" . $olttp . "</td>");
        }
        echo("</tr>");
    }
    echo("</table>");




}    


?>


</div>
<!-- end of results for watershed div -->

<!-- Start of results for field div -->
<?php
if ($_SESSION["SSVAR"]["fldstep"] > 1)
{
    echo("<p align=center>Area Weighted Average Annual Values for the field</p>");
    echo("<table>");
    // Create head
    echo("<tr>");
    echo("<td style=\"width:10%\">Scenario</td>");
    echo("<td style=\"width:10%\">Surface runoff (mm)</td>");
    echo("<td style=\"width:10%\">Soil Erosion (ton/ha)</td>");
    echo("<td style=\"width:10%\">Total Nitrogen (kg/ha)</td>");
    echo("<td style=\"width:10%\">Total Phosphorus (kg/ha)</td>");
    echo("</tr>");

    // Fill cells with values for each scenario
    $scenariolst = $_SESSION["SSVAR"]["runscenario"];
    $numofscen2 = count($scenariolst)/2;
    for ($sid = 0; $sid<$numofscen2; $sid++)
    {
        $sid2 = $sid+1;
        $scenkey = "s". (string)$sid2;
        $scennamefull = $scenariolst[$scenkey. "_full"];
        $scenname = $scenariolst[$scenkey];
        echo("<tr><td style=\"width:10%\">". $scennamefull ."</td>");
        $fldrunoff = number_format($TBQSNPArray[$scenname][0], 1, '.', '');
        echo("<td style=\"width:5%\">" . $fldrunoff . "</td>");
        $flderosion = number_format($TBQSNPArray[$scenname][1], 2, '.', '');
        echo("<td style=\"width:5%\">" . $flderosion . "</td>");
        $fldtn = number_format($TBQSNPArray[$scenname][2], 2, '.', '');
        echo("<td style=\"width:5%\">" . $fldtn . "</td>");
        $fldtp = number_format($TBQSNPArray[$scenname][3], 2, '.', '');
        echo("<td style=\"width:5%\">" . $fldtp . "</td>");
    }

    echo("</table>");
}

?>

<!-- end of results for field div -->



</form>  <!-- end mapserv form -->

<br>
<br>
</body>


</html>






