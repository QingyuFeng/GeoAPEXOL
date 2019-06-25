<?php

    //database credentials
    define('DBHOST','localhost');
    define('DBUSER','postgres');
    define('DBPASS','nserl');
    define('DBNAME','apexwebinput');

    // Get the database connection
    try {

        //create PDO connection
        $db = new PDO("pgsql:dbname=".DBNAME.";host=".DBHOST, DBUSER, DBPASS);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    } catch(PDOException $e) {
            //show error
        echo '<p class="bg-danger">'.$e->getMessage().'</p>';
        exit;
    }

    if (!($Connection = pg_connect("host=localhost dbname=apexwebinput user=postgres password=nserl")))
    {
        print("Could not establish connection.<BR>\n");
        exit;
    }   
    else
    {$Connection = pg_connect("host=localhost dbname=apexwebinput user=postgres password=nserl");}
    
?>
