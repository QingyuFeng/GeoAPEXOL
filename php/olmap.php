<?php

// olmap.php
// 
// This block of code was deal with all openlayer mapping stuff.
// It will be included in the main page. The functions include:
// 1. Initiallize the map
// 2. Display network, subcatchment, and other in each step, depending on the
// steps variable set.
// 3. Deal with user interactions
//
// 

// Start session
include('funcs.php');
include('log.php');

$taudemMap = $globWorkRoot . session_id() . "/taudem.map";
$apexMap = $globWorkRoot . session_id() . "/apex.map";

$workingDir = $globWorkRoot . session_id();
echo('<br> Session ID: '.session_id(). '<br>');


//Set working directory
$genefuncs->setWorkingDir();

// Determine units 
if ($globEngUnits == 1) {
    $lenStr = "ft";
    $areaStr = "acre";
}
else
{
    $lenStr = "m";
    $areaStr = "ha";
}

// The following code mainly works to modify
// the session variables to control the generating
// and display of maps.
// Keep these two variables upto date.
if (isset($_POST['CRIT']))
{
    $_SESSION["SSVAR"]["criticalarea"] = $_POST["CRIT"];
}
if (isset($_POST['TILED']))
{
    $_SESSION["SSVAR"]["tiledep"] = $_POST["TILED"];
}



// The first control is zoom: Zoom is perfomred
// when the izoom flag is 1 and zoomLoc len>0
if (isset($_POST['DOZOOM']))
{
    $_SESSION["SSVAR"]["izoom"] = $_POST["DOZOOM"];
}

if (isset($_POST['ZLOC']))
{
    $_SESSION["SSVAR"]["zoomLoc"] = $_POST["ZLOC"];
    $zoomLocation = trim($_SESSION["SSVAR"]["zoomLoc"]);
    $len = strlen($zoomLocation);
}

if (($len > 0) && (strcmp($_SESSION["SSVAR"]["izoom"], "1") == 0))
{
    $arr = $genefuncs->doZoom($zoomLocation,
        $_SESSION["SSVAR"]["latitude"],
        $_SESSION["SSVAR"]["longitude"]);
    $_SESSION["SSVAR"]["latitude"] = $arr[0];
    $_SESSION["SSVAR"]["longitude"] = $arr[1];

    $yll = $_SESSION["SSVAR"]["latitude"] - 0.1;
    $xll = $_SESSION["SSVAR"]["longitude"] - 0.1;
    $yur = $_SESSION["SSVAR"]["latitude"] + 0.1;
    $xur = $_SESSION["SSVAR"]["longitude"] + 0.1;
    $_SESSION["SSVAR"]["extent"] = $xll . "," . $yll . "," . $xur . "," . $yur;

    // After zoom, reset izoom to 0 to prevent rerunning the function.
    // and redirect the page to prevent resubmitting the form.
    $_SESSION["SSVAR"]["izoom"] = "0";
    header("Location: index.php");
}

// The next operation is to start over
if ((isset($_POST['DOSTVR']) && (strcmp($_POST["DOSTVR"], "1") == 0)))
{
    $_SESSION["SSVAR"] = json_decode(file_get_contents(JS_SSVARJSON), true);
    $gdalfuncs->delete_files($workingDir);
    $genefuncs->setWorkingDir();
    // and redirect the page to prevent resubmitting the form.
    header("Location: index.php");

}


// Then work on do stream network for olt 
if ((isset($_POST['DOSTRM']) && (strcmp($_POST["DOSTRM"], "1") == 0)))
{
    if (isset($_POST['EXTENT']))
    {
        $_SESSION["SSVAR"]["extent"] = urldecode($_POST["EXTENT"]);
    } 
    // Call an additional php file
    include("dosnolt.php"); 
    // Update steps value for map showing
    $_SESSION["SSVAR"]["oltstep"] = 1;
    // and redirect the page to prevent resubmitting the form.
    header("Location: index.php");
}

// Then work on do watershed with olt
if ((isset($_POST['DOWSOT']) && (strcmp($_POST["DOWSOT"], "1") == 0)))
{
    if (isset($_POST['JSONOT']))
    {
        $_SESSION["SSVAR"]["jsot"] = urldecode($_POST["JSONOT"]);
    }
    // Also created a 3857 version of the outlet for display
    if (isset($_POST['JSO3857']))
    {
        $_SESSION["SSVAR"]["jso3857"] = urldecode($_POST["JSO3857"]);
    }
    // Call an additional php file
    include("dowsolt.php");
    // Update steps value for map showing
    $_SESSION["SSVAR"]["oltstep"] = 2;
    // and redirect the page to prevent resubmitting the form.
    header("Location: index.php");
}

// Then setup apex model: basically transfer information into json. 
if ((isset($_POST['DOJAPOT']) && (strcmp($_POST["DOJAPOT"], "1") == 0)))
{
    // Call an additional php file
    include("dosetapexolt.php");
    // Update steps value for map showing
    $_SESSION["SSVAR"]["oltstep"] = 3;
    // and redirect the page to prevent resubmitting the form.
    //header("Location: index.php");
}

// Then run apex model convert json into apex input files, run
// the model and generate maps from output.
if ((isset($_POST['DORAPOT']) && (strcmp($_POST["DORAPOT"], "1") == 0)))
{
    // Call an additional php file
    include("runapexolt.php");

    // Update steps value for map showing
    $_SESSION["SSVAR"]["oltstep"] = 4;
    // and redirect the page to prevent resubmitting the form.
    //header("Location: index.php");
}



// Steps for processing Field Runs
if ((isset($_POST['DOWSFD']) && (strcmp($_POST["DOWSFD"], "1") == 0)))
{
    if (isset($_POST['EXTENT']))
    {
        $_SESSION["SSVAR"]["extent"] = urldecode($_POST["EXTENT"]);
    }
    if (isset($_POST['JSONFD']))
    {
        $_SESSION["SSVAR"]["jsfd"] = urldecode($_POST["JSONFD"]);
    }
    // Also created a 3857 version of the outlet for display
    if (isset($_POST['JSF3857']))
    {
        $_SESSION["SSVAR"]["jsf3857"] = urldecode($_POST["JSF3857"]);
    }

    // Call an additional php file
    include("dowsfld.php");
    // Update steps value for map showing
    $_SESSION["SSVAR"]["fldstep"] = 1;
    // and redirect the page to prevent resubmitting the form.
    //header("Location: index.php");
}

// Then setup apex model: basically transfer information into json.
if ((isset($_POST['DOJAPFD']) && (strcmp($_POST["DOJAPFD"], "1") == 0)))
{
    // Call an additional php file
    include("dosetapexfld.php");
    // Update steps value for map showing
    $_SESSION["SSVAR"]["fldstep"] = 2;
    // and redirect the page to prevent resubmitting the form.
    //header("Location: index.php");
}

// Then setup apex model: basically transfer information into json.
if ((isset($_POST['DORAPFD']) && (strcmp($_POST["DORAPFD"], "1") == 0)))
{
    // Call an additional php file
    include("runapexfld.php");
    // Update steps value for map showing
    $_SESSION["SSVAR"]["fldstep"] = 3;
    // and redirect the page to prevent resubmitting the form.
//    header("Location: index.php");
}

// Then setup apex model: basically transfer information into json.
if ((isset($_POST['DORAPEX']) && (strcmp($_POST["DORAPEX"], "1") == 0)))
{
    // Every time entered here, we first check whether one 
    // run existed. The reason was to decrease the repeating 
    // steps of writing and copying files.
    // Check whether this is a new run or not
    if ($_SESSION["SSVAR"]["inewapexrun"] == 0)
    {
        // Indicating this is a new run, run prepare files
        include('runapexp1.php');
    }

    // Then modify the session scenario variable
    if(!empty($_POST['scenariolst'])) {
        $_SESSION["SSVAR"]["runscenario"] = array();
        foreach($_POST['scenariolst'] as $idx=>$scenro) {
            $seskey = 's'. (string)($idx+1);
            $seskey_f = 's'. (string)($idx+1).'_full';
            $_SESSION["SSVAR"]["runscenario"][$seskey] = $scenro;
            $_SESSION["SSVAR"]["runscenario"][$seskey_f] = $scenro; 
        }
    } 

    // Check which routine to run: olt or fld
    if ($_SESSION["SSVAR"]["fldstep"] > 1)
    {
        // Run fld steps
        $TBQSNPArray = array();
        include('runapexfldp2.php');
        // Update steps value for map showing
        $_SESSION["SSVAR"]["fldstep"] = 3;
    }
    else if ($_SESSION["SSVAR"]["oltstep"] > 2)
    {
        // Run olt steps
        $TBQSNPArray = array();
        include('runapexoltp2.php');
        // Update steps value for map showing
        $_SESSION["SSVAR"]["oltstep"] = 4;


    }






    $_SESSION["SSVAR"]["inewapexrun"] = 1;

}







?>



<script type="text/javascript">
// variables
var map;
var recenter = 1;
const mapserv = '<?=$mapserv?>';

var srcDrawFld = null;
var vecDrawFld = null; 

var srcDrawOlt = null;
var vecDrawOlt = null; 





function checkEnter(e)
{
    var characterCode
    if(e && e.which){ // NN4 specific code
        e = e
        characterCode = e.which
        }
    else {
        e = event
        characterCode = e.keyCode // IE specific code
    }
    if (characterCode == 13) doSearch(); // Enter key is 13
    else return false
}


//
// doSearch()
//
//
function doSearch()
{
    document.mapserv.DOZOOM.value = "1";
    document.mapserv.submit();
}


function doStartOver()
{
    document.mapserv.DOSTVR.value = "1";
    document.mapserv.submit();
}



function areaTooLarge(wid,hgt) 
{
    var hstr  = "<table border=\"1\" width=\"100%\"><tr>";
    hstr = hstr + "<td height=\"120px\" width=\"250px\" bgcolor=\"#CCFFFF\">";
    hstr = hstr + "<font face=\"Arial\">";
    hstr = hstr + "Area is too large to delineate.<br>Zoom into a smaller area less than 0.20 degrees by 0.20 degrees.<br>The current area is " + wid.toFixed(2) + " degrees by " + hgt.toFixed(2) + " degrees.";
    hstr = hstr + "</font>";
    hstr = hstr + "</td></tr></table>";

    var popup = new Popup();
    map.addOverlay(popup);

    popup.show(map.getView().getCenter(), hstr);
}



function doStreamNet()
{
    // 1. Check whether a stream network has already been 
    // generated
    var istrmgene = '<?=$_SESSION["SSVAR"]["oltstep"]?>';
    if (istrmgene > 0) {
        if (confirm("Delete current channel network and create a new one?"));
        else
        {return};
    }   

    // 2. Judge whether the area is too large for processing.   
    var src  = map.getView().getProjection().getCode();
    var t = map.getView().calculateExtent(); 
    
    var nt = ol.proj.transformExtent(t,src,"EPSG:4326");
    var wid = ol.extent.getWidth(nt);
    var hgt = ol.extent.getHeight(nt);
    var bx = nt;
    var zoom = map.getView().getZoom();

    if ((wid > 0.30) || (hgt > 0.30)) {
        areaTooLarge(wid,hgt);
    }
    else
    {
        // Judge the critical area for watershed delineation
        var crit = document.mapserv.CRIT.value;
        if (crit < 0) {
              generalPopup('Critical source area must be greater than 0.');
              return;
        }
        else
        {   
            // If everything is good, modify the document values and then submit the form.
            document.mapserv.DOSTRM.value = "1";
            document.mapserv.EXTENT.value = escape(bx);
            document.mapserv.submit();            
        }
    }
}





//
// initol()
//
// This is the main initialization for the maps when the page loads.
//
function initol()
{
    var extent = [-14225848,2815858,-7386874,6339992];
    var mapOptions = {
        view: new ol.View({
        center: ol.proj.fromLonLat([-96.3,40.3]),
        extent: extent, 
        zoom: 4,
        minZoom: 4,
        maxZoom: 14
            }),
        target: 'map'
        };
     
    map = new ol.Map(mapOptions); 
    var layerSwitcher = new ol.control.LayerSwitcher({
        tipLabel: 'Legend' // Optional label for button
    });
   
    map.addControl(layerSwitcher);      
    
    setBaseLayersOpen();
    
    //setOverlaysOpen(); 

    // Setup  layers of each step
    <?php if ($_SESSION["SSVAR"]["oltstep"] > 0) { ?>
    setOltStep1Layers();       // Channel delineation complete, network layer available
    <?php } ?>

    <?php if ($_SESSION["SSVAR"]["oltstep"] > 1) { ?>
    setOltStep2Layers();       // Channel delineation complete, network layer available
    <?php } ?>

    <?php if ($_SESSION["SSVAR"]["oltstep"] > 3) { ?>
    var scenarioarr = JSON.parse('<?= json_encode($_SESSION["SSVAR"]["runscenario"])?>');
    setOltStep4Layers(scenarioarr);       // Channel delineation complete, network layer available
    <?php } ?>

    <?php if ($_SESSION["SSVAR"]["fldstep"] > 0) { ?>
    var wsnoinfld = '<?=$_SESSION["SSVAR"]["fldwsno"]?>';
    setFldStep1Layers(wsnoinfld);       // Channel delineation complete, network layer available
    <?php } ?>

    //Then add the results for the field boundary 
    <?php if ($_SESSION["SSVAR"]["fldstep"] > 2) { ?>
    var scenarioarr = JSON.parse('<?= json_encode($_SESSION["SSVAR"]["runscenario"])?>');
    setFldStep3Layers_fldqsnpmap(scenarioarr);       // Channel delineation complete, network layer available
    <?php } ?>




    <?php if (( $_SESSION["SSVAR"]["extent"]== 0) && ($_SESSION["SSVAR"]["izoom"]== "0")) { ?>
    map.zoomToMaxExtent();
    <?php } else { ?>
    //alert("extent:" + "<?=$_SESSION["SSVAR"]["extent"]?>");
    var extent = "<?=$_SESSION["SSVAR"]["extent"]?>";
    var exts = extent.split(',');
    //alert(exts[0]);alert(exts[1]);alert(exts[2]);alert(exts[3]);
    exts[0] = parseFloat(exts[0]);
    exts[1] = parseFloat(exts[1]);
    exts[2] = parseFloat(exts[2]);
    exts[3] = parseFloat(exts[3]);

    var coordMin = ol.proj.fromLonLat([exts[0],exts[1]], 'EPSG:3857');
    //alert(coordMin);
    var coordMax = ol.proj.fromLonLat([exts[2],exts[3]], 'EPSG:3857');
    var extent = [coordMin[0],coordMin[1],coordMax[0],coordMax[1]];
    //alert("newextent:" + extent);
    map.getView().fit(extent , map.getSize());
    //bounds = new OpenLayers.Bounds(<?=$extent?>);
    //bounds.transform(new OpenLayers.Projection("EPSG:4326"),
    // map.getProjectionObject());
    // map.zoomToExtent(bounds,true);
    <?php } ?>

}    


//
// setBaseLayersOpen()
//
// This sets up the base layers when the geographic projection is used (epsg:4326). Mostly we use the
// Google Maps base layers and this is only here for testing another approach.
//
function setBaseLayersOpen()
{
    var map_lay = new ol.layer.Tile({
        title: 'Topo',
        type: 'base',
        source: new ol.source.XYZ({
        attributions: 'Tiles © <a href="https://services.arcgisonline.com/ArcGIS/' +
                    'rest/services/World_Topo_Map/MapServer">ArcGIS</a>',
        url: 'http://server.arcgisonline.com/ArcGIS/rest/services/' +
                'World_Topo_Map/MapServer/tile/{z}/{y}/{x}'
        })
    });
    var sat_lay = new ol.layer.Tile({
        title: 'Satellite',
        type: 'base',
        source: new ol.source.XYZ({
            attributions: 'Tiles © <a href="https://services.arcgisonline.com/ArcGIS/' +
                    'rest/services/World_Imagery/MapServer">ArcGIS</a>',
            url: 'https://services.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}'
        })
    });

    var street_lay = new ol.layer.Tile({
        title: 'Streets',
        type: 'base',
        source: new ol.source.XYZ({
            attributions: 'Tiles © <a href="https://services.arcgisonline.com/ArcGIS/' +
                    'rest/services/World_Street_Map/MapServer">ArcGIS</a>',
            url: 'https://services.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}'
        })
    });

    map.addLayer(street_lay);
    map.addLayer(sat_lay);
    map.addLayer(map_lay);
   // map.addLayer(sat_lay);
}

//
 // setOltStep1Layers()
 //
 // This function is called after the channel delineation has been run. The channel network layer
 // is added. The outlet button is enabled to allow selecting an outlet on the channel network.
 //
function setOltStep1Layers()
{
    var network_lay = new ol.layer.Image({
          title: 'Network',
          source: new ol.source.ImageWMS({ 
          url:mapserv,
          crossOrigin: 'anonymous',
          attributions: 'USDA-ARS TOPAZ',
              params : {
                  'LAYERS': 'network',
                  'map' : '<?=$taudemMap?>',
              },
          serverType: 'mapserver'
        })
    });

    map.addLayer(network_lay);

}

 //
 // setOltStep2Layers()
 //
 // This function is called after the subcatchments have been delineated. The new subcatchment
 // layer is added. At this point all buttons are enabled.
 //
function setOltStep2Layers()
{
        var sub_lay = new ol.layer.Image({
                  title: 'watershed',
                  source: new ol.source.ImageWMS({
                  url:mapserv,
                  crossOrigin: 'anonymous',
                  attributions: 'USDA-ARS TOPAZ',
                  params : {
                      'LAYERS': 'watershed',
                      'map' : '<?=$taudemMap?>',
                     },
                  serverType: 'mapserver'
                })
        });

        map.addLayer(sub_lay);

    var subid_lay  = new ol.layer.Image({
        title: 'Subarea IDs',
        source: new ol.source.ImageWMS({
        url:mapserv,
        crossOrigin: 'anonymous',
            attributions: 'USDA-ARS TOPAZ',
            params : {
               'LAYERS': 'SubareaIDs',
               'map' : '<?=$taudemMap?>'
            },
              serverType: 'mapserver'
                 })
                });

         map.addLayer(subid_lay);



        // Try add the outlet into this map
        //
        var oltFill = new ol.style.Fill({
		   color: 'black'
		});

        var image = new ol.style.Circle({
            radius: 5,
            fill: oltFill,
            stroke: new ol.style.Stroke({
            color: 'black',
            width: 1
            })
        });

        var styles = {
            'Point': new ol.style.Style({
            image: image
            }),
        };

        var styleFunction = function(feature) {
            return styles[feature.getGeometry().getType()];
        };
        
        var oltjsonstr = '<?php echo $_SESSION["SSVAR"]["jso3857"]?>';

        var oltjsonparsed = JSON.parse(oltjsonstr);
		var formatjson = new ol.format.GeoJSON({
			featureProjection:'EPSG:4832'});

		srcDrawOlt = new ol.source.Vector({
                    features:formatjson.readFeatures(
					oltjsonparsed)
                    })

        vecDrawOlt = new ol.layer.Vector({
            title: 'Watershed Outlet',
            source: srcDrawOlt,
            style: styleFunction
        });


         map.addLayer(vecDrawOlt);




}


 //
 // setOltStep2Layers()
 //
 // This function is called after the subcatchments have been delineated. The new subcatchment
 // layer is added. At this point all buttons are enabled.
 //
function setOltStep4Layers(scenarioarr)
{

    var numofsce = Object.keys(scenarioarr).length/2;
    for (var si = 0; si <numofsce; si++)
    {
        var sceno = si + 1;
        var scenkeyfull = 's' + sceno.toString() + '_full';
        var scenkey = 's' + sceno.toString();
           
        var aaqlay = new ol.layer.Image({
                  title: 'Runoff (mm) ' + scenarioarr[scenkeyfull],
                  source: new ol.source.ImageWMS({
                  url:mapserv,
                  crossOrigin: 'anonymous',
                  attributions: 'USDA-ARS TOPAZ',
                  params : {
                      'LAYERS': scenarioarr[scenkey] + 'runoff',
                      'map' : '<?=$taudemMap?>',
                     },
                  serverType: 'mapserver'
                })
        });

        map.addLayer(aaqlay);

        var aaselay = new ol.layer.Image({
                  title: 'Soil Erosion (ton/ha) ' + scenarioarr[scenkeyfull],
                  source: new ol.source.ImageWMS({
                  url:mapserv,
                  crossOrigin: 'anonymous',
                  attributions: 'USDA-ARS TOPAZ',
                  params : {
                      'LAYERS':scenarioarr[scenkey] + 'erosion',
                      'map' : '<?=$taudemMap?>',
                     },
                  serverType: 'mapserver'
                })
        });

        map.addLayer(aaselay);

        var aatnlay = new ol.layer.Image({
                  title: 'Total Nitrogen(kg/ha) ' + scenarioarr[scenkeyfull],
                  source: new ol.source.ImageWMS({
                  url:mapserv,
                  crossOrigin: 'anonymous',
                  attributions: 'USDA-ARS TOPAZ',
                  params : {
                      'LAYERS': scenarioarr[scenkey] + 'tn',
                      'map' : '<?=$taudemMap?>',
                     },
                  serverType: 'mapserver'
                })
        });

        map.addLayer(aatnlay);

        var aatplay = new ol.layer.Image({
                  title: 'Total Phosphorus(kg/ha) ' + scenarioarr[scenkeyfull],
                  source: new ol.source.ImageWMS({
                  url:mapserv,
                  crossOrigin: 'anonymous',
                  attributions: 'USDA-ARS TOPAZ',
                  params : {
                      'LAYERS': scenarioarr[scenkey] + 'tp',
                      'map' : '<?=$taudemMap?>',
                     },
                  serverType: 'mapserver'
                })
        });

        map.addLayer(aatplay);
    
    }// End of for loop

}


//
// enableDrawOutlet(pass)
//
// The function to enable a draw an outlet function
// This was called when the user clicked the Set Outlet button
// on the main page.

function enableDrawOutlet()
{
    var oltstep = '<?=$_SESSION["SSVAR"]["oltstep"]?>'; 
    if (oltstep > 1) {
            if (confirm("Delete current outlet point and select a new one?"));
            else {return;}
    }
    var selectOlt = new ol.style.Style({
        stroke: new ol.style.Stroke({
        color: '#ff0000',
        width: 2
        })
    });
    var defaultOlt = new ol.style.Style({
        stroke: new ol.style.Stroke({
        color: '#0000ff',
        width: 2
        })
    });



    // If not null, initiate a source
    if (srcDrawOlt === null){ 
        srcDrawOlt = new ol.source.Vector();}

    var drawfill = new ol.style.Fill({
        color: 'black'
    });

    var image = new ol.style.Circle({
        radius: 8,
        fill: drawfill,
        stroke: new ol.style.Stroke({
        color: 'black',
        width: 1
        })
    });

    var drawStyles = {
        'Point': new ol.style.Style({
        image: image
        }),
    };

    var styleFunction = function(feature) {
        return drawStyles[feature.getGeometry().getType()];
    };

    if (vecDrawOlt === null){
        // If not, means start creating one.
        // Else, vecDrawOlt is aready added
        vecDrawOlt = new ol.layer.Vector({
            source: srcDrawOlt,
            style: styleFunction 
        });
        map.addLayer(vecDrawOlt);
    }

    var drawol = new ol.interaction.Draw({
        source: srcDrawOlt,
        type: "Point"
    });

    drawol.on('drawend', function(evt){
        srcDrawOlt.clear();
        document.mapserv.wsoltButton.disabled = false;
        },
    this);
    
    var modifysrcDrawOlt = new ol.interaction.Modify({
        source: srcDrawOlt
    });
    
    map.addInteraction(drawol);
    map.getInteractions().extend([drawol, modifysrcDrawOlt]);

}



function doWatershedOlt()
{
    var oltstep = '<?=$_SESSION["SSVAR"]["oltstep"]?>';
    if (oltstep >= 2) {
        if (confirm("Delete current watershed and build a new one?"));
        else {return;}
    }
    var utmzone = '<?=$_SESSION["SSVAR"]['utmzone']?>';
    
    fetch('https://epsg.io/?format=json&q=269' + utmzone).then(
        function(response) {return response.json();
        }).then(function(json) {
            var results = json['results'];
            if (results && results.length > 0) {
                for (var i = 0, ii = results.length; i < ii; i++) {
                    var result = results[i];
                    if (result) {
                    var code = result['code'];
                    var name = result['name'];
                    var proj4def = result['proj4'];
                    var bbox = result['bbox'];

                    if (code && code.length > 0 && proj4def && proj4def.length > 0 &&
                        bbox && bbox.length === 4) {
                        gotoSubcatchmentOlt(code, proj4def);
                        return;
                        }
                    }
                }
            }
});

}


function gotoSubcatchmentOlt(code, proj4def){
    
    var newProjCode = 'EPSG:' + code;
    proj4.defs(newProjCode, proj4def);
    // Define the GeoJson format, which will be used to write the features.
    var format = new ol.format.GeoJSON();
    var features = srcDrawOlt.getFeatures();
    var jsonutm = format.writeFeatures(features,{dataProjection: newProjCode,
                                          featureProjection: 'EPSG:3857'});

    var json3857 = format.writeFeatures(features); 
    // If everything is good, modify the document values and then submit the form.
    document.mapserv.DOWSOT.value = "1";
    document.mapserv.JSONOT.value = escape(jsonutm);
    document.mapserv.JSO3857.value = escape(json3857);
    document.mapserv.submit();
}



// Setup APEX model for watershed delineated with outlet
function setupAPEXOlt(){
    document.mapserv.DOJAPOT.value = "1";
    document.mapserv.submit();
}

// Run APEX model for watershed delineated with outlet
function runAPEXOlt(){
    document.mapserv.DORAPOT.value = "1";
    document.mapserv.submit();
}


//
// enableDrawFieldBdy()
//
// The function to enable a draw field boundary option
// This was called when the user clicked the Draw Field Boundary button
// on the main page.
function enableDrawBoundary()
{
    var fldstep = '<?=$_SESSION["SSVAR"]["fldstep"]?>';
    if (fldstep > 0) {
        if (confirm("Delete current field boundary and select a new one?"));
        else {return;}
    }

    if (srcDrawFld === null){ 
        srcDrawFld = new ol.source.Vector();}

    if (vecDrawFld === null){
        // If not, means start creating one.
        // Else, vecDrawOlt is aready added
        vecDrawFld = new ol.layer.Vector({
            source: srcDrawFld
        });
        map.addLayer(vecDrawFld);
    }

    var draw = new ol.interaction.Draw({
        source: srcDrawFld,
        type: "Polygon",
        style: new ol.style.Style({
        fill: new ol.style.Fill({
            color: 'rgba(255, 255, 255, 0.2)'
            }),
            stroke: new ol.style.Stroke({
                color: 'rgba(0, 0, 0, 0.5)',
                lineDash: [10, 10],
                width: 2
            }),
            image: new ol.style.Circle({
                radius: 5,
                stroke: new ol.style.Stroke({
                color: 'rgba(0, 0, 0, 0.7)'
            }),
            fill: new ol.style.Fill({
                color: 'rgba(255, 255, 255, 0.2)'
            })
          })
        })
    });
    
    var snapping = new ol.interaction.Snap({
        source: srcDrawFld
    });    
    
    // Block of code dealing with area measurement
    createHelpTooltip();

    var listener;
    var sketch;
    draw.on('drawstart', function(evt){
        srcDrawFld.clear();
        createMeasureTooltip();
        sketch = evt.feature;
        var tooltipCoord = evt.coordinate;
        listener = sketch.getGeometry().on('change', function(evt) {
            var geom = evt.target;
            var output;
            output = formatArea(geom);
            tooltipCoord = geom.getInteriorPoint().getCoordinates();
            measureTooltipElement.innerHTML = output;
            measureTooltip.setPosition(tooltipCoord);
            });
        }, this);

    draw.on('drawend', function(evt){
        srcDrawFld.clear();
        measureTooltipElement.className = 'tooltip tooltip-static';
        measureTooltip.setOffset([0, -7]);
        // unset sketch
        sketch = null;
        // unset tooltip so that a new one can be created
        measureTooltipElement = null;
        ol.Observable.unByKey(listener);
        document.mapserv.wsfldbButton.disabled = false;
        document.mapserv.strnetButton.disabled = true;
    }, this);
    
    var modifysrcDrawFld = new ol.interaction.Modify({
                source: srcDrawFld
        });
    
    modifysrcDrawFld.on('modifyend', function(evt){
        createMeasureTooltip();
        evt.features.forEach(function(feature){
            var coords = feature.getGeometry().getCoordinates();
            var geom = feature.getGeometry();
            var output = formatArea(geom);
            var tooltipCoord = geom.getInteriorPoint().getCoordinates();
            measureTooltipElement.innerHTML = output;
            measureTooltip.setPosition(tooltipCoord);
            measureTooltipElement.className = 'tooltip tooltip-static';
            measureTooltip.setOffset([0, -7]);
            // unset tooltip so that a new one can be created
            measureTooltipElement = null;
        });
    }, this);


    map.addInteraction(draw);
    map.addInteraction(snapping);
    map.addInteraction(modifysrcDrawFld);
   
    map.on('pointermove', function(evt){
        // When user was dragging map, then coordinates didn't change and there's
        // no need to continue
        if (evt.dragging) {
            return;
        }
        // You can access coordinates from evt.coordinate now   
        /** @type {string} */
        var helpMsg = 'Click to start drawing';
        
        if(sketch){
            helpMsg = 'Click to continue drawing the polygon';
        }   
        helpTooltipElement.innerHTML = helpMsg;
        helpTooltip.setPosition(evt.coordinate);
        helpTooltipElement.classList.remove('hidden');

    });
    map.getViewport().addEventListener('mouseout', function() {
            helpTooltipElement.classList.add('hidden');
        });

    /**
    * Creates a new measure tooltip
    */
    
    /**
    * The measure tooltip element.
    * @type {Element}
    */
    var measureTooltipElement;  
    var helpTooltipElement;

    /**
    * Overlay to show the help messages.
    * @type {ol.Overlay}
    */
    var helpTooltip;
    /**
    * Overlay to show the measurement.
    * @type {ol.Overlay}
    */
    var measureTooltip = null;

    function createMeasureTooltip() {
        if (measureTooltipElement) {
            measureTooltipElement.parentNode.removeChild(measureTooltipElement);
        }
        measureTooltipElement = document.createElement('div');
        measureTooltipElement.className = 'tooltip tooltip-measure';
        if (measureTooltip !== null) {
            map.removeOverlay(measureTooltip);
        }   

        measureTooltip = new ol.Overlay({
            element: measureTooltipElement,
            offset: [0, -15],
            positioning: 'bottom-center'
        });
        map.addOverlay(measureTooltip);
        }

    /**
    * Creates a new help tooltip
    */
    function createHelpTooltip() {
        if (helpTooltipElement) {
            helpTooltipElement.parentNode.removeChild(helpTooltipElement);
        }
        helpTooltipElement = document.createElement('div');
        helpTooltipElement.className = 'tooltip hidden';
        helpTooltip = new ol.Overlay({
            element: helpTooltipElement,
            offset: [15, 0],
            positioning: 'center-left'
        });
        map.addOverlay(helpTooltip);
   }


    /**
    * Format area output.
    * @param {ol.geom.Polygon} polygon The polygon.
    * @return {string} Formatted area.
    */
    var wgs84Sphere = new ol.Sphere(6378137); 
    var formatArea = function(polygon) {
        var area;
        var sourceProj = map.getView().getProjection();
        var geom = /** @type {ol.geom.Polygon} */(polygon.clone().transform(
                sourceProj, 'EPSG:4326'));
        var coordinates = geom.getLinearRing(0).getCoordinates();
        area = Math.abs(wgs84Sphere.geodesicArea(coordinates));
        var output;
        if (area > 10000) {
            output = (Math.round(area / 1000000 * 100) / 100) +
                ' ' + 'km<sup>2</sup>';
        } else {
            output = (Math.round(area * 100) / 100) +
            ' ' + 'm<sup>2</sup>';
        }
        return output;
      };

}




function doWatershedFld()
{
    var fldstep = '<?=$_SESSION["SSVAR"]["fldstep"]?>';
    if (fldstep >= 1) {
        if (confirm("Delete current watershed and build a new one?"));
        else {return;}
    }
    
    var src  = map.getView().getProjection().getCode();
    var mapext = map.getView().calculateExtent();
    var mapext4326 = ol.proj.transformExtent(mapext,src,"EPSG:4326");   
    var utmzone = Math.floor((((mapext4326[0] + mapext4326[2])/2.0)/6) + 31);

    //var utmzone = '<?=$_SESSION["SSVAR"]['utmzone']?>';
    
    fetch('https://epsg.io/?format=json&q=269' + utmzone).then(
        function(response) {return response.json();
        }).then(function(json) {
            var results = json['results'];
            if (results && results.length > 0) {
                for (var i = 0, ii = results.length; i < ii; i++) {
                    var result = results[i];
                    if (result) {
                        var code = result['code'];
                        var name = result['name'];
                        var proj4def = result['proj4'];
                        var bbox = result['bbox'];
                        if (code && code.length > 0 && proj4def && proj4def.length > 0 &&
                        bbox && bbox.length === 4) {
                            gotoWSFld(code, proj4def);
                            return;
                        }
                    }
                }
            }
});

}

function gotoWSFld(code, proj4def){
    
    var newProjCode = 'EPSG:' + code;
        proj4.defs(newProjCode, proj4def);
    // Define the GeoJson format, which will be used to write the features.
    const format = new ol.format.GeoJSON()
    const features = srcDrawFld.getFeatures();
    const jsonutm = format.writeFeatures(features,{dataProjection: newProjCode,
                                          featureProjection: 'EPSG:3857'});
    var json3857 = format.writeFeatures(features); 
    // Besides taking care of the field boundary, others need also to be 
    // calculated. These include:
    // 1. update extent based on field boundary. This needs 
    // to be done to make sure the watershed delineated are 
    // large enough to cover the whole field drawn by the user.
    // This is was done by include certain buffers around the 
    // extent of the field boundary. Update the extent with 
    // buffers and clip dem with the updated extent.
    var src  = map.getView().getProjection().getCode();
//  var mapext = map.getView().calculateExtent();
    // Get the extent of the user drawn field boundary
    var fldext = srcDrawFld.getExtent();    
    // Convert the extent from EPSG:3857 default to dem projection 4326
    var fldext4326 = ol.proj.transformExtent(fldext,src,"EPSG:4326");   
    // Judge the area, it can not be too large
    var boxwidth = ol.extent.getWidth(fldext4326);
    var boxheight = ol.extent.getHeight(fldext4326);
    var box = fldext4326;

    var boxwidth025 = boxwidth + 0.025;
    var boxheight025 = boxheight + 0.025;
    //alert('height: ' + boxheight + 'height025' + boxheight025);   
    if ((boxwidth025 > 0.30) || (boxheight025 > 0.30)) {
        areaTooLarge(wid,hgt);
    }
    else
    {
        // Judge the critical area for watershed delineation
        var crit = document.mapserv.CRIT.value;
        if (crit < 0) {
            generalPopup('Critical source area must be greater than 0.');
            return;
        }
        else
        {   
            document.mapserv.DOWSFD.value = "1";
            document.mapserv.EXTENT.value = escape(fldext4326);
            document.mapserv.JSONFD.value = escape(jsonutm);
            document.mapserv.JSF3857.value = escape(json3857);
            document.mapserv.submit();
        }   
    
    }
    //alert(fldext4326 + ', height ' + boxheight + ', width ' + boxwidth  );
}


//
// setPass1Layers()
 //
 // This function is called after the channel delineation has been run. The channel network layer
 // is added. The outlet button is enabled to allow selecting an outlet on the channel network.
 //
function setFldStep1Layers(wsnoinfld)
{
        var network_lay = new ol.layer.Image({
                  title: 'Network',
                  source: new ol.source.ImageWMS({
                  url:mapserv,
                  crossOrigin: 'anonymous',
                  attributions: 'USDA-ARS TOPAZ',
                  params : {
                      'LAYERS': 'network',
                      'map' : '<?=$taudemMap?>',
                     },
                  serverType: 'mapserver'
                })
        });
        map.addLayer(network_lay);

    // Adding watershed boundaries
    for (iws = 1; iws <= wsnoinfld; iws++) {
        var ws_lay = new ol.layer.Image({
                  title: 'Watershed' + iws.toString(),
                  source: new ol.source.ImageWMS({
                  url:mapserv,
                  crossOrigin: 'anonymous',
                  attributions: 'USDA-ARS TOPAZ',
                  params : {
                      'LAYERS': 'watershed' + iws.toString(),
                      'map' : '<?=$taudemMap?>',
                     },
                  serverType: 'mapserver'
                })
            });
            map.addLayer(ws_lay);

            var subid_lay = new ol.layer.Image({
                title: 'Subarea ID for Watershed' + iws.toString(),
                source: new ol.source.ImageWMS({
                url:mapserv,
                crossOrigin: 'anonymous',
                attributions: 'USDA-ARS TOPAZ',
                params : {
                    'LAYERS': 'subareaid' + iws.toString(),
                    'map' : '<?=$taudemMap?>',
                },
                serverType: 'mapserver'
                })
                });
                map.addLayer(subid_lay);
    }


    var fldjsonstr = '<?php echo $_SESSION['SSVAR']['jsf3857']?>';
	// The string need to be parsed to a json object.
	// To display the polygon, a style need to be defined
	var fldStroke = new ol.style.Stroke({
	   color : 'black',
	   width : 2    
	});

	var fldFill = new ol.style.Fill({
	   color: 'rgba(255,0,0,1.0)'
	});		

	var fldStyle = new ol.style.Style({
	   stroke : fldStroke
	  // fill : _myFill
	});

    var fldjsonparsed = JSON.parse(fldjsonstr);

	var formatjson = new ol.format.GeoJSON({
		featureProjection:'EPSG:4832'});

	srcDrawFld = new ol.source.Vector({
            features:formatjson.readFeatures(
			fldjsonparsed)
    })

	vecDrawFld = new ol.layer.Vector({
        title: 'User Field Boundary',
        source: srcDrawFld,
        style: fldStyle
	});

    map.addLayer(vecDrawFld);

}






function setFldStep3Layers(wsnoinfld)
{
    for (iws = 1; iws <= wsnoinfld; iws++) {
        var aaqlay = new ol.layer.Image({
                  title: 'Runoff (mm) ' + iws.toString(),
                  source: new ol.source.ImageWMS({
                  url:mapserv,
                  crossOrigin: 'anonymous',
                  attributions: 'USDA-ARS TOPAZ',
                  params : {
                      'LAYERS': 'aaq' + iws.toString(),
                      'map' : '<?=$taudemMap?>',
                     },
                  serverType: 'mapserver'
                })
            });
            map.addLayer(aaqlay);

        var aaselay = new ol.layer.Image({
                  title: 'Soil Erosion (ton/ha) ' + iws.toString(),
                  source: new ol.source.ImageWMS({
                  url:mapserv,
                  crossOrigin: 'anonymous',
                  attributions: 'USDA-ARS TOPAZ',
                  params : {
                      'LAYERS': 'aarsl2' + iws.toString(),
                      'map' : '<?=$taudemMap?>',
                     },
                  serverType: 'mapserver'
                })
            });
            map.addLayer(aaselay);

        var aatnlay = new ol.layer.Image({
                  title: 'Total nitrogen (kg/ha) ' + iws.toString(),
                  source: new ol.source.ImageWMS({
                  url:mapserv,
                  crossOrigin: 'anonymous',
                  attributions: 'USDA-ARS TOPAZ',
                  params : {
                      'LAYERS': 'aatn' + iws.toString(),
                      'map' : '<?=$taudemMap?>',
                     },
                  serverType: 'mapserver'
                })
            });
            map.addLayer(aatnlay);


        var aatplay = new ol.layer.Image({
                  title: 'Total phosphorus (kg/ha) ' + iws.toString(),
                  source: new ol.source.ImageWMS({
                  url:mapserv,
                  crossOrigin: 'anonymous',
                  attributions: 'USDA-ARS TOPAZ',
                  params : {
                      'LAYERS': 'aatp' + iws.toString(),
                      'map' : '<?=$taudemMap?>',
                     },
                  serverType: 'mapserver'
                })
            });
            map.addLayer(aatplay);




    }

}        




function setFldStep3Layers_fldqsnpmap(scenarioarr)
{
	
	var numofsce = Object.keys(scenarioarr).length/2;
    for (var si = 0; si <numofsce; si++)
    {
        var sceno = si + 1;
        var scenkeyfull = 's' + sceno.toString() + '_full';
        var scenkey = 's' + sceno.toString();

	
        var fldaaqlay = new ol.layer.Image({
                  title: 'Field Runoff (mm) ' + scenarioarr[scenkeyfull],
                  source: new ol.source.ImageWMS({
                  url:mapserv,
                  crossOrigin: 'anonymous',
                  attributions: 'USDA-ARS TOPAZ',
                  params : {
                      'LAYERS': scenarioarr[scenkey] + 'fldaaq',
                      'map' : '<?=$taudemMap?>',
                     },
                  serverType: 'mapserver'
                })
            });
            map.addLayer(fldaaqlay);

        var fldaaselay = new ol.layer.Image({
                  title: 'Field Soil Erosion (ton/ha) ' + scenarioarr[scenkeyfull],
                  source: new ol.source.ImageWMS({
                  url:mapserv,
                  crossOrigin: 'anonymous',
                  attributions: 'USDA-ARS TOPAZ',
                  params : {
                      'LAYERS': scenarioarr[scenkey] + 'fldaarsl2',
                      'map' : '<?=$taudemMap?>',
                     },
                  serverType: 'mapserver'
                })
            });
            map.addLayer(fldaaselay);

        var fldaatnlay = new ol.layer.Image({
                  title: 'Field Total nitrogen (kg/ha) ' + scenarioarr[scenkeyfull],
                  source: new ol.source.ImageWMS({
                  url:mapserv,
                  crossOrigin: 'anonymous',
                  attributions: 'USDA-ARS TOPAZ',
                  params : {
                      'LAYERS': scenarioarr[scenkey] + 'fldaatn',
                      'map' : '<?=$taudemMap?>',
                     },
                  serverType: 'mapserver'
                })
            });
            map.addLayer(fldaatnlay);


        var fldaatplay = new ol.layer.Image({
                  title: 'Field Total phosphorus (kg/ha) ' + scenarioarr[scenkeyfull],
                  source: new ol.source.ImageWMS({
                  url:mapserv,
                  crossOrigin: 'anonymous',
                  attributions: 'USDA-ARS TOPAZ',
                  params : {
                      'LAYERS': scenarioarr[scenkey] + 'fldaatp',
                      'map' : '<?=$taudemMap?>',
                     },
                  serverType: 'mapserver'
                })
            });
            map.addLayer(fldaatplay);
	}
}





// Setup APEX model for watershed delineated with outlet
function setupAPEXFld(){
    document.mapserv.DOJAPFD.value = "1";
    document.mapserv.submit();
}

// Run APEX model for watershed delineated with outlet
function runAPEXFld(){
    document.mapserv.DORAPFD.value = "1";
    document.mapserv.submit();
}

// Run APEX model for watershed delineated with outlet
function runAPEX(){
    document.mapserv.DORAPEX.value = "1";
    document.mapserv.submit();
}



</script>

