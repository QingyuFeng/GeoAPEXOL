<?php
    // application folder name
    defined('FDAPP')
    or define('FDAPP', 'geoapexol');

    // Load global variables
    require('/var/www/html/' . FDAPP . '/php/globals.php');

    // Define path to save sessions
    session_save_path(FD_TMPSESSION);

    // Start session
    session_start();

    if (!isset($_SESSION['SSVAR']))
    {
        // Read in default json file
        $_SESSION["SSVAR"] = json_decode(file_get_contents(JS_SSVARJSON), true);
    }


    // require files
    require('php/genefuncs.php');
    require('php/gdalfuncs.php');
    require('php/taudemfuncs.php');
    require('php/mapfilefuncs.php');
    require('php/soilfuncs.php');
    require('php/lufuncs.php');
    require('php/database.php');
    require('php/apexfuncs.php');
    require('php/updatejs.php');

    // Defining classes:
    $genefuncs = new genefuncs();
    $gdalfuncs = new gdalfuncs();
    $taudemfuncs = new taudemfuncs();
    $mapfilefuncs = new mapfilefuncs();
    $soilfuncs = new soilfuncs();
    $lufuncs = new lufuncs();
    $apexfuncs = new apexfuncs();

?>


