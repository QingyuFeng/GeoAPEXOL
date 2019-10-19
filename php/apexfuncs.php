<?php
//
// .php
// 
// These should be used in php code to make moving to other machines easier.
//
// 

//include($globwebdir . 'php/updatejs.php');

class apexfuncs
{
    

    public function copyJSONFILEStoSceFD($workingDir, $scerunfdname)
    {
    // runsite.json
    // var1wsssub.json
    // wssubsollulatlon.json
    // runsub.json
        $srcjsonrunsite = $workingDir . '/apexruns/runsite.json';
        $destjsonrunsite = $workingDir . '/apexruns/' . $scerunfdname . '/runsite.json'; 
        if (!copy($srcjsonrunsite,$destjsonrunsite))
        {
            addToLog("Error copying runsite.json to the apexrun folder");
            return false;
        }

        $srcjsonvar1 = $workingDir . '/apexruns/var1wssub.json';
        $destjsonvar1 = $workingDir . '/apexruns/' . $scerunfdname . '/var1wssub.json';
        if (!copy($srcjsonvar1,$destjsonvar1))
        {
            addToLog("Error copying var1wssub.json to the apexrun folder");
            return false;
        }

        $srcjsonsub = $workingDir . '/apexruns/runsub.json';
        $destjsonsub = $workingDir . '/apexruns/' . $scerunfdname . '/runsub.json';
        if (!copy($srcjsonsub,$destjsonsub))
        {
            addToLog("Error copying runsub.json to the apexrun folder");
            return false;
        }

        $srcjsonwss = $workingDir . '/apexruns/wssubsollulatlon.json';
        $destjsonwss = $workingDir . '/apexruns/' . $scerunfdname . '/wssubsollulatlon.json';
        if (!copy($srcjsonwss,$destjsonwss))
        {
            addToLog("Error copying wssubsollulatlon.json to the apexrun folder");
            return false;
        }

        return TRUE;
    }



    public function getClimStation($workingDir)
    {
        // Call an external c++ program to find the stationlistfile
        global $globwebdir;
        $weastnlist = $globalwebdir . 'gisutils/clinear/stations2015.db';

        $cmd = $globalwebdir . 'gisutils/clinear/climNearest ' . $_SESSION["SSVAR"]["longitude"] . ' ' . $_SESSION["SSVAR"]["latitude"] . ' ' . $workingDir . '/apexruns/ ' . $weastnlist;

        exec($cmd, $output, $rc);
        if ($rc !== 0) {
           addToLog("<p>***Error Could not execute: " . $rc . "***" . $cmd . "***<p>");
        }

    }

    //
    // modSubJsonOlt
    //
    // This function will read the demw, soil, slope,
    // and landuse asc files. The characteristics of
    // watershed and subareas will be calculated and
    // stored into a text file. These information will
    // include:
    // 1. watershed and subarea area
    // 2. distribution and combination of soil, slope,
    //    landuse, and elevation for each subarea.
    //    distribution will include the area and percent
    //    of area.
    // 3. This interface will use dominant combination
    // of soil, landuse, and slope group as the properties
    // of the subarea.

    public function preAPEXJSONOlt($workingDir, $scerunfdname) 
    {
        global $globwebdir;
        addToLog('Copy template var1wssub.json to the apexrun folder');
        $origvar1wssubjson = $globwebdir . 'json/var1wssub.json';
        $destvar1json = $workingDir . '/apexruns/'.$scerunfdname .'/var1wssub.json';
        if (!copy($origvar1wssubjson,$destvar1json))
        {
            $error[]="<p>Error copying var1wssub.json to the apexrun folder <p>";
            return false;
        }

        addToLog('Copy template tmpsitefile.json to the apexrun folder');
        $origsitjson = $globwebdir . 'json/tmpsitefile.json';
        $destsitjson = $workingDir . '/apexruns/'.$scerunfdname .'/tmpsitefile.json';
        if (!copy($origsitjson,$destsitjson))
        {
            $error[]="<p>Error copying tmpsitefile.json to the apexrun folder <p>";
            return false;
        }
        
        $cmd = escapeshellcmd("/usr/bin/python3 ". $globwebdir . "gisutils/oltprepJSONs.py ". $workingDir . " " . $globwebdir . "apexdata/table_nassmgtupn.csv " . $globwebdir . "apexdata/table_chn.txt " . $globwebdir . "gisutils/utm ". $scerunfdname);
        addToLog($cmd);
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
            return false;
        }
        else{return TRUE;}
    }


    public function preAPEXJSONFld($workingDir, $scerunfdname)
    {
        global $globwebdir;
        addToLog('Copy template var1wssub.json to the apexrun folder');
        $origvar1wssubjson = $globwebdir . 'json/var1wssub.json';
        $destvar1json = $workingDir . '/apexruns/'.$scerunfdname .'/var1wssub.json';
        if (!copy($origvar1wssubjson,$destvar1json))
        {
            $error[]="<p>Error copying var1wssub.json to the apexrun folder <p>";
            return false;
        }

        addToLog('Copy template tmpsitefile.json to the apexrun folder');
        $origsitjson = $globwebdir . 'json/tmpsitefile.json';
        $destsitjson = $workingDir . '/apexruns/'.$scerunfdname .'/tmpsitefile.json';
        if (!copy($origsitjson,$destsitjson))
        {
            $error[]="<p>Error copying tmpsitefile.json to the apexrun folder <p>";
            return false;
        }

        $cmd = escapeshellcmd("/usr/bin/python3 ". $globwebdir . "gisutils/fldprepJSONs.py ". $workingDir . " " . $globwebdir . "apexdata/table_nassmgtupn.csv " . $globwebdir . "apexdata/table_chn.txt " . $globwebdir . "gisutils/utm ". $scerunfdname);
        addToLog($cmd);
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
            return false;
        }
        else{return TRUE;}
    }



    public function writeAInputFld($workingDir)
    {
        global $globwebdir;

        // python .py workingDir climDir
        $cmd = escapeshellcmd("/usr/bin/python3 ". $globwebdir . "gisutils/fldwriteapexinputs.py ". $workingDir . " " . $globwebdir . "gisutils/clinear");
        addToLog($cmd);
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
            return false;
        }
        else{return TRUE;}
    }


    public function writeAInputOlt($workingDir)
    {
        global $globwebdir;

        // python .py workingDir climDir
        $cmd = escapeshellcmd("/usr/bin/python3 ". $globwebdir . "gisutils/oltwriteapexinputs.py ". $workingDir . " " . $globwebdir . "gisutils/clinear");
        addToLog($cmd);
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
            return false;
        }
        else{return TRUE;}
    }


    public function writeSOLCOM($workingDir, $scenario)
    {
        global $globwebdir;
        $cmd = escapeshellcmd("/usr/bin/python3 ". $globwebdir . "gisutils/writeSOLCOM.py ". $workingDir . " " . $scenario);
        addToLog($cmd);
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
            return false;
        }
        else{return TRUE;}
    }

    public function writeSOLFile($workingDir, $scenario)
    {
        global $globwebdir;
        $cmd = escapeshellcmd("/usr/bin/python3 ". $globwebdir . "gisutils/writejson2sol.py ". $workingDir. " " . $scenario);
        addToLog($cmd);
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
            return false;
        }
        else{return TRUE;}
    }


    public function writeSOL($workingDir, $Connection, $scenariofdname)
    {
        global $globwebdir;
        $updatejs = new updatejs();

        addToLog('Copy template tmpsolfile.json to the apexrun folder');
        $srcsoljson = $globwebdir . 'json/tmpsolfile.json';
        $destsoljson = $workingDir . '/apexruns/'. $scenariofdname . '/tmpsolfile.json';
        if (!copy($srcsoljson,$destsoljson))
        {
            $error[]="<p>Error copying tmpsolfile.json to the apexrun folder <p>";
            return false;
        }
        
        $this->writeSOLCOM($workingDir, $scenariofdname);

        // Get the unique soil list
        // runsoljson: json contains template for all soil mukeys
        // runsoljson2: array from json
        // runsoljson3: updated array str of soil json from database
        $fnrunsoljson = $workingDir . '/apexruns/'. $scenariofdname . '/runsol.json';
        $runsoljson2 = json_decode(file_get_contents($fnrunsoljson), true);
        $solmklst = array_keys($runsoljson2);
        foreach ($solmklst as $solmk)
        {
            $runsoljson2 = $updatejs->UpdateSOLJSON($Connection,
                                        $solmk,
                                        $runsoljson2);
            //echo('<br>'.  $runsoljson2[$solmk]['solmukey'] . '<br>');
        }
        // After upating, write it into the file
         $runsoljson3 = json_encode($runsoljson2, true, JSON_UNESCAPED_UNICODE);
        //now send evrything to ur data.json file using folowing code
        // Write json output into the run folder
        if (json_decode($runsoljson3) != null)
        {
            $file = fopen($fnrunsoljson,'w');
            fwrite($file, $runsoljson3);
            fclose($file);
            //echo('write successfully');
        }
        else
        {
            $error[]="<p>Error writing updated json to the json file <p>";
            return;
            
        } 

        // Write the SOL Files from json:
        $this->writeSOLFile($workingDir, $scenariofdname);

        return TRUE;    
    
    }


    public function writeCLICOMs($workingDir, $scenariofdname)
    {
        global $globwebdir;
        $cmd = escapeshellcmd("/usr/bin/python3 ". $globwebdir . "gisutils/writeCLICOM.py ". $workingDir . " " . $globwebdir . "gisutils/clinear " . $scenariofdname);
        addToLog($cmd);
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
            return false;
        }
        else{return TRUE;}
    }



    public function writeSITFLCOM($workingDir, $scenariofdname)
    {
        global $globwebdir;
        $cmd = escapeshellcmd("/usr/bin/python3 ". $globwebdir . "gisutils/writeSIT.py ". $workingDir . " " . $scenariofdname);
        addToLog($cmd);
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
            return false;
        }
        else{return TRUE;}
    }


    public function writeSUBFLCOM($workingDir, $scenariofdname)
    {
        global $globwebdir;
        $cmd = escapeshellcmd("/usr/bin/python3 ". $globwebdir . "gisutils/writeSUB.py ". $workingDir . " " . $scenariofdname);
        addToLog($cmd);
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
            return false;
        }
        else{return TRUE;}
    }


    public function writeCLIFILEs($workingDir, $Connection, $scenariofdname)
    {
        global $globwebdir;
        
        $fnstnjson = $workingDir . '/apexruns/' . $scenariofdname . '/runclistn.json';
        $stninfo = json_decode(file_get_contents($fnstnjson), true);
        $stabbr = $stninfo[1][0];
        $stnname = $stninfo[1][3];

        $this->WriteWP1WNDfile($Connection, 
                $stnname, 
                $stabbr,
                $workingDir,
                $scenariofdname
                );

        $dlystartyr = $_SESSION["SSVAR"]["dlystartyr"];
        $dlyendyr = $_SESSION["SSVAR"]["dlyendyr"];
        
        // TODO: Enable this if possbile. Write DLY takes 10s 
        // Without writing it, the program is fast.
        //$this->WriteDLYFile($Connection,
        //    $stnname,
        //    $stabbr,
        //    $dlystartyr,
        //    $dlyendyr,
        //    $workingDir
        //    );


        return TRUE;
    
    }



    public function WriteDLYFile($Connection,
        $stnname,
        $stateabb,
        $dlystartyr,
        $dlyendyr,
        $workingDir
        )
    {

        $fn_dly = $workingDir . '/apexruns/' . trim($stnname) . '.DLY';

        $stnst = substr($stnname, 0, 2);
        $statelower = strtolower($stnst);
        $stnname_lowerstab = trim($statelower).trim(substr($stnname, 2));

        $sqlstmt = "SELECT * from " . $statelower . "_obspt4dly where stationname ='" . $stnname_lowerstab . "' AND yearid >= '" . $dlystartyr . "' AND yearid <= '" . $dlyendyr . "' order by stnvaryrid";
        if(!($Result = pg_exec($Connection, $sqlstmt)))
        {
            print("Could not execute query: ");
            print($sqlstmt);
            print(pg_errormessage($Connection));
            pg_close($Connection);
            print("<BR>\n");
            exit;
        }
        $Rows = pg_numrows($Result);

        if ($Rows == 0) {
            echo "<center>";
            echo "There are no records.";
            echo $sqlstmt;
            echo "</center>";
            pg_freeresult($Result);
            exit;
        }
    
        $fid_dly = fopen($fn_dly, "w");

		// Generate a range of date series
		$dlystartdate = "" . $dlystartyr . "-01-01";
		$dlyenddate = "" . $dlyendyr . "-12-31";

		$dlyperiod = new DatePeriod(
			new DateTime($dlystartdate),
			new DateInterval('P1D'),
			new DateTime($dlyenddate)
			);
        
        foreach($dlyperiod as $date){
			$nodayyr = (int)$this->getdayoftheyear($date->format('d M Y'))+1;
			for ($Row=0; $Row < pg_numrows($Result); $Row+=3) {
				if ((trim(pg_result($Result,$Row+1,"dayiyr".$nodayyr."")) == '9999')
					or (trim(pg_result($Result,$Row+2,"dayiyr".$nodayyr."")) == '9999')
					or (trim(pg_result($Result,$Row,"dayiyr".$nodayyr."")) == '9999')
					)
				{
					fprintf($fid_dly, "%6s%4s%4s%6.2f%6.0f%6.0f%6.0f%6.2f%6.2f\n", 
						$date->format('Y'),
						$date->format('m'),
						$date->format('d'),
						0.0, 
						trim(pg_result($Result,$Row+1,"dayiyr".$nodayyr."")),
						trim(pg_result($Result,$Row+2,"dayiyr".$nodayyr."")),
						trim(pg_result($Result,$Row,"dayiyr".$nodayyr."")),
						0.0, 0.0
						);
				}
				else 
				{
					fprintf($fid_dly, "%6s%4s%4s%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f\n", 
						$date->format('Y'),
						$date->format('m'),
						$date->format('d'),
						0.0, 
						trim(pg_result($Result,$Row+1,"dayiyr".$nodayyr."")),
						trim(pg_result($Result,$Row+2,"dayiyr".$nodayyr."")),
						trim(pg_result($Result,$Row,"dayiyr".$nodayyr."")),
						0.0, 0.0									
						);
				}
			}
		}
    
        


        pg_freeresult($Result);
        fclose($fid_dly);
    
    }


    public function getdayoftheyear($dateString)
    {
      $day = date('z', strtotime($dateString));
      return $day;
    }








    public function WriteWP1WNDfile($Connection,
                $stnname,
                $stabbr, 
                $workingDir,
                $scenariofdname
                )
    {
        $fnwp1 = $workingDir . '/apexruns/'. $scenariofdname . '/' . trim($stnname) . '.WP1';
        $fnwnd = $workingDir . '/apexruns/'. $scenariofdname . '/' . trim($stnname) . '.WND';

        $stnst = substr($stnname, 0, 2);
		$statelower = strtolower($stnst);
		$stnname_lowerstab = trim($statelower).trim(substr($stnname, 2));
		$sqlstmt = "SELECT * from " . $statelower . "_monstat4wp1 where stationname ='" . $stnname_lowerstab . "'"; 

		if(!($Result = pg_exec($Connection, $sqlstmt)))
		{
				print("Could not execute query: ");
				print($sqlstmt);
				print(pg_errormessage($Connection));
				pg_close($Connection);
				print("<BR>\n");
				exit;
		}

		$Rows = pg_numrows($Result);
		if ($Rows == 0) {
				echo "<center>";
				echo "There are no records.";
				echo $sqlstmt;
				echo "</center>";
				pg_freeresult($Result);
				exit;
		}

		$fid_wp1 = fopen($fnwp1, "w");
		fprintf($fid_wp1, "%14s%13s%s\n", trim(pg_result(
			$Result,0,"stationname")), 
			trim($stnst), 
			pg_result($Result,0,"stationlocation")
																);
		fprintf($fid_wp1, "    LAT = %7.2f   LON =   %7.2f    ELEV = %7.2f     \n",
			trim(pg_result($Result,0,"latitude")), 
			trim(pg_result($Result,0,"longitude")), 
			trim(pg_result($Result,0,"elevation"))
			);						

		fprintf($fid_wp1, "%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f\n", 
			trim(pg_result($Result,0,"obmxmon1")), 
			trim(pg_result($Result,0,"obmxmon2")), 
			trim(pg_result($Result,0,"obmxmon3")), 
			trim(pg_result($Result,0,"obmxmon4")), 
			trim(pg_result($Result,0,"obmxmon5")), 
			trim(pg_result($Result,0,"obmxmon6")), 
			trim(pg_result($Result,0,"obmxmon7")), 
			trim(pg_result($Result,0,"obmxmon8")), 
			trim(pg_result($Result,0,"obmxmon9")), 
			trim(pg_result($Result,0,"obmxmon10")), 
			trim(pg_result($Result,0,"obmxmon11")), 
			trim(pg_result($Result,0,"obmxmon12"))
			);									

		fprintf($fid_wp1, "%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f\n", 
			trim(pg_result($Result,0,"obmnmon1")), 
			trim(pg_result($Result,0,"obmnmon2")), 
			trim(pg_result($Result,0,"obmnmon3")), 
			trim(pg_result($Result,0,"obmnmon4")), 
			trim(pg_result($Result,0,"obmnmon5")), 
			trim(pg_result($Result,0,"obmnmon6")), 
			trim(pg_result($Result,0,"obmnmon7")), 
			trim(pg_result($Result,0,"obmnmon8")), 
			trim(pg_result($Result,0,"obmnmon9")), 
			trim(pg_result($Result,0,"obmnmon10")), 
			trim(pg_result($Result,0,"obmnmon11")), 
			trim(pg_result($Result,0,"obmnmon12"))
			);										

		fprintf($fid_wp1, "%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f\n", 
			trim(pg_result($Result,0,"stdmxmon1")), 
			trim(pg_result($Result,0,"stdmxmon2")), 
			trim(pg_result($Result,0,"stdmxmon3")), 
			trim(pg_result($Result,0,"stdmxmon4")), 
			trim(pg_result($Result,0,"stdmxmon5")), 
			trim(pg_result($Result,0,"stdmxmon6")), 
			trim(pg_result($Result,0,"stdmxmon7")), 
			trim(pg_result($Result,0,"stdmxmon8")), 
			trim(pg_result($Result,0,"stdmxmon9")), 
			trim(pg_result($Result,0,"stdmxmon10")), 
			trim(pg_result($Result,0,"stdmxmon11")), 
			trim(pg_result($Result,0,"stdmxmon12"))
			);										

		fprintf($fid_wp1, "%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f\n", 
			trim(pg_result($Result,0,"stdmnmon1")), 
			trim(pg_result($Result,0,"stdmnmon2")), 
			trim(pg_result($Result,0,"stdmnmon3")), 
			trim(pg_result($Result,0,"stdmnmon4")), 
			trim(pg_result($Result,0,"stdmnmon5")), 
			trim(pg_result($Result,0,"stdmnmon6")), 
			trim(pg_result($Result,0,"stdmnmon7")), 
			trim(pg_result($Result,0,"stdmnmon8")), 
			trim(pg_result($Result,0,"stdmnmon9")), 
			trim(pg_result($Result,0,"stdmnmon10")), 
			trim(pg_result($Result,0,"stdmnmon11")), 
			trim(pg_result($Result,0,"stdmnmon12"))
			);												

		fprintf($fid_wp1, "%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f\n", 
			trim(pg_result($Result,0,"rmomon1")), 
			trim(pg_result($Result,0,"rmomon2")), 
			trim(pg_result($Result,0,"rmomon3")), 
			trim(pg_result($Result,0,"rmomon4")), 
			trim(pg_result($Result,0,"rmomon5")), 
			trim(pg_result($Result,0,"rmomon6")), 
			trim(pg_result($Result,0,"rmomon7")), 
			trim(pg_result($Result,0,"rmomon8")), 
			trim(pg_result($Result,0,"rmomon9")), 
			trim(pg_result($Result,0,"rmomon10")), 
			trim(pg_result($Result,0,"rmomon11")), 
			trim(pg_result($Result,0,"rmomon12"))
			);												

		fprintf($fid_wp1, "%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f\n", 
			trim(pg_result($Result,0,"rst2mon1")), 
			trim(pg_result($Result,0,"rst2mon2")), 
			trim(pg_result($Result,0,"rst2mon3")), 
			trim(pg_result($Result,0,"rst2mon4")), 
			trim(pg_result($Result,0,"rst2mon5")), 
			trim(pg_result($Result,0,"rst2mon6")), 
			trim(pg_result($Result,0,"rst2mon7")), 
			trim(pg_result($Result,0,"rst2mon8")), 
			trim(pg_result($Result,0,"rst2mon9")), 
			trim(pg_result($Result,0,"rst2mon10")), 
			trim(pg_result($Result,0,"rst2mon11")), 
			trim(pg_result($Result,0,"rst2mon12"))
			);												

		fprintf($fid_wp1, "%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f\n", 
			trim(pg_result($Result,0,"rst3mon1")), 
			trim(pg_result($Result,0,"rst3mon2")), 
			trim(pg_result($Result,0,"rst3mon3")), 
			trim(pg_result($Result,0,"rst3mon4")), 
			trim(pg_result($Result,0,"rst3mon5")), 
			trim(pg_result($Result,0,"rst3mon6")), 
			trim(pg_result($Result,0,"rst3mon7")), 
			trim(pg_result($Result,0,"rst3mon8")), 
			trim(pg_result($Result,0,"rst3mon9")), 
			trim(pg_result($Result,0,"rst3mon10")), 
			trim(pg_result($Result,0,"rst3mon11")), 
			trim(pg_result($Result,0,"rst3mon12"))
																);												

		fprintf($fid_wp1, "%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f\n", 
			trim(pg_result($Result,0,"prw1mon1")), 
			trim(pg_result($Result,0,"prw1mon2")), 
			trim(pg_result($Result,0,"prw1mon3")), 
			trim(pg_result($Result,0,"prw1mon4")), 
			trim(pg_result($Result,0,"prw1mon5")), 
			trim(pg_result($Result,0,"prw1mon6")), 
			trim(pg_result($Result,0,"prw1mon7")), 
			trim(pg_result($Result,0,"prw1mon8")), 
			trim(pg_result($Result,0,"prw1mon9")), 
			trim(pg_result($Result,0,"prw1mon10")), 
			trim(pg_result($Result,0,"prw1mon11")), 
			trim(pg_result($Result,0,"prw1mon12"))
			);												

		fprintf($fid_wp1, "%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f\n", 
			trim(pg_result($Result,0,"prw2mon1")), 
			trim(pg_result($Result,0,"prw2mon2")), 
			trim(pg_result($Result,0,"prw2mon3")), 
			trim(pg_result($Result,0,"prw2mon4")), 
			trim(pg_result($Result,0,"prw2mon5")), 
			trim(pg_result($Result,0,"prw2mon6")), 
			trim(pg_result($Result,0,"prw2mon7")), 
			trim(pg_result($Result,0,"prw2mon8")), 
			trim(pg_result($Result,0,"prw2mon9")), 
			trim(pg_result($Result,0,"prw2mon10")), 
			trim(pg_result($Result,0,"prw2mon11")), 
			trim(pg_result($Result,0,"prw2mon12"))
			);


		fprintf($fid_wp1, "%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f\n", 
			trim(pg_result($Result,0,"uavmmon1")), 
			trim(pg_result($Result,0,"uavmmon2")), 
			trim(pg_result($Result,0,"uavmmon3")), 
			trim(pg_result($Result,0,"uavmmon4")), 
			trim(pg_result($Result,0,"uavmmon5")), 
			trim(pg_result($Result,0,"uavmmon6")), 
			trim(pg_result($Result,0,"uavmmon7")), 
			trim(pg_result($Result,0,"uavmmon8")), 
			trim(pg_result($Result,0,"uavmmon9")), 
			trim(pg_result($Result,0,"uavmmon10")), 
			trim(pg_result($Result,0,"uavmmon11")), 
			trim(pg_result($Result,0,"uavmmon12"))
			);																			

		fprintf($fid_wp1, "%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f\n", 
			trim(pg_result($Result,0,"wimon1")), 
			trim(pg_result($Result,0,"wimon2")), 
			trim(pg_result($Result,0,"wimon3")), 
			trim(pg_result($Result,0,"wimon4")), 
			trim(pg_result($Result,0,"wimon5")), 
			trim(pg_result($Result,0,"wimon6")), 
			trim(pg_result($Result,0,"wimon7")), 
			trim(pg_result($Result,0,"wimon8")), 
			trim(pg_result($Result,0,"wimon9")), 
			trim(pg_result($Result,0,"wimon10")), 
			trim(pg_result($Result,0,"wimon11")), 
			trim(pg_result($Result,0,"wimon12"))
			);												

		fprintf($fid_wp1, "%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f\n", 
			trim(pg_result($Result,0,"obslmon1")), 
			trim(pg_result($Result,0,"obslmon2")), 
			trim(pg_result($Result,0,"obslmon3")), 
			trim(pg_result($Result,0,"obslmon4")), 
			trim(pg_result($Result,0,"obslmon5")), 
			trim(pg_result($Result,0,"obslmon6")), 
			trim(pg_result($Result,0,"obslmon7")), 
			trim(pg_result($Result,0,"obslmon8")), 
			trim(pg_result($Result,0,"obslmon9")), 
			trim(pg_result($Result,0,"obslmon10")), 
			trim(pg_result($Result,0,"obslmon11")), 
			trim(pg_result($Result,0,"obslmon12"))
			);												

		fprintf($fid_wp1, "%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f%6.1f\n", 
			trim(pg_result($Result,0,"rhmon1")), 
			trim(pg_result($Result,0,"rhmon2")), 
			trim(pg_result($Result,0,"rhmon3")), 
			trim(pg_result($Result,0,"rhmon4")), 
			trim(pg_result($Result,0,"rhmon5")), 
			trim(pg_result($Result,0,"rhmon6")), 
			trim(pg_result($Result,0,"rhmon7")), 
			trim(pg_result($Result,0,"rhmon8")), 
			trim(pg_result($Result,0,"rhmon9")), 
			trim(pg_result($Result,0,"rhmon10")), 
			trim(pg_result($Result,0,"rhmon11")), 
			trim(pg_result($Result,0,"rhmon12"))
			);										

		fprintf($fid_wp1, "%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f\n", 
			trim(pg_result($Result,0,"uav0mon1")), 
			trim(pg_result($Result,0,"uav0mon2")), 
			trim(pg_result($Result,0,"uav0mon3")), 
			trim(pg_result($Result,0,"uav0mon4")), 
			trim(pg_result($Result,0,"uav0mon5")), 
			trim(pg_result($Result,0,"uav0mon6")), 
			trim(pg_result($Result,0,"uav0mon7")), 
			trim(pg_result($Result,0,"uav0mon8")), 
			trim(pg_result($Result,0,"uav0mon9")), 
			trim(pg_result($Result,0,"uav0mon10")), 
			trim(pg_result($Result,0,"uav0mon11")), 
			trim(pg_result($Result,0,"uav0mon12"))
			);										

		fclose($fid_wp1);


		$fid_wnd = fopen($fnwnd, "w");
		fprintf($fid_wnd, "%14s%13s%s\n", trim(pg_result($Result,0,"stationname")), 
			trim($stnst), 
			pg_result($Result,0,"stationlocation")
			);
		fprintf($fid_wnd, "    LAT = %7.2f   LON =   %7.2f    ELEV = %7.2f     \n",
			trim(pg_result($Result,0,"latitude")), 
			trim(pg_result($Result,0,"longitude")), 
			trim(pg_result($Result,0,"elevation"))
			);						

		fprintf($fid_wnd, "%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f\n", 
			trim(pg_result($Result,0,"uav0mon1")), 
			trim(pg_result($Result,0,"uav0mon2")), 
			trim(pg_result($Result,0,"uav0mon3")), 
			trim(pg_result($Result,0,"uav0mon4")), 
			trim(pg_result($Result,0,"uav0mon5")), 
			trim(pg_result($Result,0,"uav0mon6")), 
			trim(pg_result($Result,0,"uav0mon7")), 
			trim(pg_result($Result,0,"uav0mon8")), 
			trim(pg_result($Result,0,"uav0mon9")), 
			trim(pg_result($Result,0,"uav0mon10")), 
			trim(pg_result($Result,0,"uav0mon11")), 
			trim(pg_result($Result,0,"uav0mon12"))
			);			

		fprintf($fid_wnd, "%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f\n", 
			trim(pg_result($Result,0,"d1northmon1")), 
			trim(pg_result($Result,0,"d1northmon2")), 
			trim(pg_result($Result,0,"d1northmon3")), 
			trim(pg_result($Result,0,"d1northmon4")), 
			trim(pg_result($Result,0,"d1northmon5")), 
			trim(pg_result($Result,0,"d1northmon6")), 
			trim(pg_result($Result,0,"d1northmon7")), 
			trim(pg_result($Result,0,"d1northmon8")), 
			trim(pg_result($Result,0,"d1northmon9")), 
			trim(pg_result($Result,0,"d1northmon10")), 
			trim(pg_result($Result,0,"d1northmon11")), 
			trim(pg_result($Result,0,"d1northmon12"))
			);			

		fprintf($fid_wnd, "%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f\n", 
			trim(pg_result($Result,0,"d2nnemon1")), 
			trim(pg_result($Result,0,"d2nnemon2")), 
			trim(pg_result($Result,0,"d2nnemon3")), 
			trim(pg_result($Result,0,"d2nnemon4")), 
			trim(pg_result($Result,0,"d2nnemon5")), 
			trim(pg_result($Result,0,"d2nnemon6")), 
			trim(pg_result($Result,0,"d2nnemon7")), 
			trim(pg_result($Result,0,"d2nnemon8")), 
			trim(pg_result($Result,0,"d2nnemon9")), 
			trim(pg_result($Result,0,"d2nnemon10")), 
			trim(pg_result($Result,0,"d2nnemon11")), 
			trim(pg_result($Result,0,"d2nnemon12"))
			);			

		fprintf($fid_wnd, "%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f\n", 
			trim(pg_result($Result,0,"d3nemon1")), 
			trim(pg_result($Result,0,"d3nemon2")), 
			trim(pg_result($Result,0,"d3nemon3")), 
			trim(pg_result($Result,0,"d3nemon4")), 
			trim(pg_result($Result,0,"d3nemon5")), 
			trim(pg_result($Result,0,"d3nemon6")), 
			trim(pg_result($Result,0,"d3nemon7")), 
			trim(pg_result($Result,0,"d3nemon8")), 
			trim(pg_result($Result,0,"d3nemon9")), 
			trim(pg_result($Result,0,"d3nemon10")), 
			trim(pg_result($Result,0,"d3nemon11")), 
			trim(pg_result($Result,0,"d3nemon12"))
			);			
		fprintf($fid_wnd, "%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f\n", 
			trim(pg_result($Result,0,"d4enemon1")), 
			trim(pg_result($Result,0,"d4enemon2")), 
			trim(pg_result($Result,0,"d4enemon3")), 
			trim(pg_result($Result,0,"d4enemon4")), 
			trim(pg_result($Result,0,"d4enemon5")), 
			trim(pg_result($Result,0,"d4enemon6")), 
			trim(pg_result($Result,0,"d4enemon7")), 
			trim(pg_result($Result,0,"d4enemon8")), 
			trim(pg_result($Result,0,"d4enemon9")), 
			trim(pg_result($Result,0,"d4enemon10")), 
			trim(pg_result($Result,0,"d4enemon11")), 
			trim(pg_result($Result,0,"d4enemon12"))
			);			
		fprintf($fid_wnd, "%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f\n", 
			trim(pg_result($Result,0,"d5eastmon1")), 
			trim(pg_result($Result,0,"d5eastmon2")), 
			trim(pg_result($Result,0,"d5eastmon3")), 
			trim(pg_result($Result,0,"d5eastmon4")), 
			trim(pg_result($Result,0,"d5eastmon5")), 
			trim(pg_result($Result,0,"d5eastmon6")), 
			trim(pg_result($Result,0,"d5eastmon7")), 
			trim(pg_result($Result,0,"d5eastmon8")), 
			trim(pg_result($Result,0,"d5eastmon9")), 
			trim(pg_result($Result,0,"d5eastmon10")), 
			trim(pg_result($Result,0,"d5eastmon11")), 
			trim(pg_result($Result,0,"d5eastmon12"))
			);			
		fprintf($fid_wnd, "%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f\n", 
			trim(pg_result($Result,0,"d6esemon1")), 
			trim(pg_result($Result,0,"d6esemon2")), 
			trim(pg_result($Result,0,"d6esemon3")), 
			trim(pg_result($Result,0,"d6esemon4")), 
			trim(pg_result($Result,0,"d6esemon5")), 
			trim(pg_result($Result,0,"d6esemon6")), 
			trim(pg_result($Result,0,"d6esemon7")), 
			trim(pg_result($Result,0,"d6esemon8")), 
			trim(pg_result($Result,0,"d6esemon9")), 
			trim(pg_result($Result,0,"d6esemon10")), 
			trim(pg_result($Result,0,"d6esemon11")), 
			trim(pg_result($Result,0,"d6esemon12"))
			);			
		fprintf($fid_wnd, "%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f\n", 
			trim(pg_result($Result,0,"d7semon1")), 
			trim(pg_result($Result,0,"d7semon2")), 
			trim(pg_result($Result,0,"d7semon3")), 
			trim(pg_result($Result,0,"d7semon4")), 
			trim(pg_result($Result,0,"d7semon5")), 
			trim(pg_result($Result,0,"d7semon6")), 
			trim(pg_result($Result,0,"d7semon7")), 
			trim(pg_result($Result,0,"d7semon8")), 
			trim(pg_result($Result,0,"d7semon9")), 
			trim(pg_result($Result,0,"d7semon10")), 
			trim(pg_result($Result,0,"d7semon11")), 
			trim(pg_result($Result,0,"d7semon12"))
			);			
		fprintf($fid_wnd, "%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f\n", 
			trim(pg_result($Result,0,"d8ssemon1")), 
			trim(pg_result($Result,0,"d8ssemon2")), 
			trim(pg_result($Result,0,"d8ssemon3")), 
			trim(pg_result($Result,0,"d8ssemon4")), 
			trim(pg_result($Result,0,"d8ssemon5")), 
			trim(pg_result($Result,0,"d8ssemon6")), 
			trim(pg_result($Result,0,"d8ssemon7")), 
			trim(pg_result($Result,0,"d8ssemon8")), 
			trim(pg_result($Result,0,"d8ssemon9")), 
			trim(pg_result($Result,0,"d8ssemon10")), 
			trim(pg_result($Result,0,"d8ssemon11")), 
			trim(pg_result($Result,0,"d8ssemon12"))
			);			
		fprintf($fid_wnd, "%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f\n", 
			trim(pg_result($Result,0,"d9southmon1")), 
			trim(pg_result($Result,0,"d9southmon2")), 
			trim(pg_result($Result,0,"d9southmon3")), 
			trim(pg_result($Result,0,"d9southmon4")), 
			trim(pg_result($Result,0,"d9southmon5")), 
			trim(pg_result($Result,0,"d9southmon6")), 
			trim(pg_result($Result,0,"d9southmon7")), 
			trim(pg_result($Result,0,"d9southmon8")), 
			trim(pg_result($Result,0,"d9southmon9")), 
			trim(pg_result($Result,0,"d9southmon10")), 
			trim(pg_result($Result,0,"d9southmon11")), 
			trim(pg_result($Result,0,"d9southmon12"))
			);			
		fprintf($fid_wnd, "%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f\n", 
			trim(pg_result($Result,0,"d10sswmon1")), 
			trim(pg_result($Result,0,"d10sswmon2")), 
			trim(pg_result($Result,0,"d10sswmon3")), 
			trim(pg_result($Result,0,"d10sswmon4")), 
			trim(pg_result($Result,0,"d10sswmon5")), 
			trim(pg_result($Result,0,"d10sswmon6")), 
			trim(pg_result($Result,0,"d10sswmon7")), 
			trim(pg_result($Result,0,"d10sswmon8")), 
			trim(pg_result($Result,0,"d10sswmon9")), 
			trim(pg_result($Result,0,"d10sswmon10")), 
			trim(pg_result($Result,0,"d10sswmon11")), 
			trim(pg_result($Result,0,"d10sswmon12"))
			);			
		fprintf($fid_wnd, "%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f\n", 
			trim(pg_result($Result,0,"d11swmon1")), 
			trim(pg_result($Result,0,"d11swmon2")), 
			trim(pg_result($Result,0,"d11swmon3")), 
			trim(pg_result($Result,0,"d11swmon4")), 
			trim(pg_result($Result,0,"d11swmon5")), 
			trim(pg_result($Result,0,"d11swmon6")), 
			trim(pg_result($Result,0,"d11swmon7")), 
			trim(pg_result($Result,0,"d11swmon8")), 
			trim(pg_result($Result,0,"d11swmon9")), 
			trim(pg_result($Result,0,"d11swmon10")), 
			trim(pg_result($Result,0,"d11swmon11")), 
			trim(pg_result($Result,0,"d11swmon12"))
			);			
		fprintf($fid_wnd, "%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f\n", 
			trim(pg_result($Result,0,"d12wswmon1")), 
			trim(pg_result($Result,0,"d12wswmon2")), 
			trim(pg_result($Result,0,"d12wswmon3")), 
			trim(pg_result($Result,0,"d12wswmon4")), 
			trim(pg_result($Result,0,"d12wswmon5")), 
			trim(pg_result($Result,0,"d12wswmon6")), 
			trim(pg_result($Result,0,"d12wswmon7")), 
			trim(pg_result($Result,0,"d12wswmon8")), 
			trim(pg_result($Result,0,"d12wswmon9")), 
			trim(pg_result($Result,0,"d12wswmon10")), 
			trim(pg_result($Result,0,"d12wswmon11")), 
			trim(pg_result($Result,0,"d12wswmon12"))
			);			
		fprintf($fid_wnd, "%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f\n", 
			trim(pg_result($Result,0,"d13westmon1")), 
			trim(pg_result($Result,0,"d13westmon2")), 
			trim(pg_result($Result,0,"d13westmon3")), 
			trim(pg_result($Result,0,"d13westmon4")), 
			trim(pg_result($Result,0,"d13westmon5")), 
			trim(pg_result($Result,0,"d13westmon6")), 
			trim(pg_result($Result,0,"d13westmon7")), 
			trim(pg_result($Result,0,"d13westmon8")), 
			trim(pg_result($Result,0,"d13westmon9")), 
			trim(pg_result($Result,0,"d13westmon10")), 
			trim(pg_result($Result,0,"d13westmon11")), 
			trim(pg_result($Result,0,"d13westmon12"))
			);			
		fprintf($fid_wnd, "%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f\n", 
			trim(pg_result($Result,0,"d14wnwmon1")), 
			trim(pg_result($Result,0,"d14wnwmon2")), 
			trim(pg_result($Result,0,"d14wnwmon3")), 
			trim(pg_result($Result,0,"d14wnwmon4")), 
			trim(pg_result($Result,0,"d14wnwmon5")), 
			trim(pg_result($Result,0,"d14wnwmon6")), 
			trim(pg_result($Result,0,"d14wnwmon7")), 
			trim(pg_result($Result,0,"d14wnwmon8")), 
			trim(pg_result($Result,0,"d14wnwmon9")), 
			trim(pg_result($Result,0,"d14wnwmon10")), 
			trim(pg_result($Result,0,"d14wnwmon11")), 
			trim(pg_result($Result,0,"d14wnwmon12"))
			);			
		fprintf($fid_wnd, "%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f\n", 
			trim(pg_result($Result,0,"d15nwmon1")), 
			trim(pg_result($Result,0,"d15nwmon2")), 
			trim(pg_result($Result,0,"d15nwmon3")), 
			trim(pg_result($Result,0,"d15nwmon4")), 
			trim(pg_result($Result,0,"d15nwmon5")), 
			trim(pg_result($Result,0,"d15nwmon6")), 
			trim(pg_result($Result,0,"d15nwmon7")), 
			trim(pg_result($Result,0,"d15nwmon8")), 
			trim(pg_result($Result,0,"d15nwmon9")), 
			trim(pg_result($Result,0,"d15nwmon10")), 
			trim(pg_result($Result,0,"d15nwmon11")), 
			trim(pg_result($Result,0,"d15nwmon12"))
			);			
		fprintf($fid_wnd, "%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f%6.2f\n", 
			trim(pg_result($Result,0,"d16nnwmon1")), 
			trim(pg_result($Result,0,"d16nnwmon2")), 
			trim(pg_result($Result,0,"d16nnwmon3")), 
			trim(pg_result($Result,0,"d16nnwmon4")), 
			trim(pg_result($Result,0,"d16nnwmon5")), 
			trim(pg_result($Result,0,"d16nnwmon6")), 
			trim(pg_result($Result,0,"d16nnwmon7")), 
			trim(pg_result($Result,0,"d16nnwmon8")), 
			trim(pg_result($Result,0,"d16nnwmon9")), 
			trim(pg_result($Result,0,"d16nnwmon10")), 
			trim(pg_result($Result,0,"d16nnwmon11")), 
			trim(pg_result($Result,0,"d16nnwmon12"))
			);	

		fclose($fid_wnd);

		pg_freeresult($Result);
    }




    public function writeRUNFILE($workingDir, $runscenario)
    {
        global $globwebdir;
        $cmd = escapeshellcmd("/usr/bin/python3 ". $globwebdir . "gisutils/writeRUN.py ". $workingDir . " ". $runscenario);
        addToLog($cmd);
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
            return false;
        }
        else{return TRUE;}
    }



    public function pywriteCONT($workingDir, $scefdname)
    {
        global $globwebdir;
        $cmd = escapeshellcmd("/usr/bin/python3 ". $globwebdir . "gisutils/writeCONT.py ". $workingDir . " " .  $scefdname);
        addToLog($cmd);
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
            return false;
        }
        else{return TRUE;}
    }


    public function writeCONTFILE($workingDir, $scefdname)
    {
        global $globwebdir;
        
        $srcctljson = $globwebdir . 'json/tmpcontfile.json';
        $destctljson = $workingDir . '/apexruns/' . $scefdname . '/runcont.json';
        if (!copy($srcctljson,$destctljson))
        {
            $error[]="<p>Error copying tmpcontfile.json to the apexrun folder <p>";
            return false;
        }
        $this->pywriteCONT($workingDir, $scefdname);
       
        return TRUE;
    }



    public function copyOTHERFILE($workingDir, $scerunfdname)
    {
        global $globwebdir;

        $file4copy = array(
            "apex1501"=>"apex1501",
            "APEXDIM" => "APEXDIM.DAT",
            "APEXFILE" => "APEXFILE.DAT",
            "CROPCOM" => "CROPCOM.DAT",
            "FERTCOM" => "FERTCOM.DAT",
            "HERD0806" => "HERD0806.DAT",
            "MLRN0806" => "MLRN0806.DAT",
            "PARM0806" => "PARM0806.DAT",
            "PESTCOM" => "PESTCOM.DAT",
            "PRNT0806" => "PRNT0806.DAT",
            "PSOCOM" => "PSOCOM.DAT",
            "RFDTLST" => "RFDTLST.DAT",
            "TILLCOM" => "TILLCOM.DAT",
            "TR55COM" => "TR55COM.DAT"
        );

        foreach($file4copy as $f4c)
        {
            $srcfn = $globwebdir . 'apexdata/'. $f4c;  
            $destfn = $workingDir . '/apexruns/'. $scerunfdname . '/' . $f4c;
            if(file_exists($destfn))
            {unlink($destfn);}
            
            if (!copy($srcfn,$destfn))
            {
                $error[]="<p>Error copying" . $f4c . "to the apexrun folder <p>";
                return false;
            }

        }

        // After copying, the permission of apex1501 need to be
        // changed to execute.
        chmod($workingDir . '/apexruns/'. $scerunfdname . '/'. $file4copy["apex1501"], 0755);
        return TRUE;
    }



    public function copyOPSFILE($workingDir, $scenariofdname)
    {
        global $globwebdir;
        $file4copy = array(
            "AGRR"=>"AGRR.OPC",
            "PAST"=>"PAST.OPC",
            "URBAN"=>"URBAN.OPC",
             "CORN"=>"CORN.OPC",
             "SHRUB"=>"SHRUB.OPC",
             "WHEAT"=>"WHEAT.OPC",
             "FALLOW"=>"FALLOW.OPC",
             "SOYB"=>"SOYB.OPC",
             "FOREST"=>"FOREST.OPC",
             "TREES"=>"TREES.OPC"
        );
        
        
        foreach($file4copy as $f4c)
        {
            $srcfn = $globwebdir . 'apexdata/OPSCDFT/'. $f4c;
            $destfn = $workingDir . '/apexruns/' . $scenariofdname . '/' . $f4c;
            if(file_exists($destfn))
            {unlink($destfn);}
            if (!copy($srcfn,$destfn))
            {
                $error[]="<p>Error copying" . $f4c . "to the apexrun folder <p>";
                return false;
            }

        }

        return TRUE;
    }

    public function writeOPSCOM($workingDir, $scenario)
    {
        global $globwebdir;
        $cmd = escapeshellcmd("/usr/bin/python3 ". $globwebdir . "gisutils/writeOPSCOM.py ". $workingDir. " ".$scenario);
        addToLog($cmd);
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
            return false;
        }
    
        return TRUE;
    }



    public function runAPEX1501($workingDir, $scerunfdname)
    {
        global $globwebdir;
        $runfd = $workingDir . '/apexruns/'. $scerunfdname;
        
        chdir($runfd); 
        $cmd = escapeshellcmd("./apex1501");
        addToLog($cmd);
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
            return false;
        }
        chdir($globwebdir);
        return TRUE;
    }


    public function getQSNPRECLevels($workingDir)
    {
        global $globwebdir;

        // First copy the json to the working folder
        $origjson = $globwebdir . 'json/susrsltclasses.json'; 
        $destjson = $workingDir . '/apexoutjsonmap/susrsltclasses.json';
        copy($origjson,$destjson);

        $cmd = escapeshellcmd("python3 ".$globwebdir."gisutils/getQSNPRECLevels.py " . $workingDir);
        addToLog($cmd);

        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            $error="<p>***Could not execute: " . $cmd . "***<p>";
            return $error;
        }
        
        return TRUE;
    }


    public function reclaQSNPMaps($workingDir)
    {
        global $globwebdir;

        $cmd = escapeshellcmd("python3 ".$globwebdir."gisutils/reclassQSNPmap.py " . $workingDir . " " . $globwebdir);
        addToLog($cmd);
        exec($cmd, $output, $rc);
        //var_dump($output);
        if ($rc !== 0) {
            $error="<p>***Could not execute: " . $cmd . "***<p>";
            return $error;
        }

        return TRUE;

    }




    public function installTile($workingDir, $tiledep, $scerunfdname)
    {
        global $globwebdir;

        $cmd = "python3 ".$globwebdir."gisutils/installTileHSGCD.py " . $workingDir . " " . $tiledep . " " . $scerunfdname;
        addToLog($cmd);
        exec($cmd, $output, $rc);
        //var_dump($output);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
            return;
        }

        return TRUE;
    }


    public function getWeightedQSNP($workingDir)
    {
        global $globwebdir;

        $cmd = "python3 ".$globwebdir."gisutils/fldweightedQSNP.py " . $workingDir;
        addToLog($cmd);
        exec($cmd, $output, $rc);
        //var_dump($output);
        if ($rc !== 0) {
            $error="<p>***Could not execute: " . $cmd . "***<p>";
            return $error;
        }

        return TRUE;
    }


    public function getQSNPArrayOlt($workingDir, $scenariolst)
    {
        // This function read in the json file of outputs and 
        // return it to an array for display
        $qsnpallscen = array();
         
        $fnaaqsnp = $workingDir . '/apexruns/susresults.json';
        $qsnpallscen = json_decode(file_get_contents($fnaaqsnp), true);
        
        return $qsnpallscen;
    }


    public function getQSNPArrayFld($workingDir)
    {
        // This function read in the json file of outputs and
        // return it to an array for display
        $qsnpallscen = array();

        $fnaaqsnp = $workingDir . '/apexruns/fldwtedqsnp.json';
        $qsnpallscen = json_decode(file_get_contents($fnaaqsnp), true);;

        return $qsnpallscen;
    }

    public function createLgdFig($workingDir)
    {
        global $globwebdir;

        $src = $globwebdir . 'apexdata/legend.tif';
        $des = $workingDir . '/apexoutjsonmap/legend.tif';
        copy($src, $des);


        $cmd = escapeshellcmd("/usr/bin/python3 ".$globwebdir."gisutils/createLegendFig.py " . $workingDir);
        addToLog($cmd);
        system($cmd);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
        }

        return TRUE;
    }


    public function makeScenarioOPCjson($workingDir, $scenariofdname)
    {
        global $globwebdir;

        $cmd = escapeshellcmd("/usr/bin/python3 ".$globwebdir."gisutils/makeSceOPSJSON.py " . $workingDir . " " . $scenariofdname);
        addToLog($cmd);
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
            return;
        }
        
        else{return TRUE;}
    }



    public function saveJSON2File($workingDir, $jsonarr)
    {
        $outfn = $workingDir . "/apexruns/allscenario.json";
        if(file_exists($outfn))
            {unlink($outfn);}

        $jsonstr = json_encode($jsonarr, true, JSON_UNESCAPED_UNICODE);
        if (json_decode($jsonstr) != NULL)
        {
            $file = fopen($outfn, 'w');
            fwrite($file, $jsonstr);
            fclose($file);
            return TRUE;
        }
        else{
            return "Json was not saved correctly!";
        }
    }





}


?>
