<?php
//
// globals.php
// 
// These should be used in php code to make moving to other machines easier.
//
// 
//
//


class genefuncs
{

    public function setWorkingDir()
    {
        global $globWorkRoot, $globwebdir;
        $workingDir = $globWorkRoot . session_id();
        addToLog("Working Directory: '" . $workingDir . "'<br>");
        if (!file_exists($workingDir)) {
            addToLog("Setup Session Directory|" . $workingDir);

            mkdir($workingDir);
        }

        if (!file_exists($workingDir . "/gislayers")) {
           mkdir($workingDir . "/gislayers");}
        
        if (!file_exists($workingDir . "/taudemlayers")) {
           mkdir($workingDir . "/taudemlayers");}

        if (!file_exists($workingDir . "/apexruns")) {
           mkdir($workingDir . "/apexruns");}

        if (!file_exists($workingDir . "/soils")) {
           mkdir($workingDir . "/soils");}

        if (file_exists($workingDir . "/landuse") == FALSE)
        {mkdir($workingDir . "/landuse");}

        if (!file_exists($workingDir . "/dbfextract")) {
            copy($globwebdir."gisutils/dbfextract",$workingDir . "/dbfextract");
            chmod($workingDir . "/dbfextract", 0755);
        }

        if (file_exists($workingDir . "/apexoutjsonmap") == FALSE)
        {mkdir($workingDir . "/apexoutjsonmap");}



    }


    public function doZoom($zoomLoc,$latitude,$longitude)
    {
        global $globgisdir;
        // Arr has lat long and return message
        $str2 = sprintf(">>%s<<",$zoomLoc);
        $zoomLoc = trim($zoomLoc);
        $found = 0;
        $arr = array($latitude,$longitude,$str2,$found);

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
                                $arr[2]=$str2;
                                $arr[3]=$found;
                                break;
                            }
                        }
                    }
            fclose($zf);
            if ($found == 0) {
                $str2 = sprintf("<br>Zip code %d not found<br>",$zipVal);
                $arr[2]=$str2;
                $arr[3]=$found;
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

                    $str2 = sprintf("Found longitude %d and latitide: %d<br>",$city,$state);
                    $arr[2]=$str2;
                    $arr[3]=$found;
                } else
                {
                    $str2 = sprintf("Couldn't find latitude %d and longitude: %d<br>",$city,$state);
                    $arr[2]=$str2;
                }
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
                                
                                $str2 = sprintf("Found %s, %s<br>",$city,$state);
                                $arr[2]=$str2;
                                $arr[3]=$found;
                                break;
                            }
                        }
                    }
                    fclose($zf);

                if ($found == 0)
                {
                    $str2 = sprintf("<br>%s, %s not found<br>",$city,$state);
                    $arr[2]=$str2;
                }
            }
        }
        }

        return $arr;
    }



    /*
    * php delete function that deals with directories recursively
     */
    public function deletefiles($target) 
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




















}

?>
