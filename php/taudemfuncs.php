<?php
//
// taudemfuncs.php
// 
// collection of functions to run taudem commands for watershed delineation
// 
//

class taudemfuncs
{

    public function runPitRemove($inDem,$outfelDem)
    {
        // mpiexec -n <number of processes> PitRemove -z <demfile> -fel < felfile> [ -4way ] [ -depmask depmaskfile]        
        global $globwebdir;
        $cmd = $globwebdir. "gisutils/taudembin/pitremove -z " . $inDem . " -fel " . $outfelDem;

        addToLog("<br>" . $cmd . "<br>");
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
            return;
        }
    }


    //
    // runD8FlowDir()
    //
    public function runD8FlowDir($infelDem,$outpFlowDir,$outsd8Slope)
    {
        global $globwebdir;
        $cmd = $globwebdir. "gisutils/taudembin/d8flowdir -fel " . $infelDem . " -p " . $outpFlowDir . " -sd8 " . $outsd8Slope;

        addToLog("<br>" . $cmd . "<br>");
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
            return;
        }
    }


    //
    // runAreaD8()
    //
    public function runAreaD8($inpFlowDir,$outad8ContriArea)
    {

        // Command line to run
        // aread8 -p < pfile > -ad8 <ad8file> [ -o <outletfile>] [ -wg < wgfile >] [ -nc ] [ -lyrname < layer name >] [ -lyrno < layer number >]
        global $globwebdir;
        $cmd = $globwebdir. "gisutils/taudembin/aread8 -p " . $inpFlowDir . " -ad8 " . $outad8ContriArea . ' -nc ';

        addToLog("<br>" . $cmd . "<br>");
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
            return;
        }
    }


    //
    // runTauDEMThreshold()
    //
    public function runThreshold($inad8ContriArea, $outsrcStreamNet, $usrthreshold, $demcellsize)
    {
        // Command line to run
        // Threshold -ssa <ssafile> -src <srcfile> -thresh 100.0 [ -mask maskfile]
        global $globwebdir;
        $thresholdval = $usrthreshold * 10000.0 / ($demcellsize*$demcellsize);

        $cmd = $globwebdir. "gisutils/taudembin/threshold -ssa " . $inad8ContriArea . " -src " . $outsrcStreamNet . " -thresh ". $thresholdval;

        addToLog("<br>" . $cmd . "<br>");
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
            return;
        }
    }


    public function runMoveOutletsToStreams($inp,$insrc, $inoutlet, $outmvoutlet)
    {
        global $globwebdir;
        // MoveOutletsToStreams -p <pfile> -src <srcfile> -o <outletfile> [ -lyrname <layer name>] [ -lyrno <layer number>] -om <movedoutletsfile> [ -omlyr omlayername] [ -md maxdist]
        $cmd = $globwebdir. "gisutils/taudembin/moveoutletstostrm -p " . $inp . " -src " . $insrc . " -o " . $inoutlet . " -om " . $outmvoutlet . " -md 100";
        addToLog("<br>" . $cmd . "<br>");
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
            return;
        }

}


    //
    // runAreaD8OLT()
    //
    public function runAreaD8OLT($inpFlowDir, $inOutlet, $outad8ContriArea)
    {
        // Command line to run
        // aread8 -p < pfile > -ad8 <ad8file> [ -o <outletfile>] [ -wg < wgfile >] [ -nc ] [ -lyrname < layer name >] [ -lyrno < layer number >]
        global $globwebdir;
        $cmd = $globwebdir. "gisutils/taudembin/aread8 -p " . $inpFlowDir . " -o " . $inOutlet . " -ad8 " . $outad8ContriArea . ' -nc';

        addToLog($cmd);
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
            return;
        }
    }   


    //
    // runGridNetOLT()
    //
    public function runGridNetOLT($inp,$inOutlet,$outplen, $outtlen, $outgord)
    {
        // Command line to run
        // Gridnet -p<pfile> -plen <plenfile> -tlen <tlenfile> -gord <gordfile> [-o <outletfile>] [-lyrname <layer name>] [-lyrno <layer number>] [-mask <maskfile> [-thresh <threshold>]]
        global $globwebdir;
$cmd = $globwebdir. "gisutils/taudembin/gridnet -p " . $inp . " -plen " . $outplen . " -tlen ". $outtlen . " -gord " . $outgord . " -o " . $inOutlet;
        addToLog($cmd);
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
            return;
        }
    }



    //
    // runStreamNetOLT()
    //
    public function runStreamNetOLT($infel, $inp, $inad8, $insrc,$inoutlet, $outord, $outtree, $outcoord, $outstream, $outw)
    {
        // StreamNet -fel <felfile> -p <pfile> -ad8 <ad8file> -src <srcfile> -ord <ordfile> -tree <treefile> -coord<coordfile> -net <netfile> [ -netlyr netlayername] [ -o <outletfile>] [ -lyrname <layer name>] [ -lyrno <layer number>] -w <wfile> [ -sw]
        global $globwebdir;
        $cmd = $globwebdir. "gisutils/taudembin/streamnet -fel " . $infel . " -p " . $inp . " -ad8 ". $inad8 . " -src " . $insrc . " -ord " . $outord . " -tree " . $outtree .  " -coord " . $outcoord . " -net " . $outstream . " -o " . $inoutlet . " -w ". $outw;

        addToLog($cmd);
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
            return;
        }
    }


    //
    // runGridNetFLD()
    //
    public function runGridNetFLD($inp,$outplen, $outtlen, $outgord)
    {

        // Command line to run
        // Gridnet -p<pfile> -plen <plenfile> -tlen <tlenfile> -gord <gordfile> [-o <outletfile>] [-lyrname <layer name>] [-lyrno <layer number>] [-mask <maskfile> [-thresh <threshold>]]
        global $globwebdir;
        $cmd = $globwebdir. "gisutils/taudembin/gridnet -p " . $inp . " -plen " . $outplen . " -tlen ". $outtlen . " -gord " . $outgord;
        addToLog($cmd."<br>");
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
            return;
        }

    }


    //
    // runStreamNetFLD()
    //
    public function runStreamNetFLD($infel, $inp, $inad8, $insrc, $outord, $outtree, $outcoord, $outstream, $outw)
    {
        // StreamNet -fel <felfile> -p <pfile> -ad8 <ad8file> -src <srcfile> -ord <ordfile> -tree <treefile> -coord<coordfile> -net <netfile> [ -netlyr netlayername] [ -o <outletfile>] [ -lyrname <layer name>] [ -lyrno <layer number>] -w <wfile> [ -sw]
        global $globwebdir;
        $cmd = $globwebdir. "gisutils/taudembin/streamnet -fel " . $infel . " -p " . $inp . " -ad8 ". $inad8 . " -src " . $insrc . " -ord " . $outord . " -tree " . $outtree .  " -coord " . $outcoord . " -net " . $outstream . " -w ". $outw;

        addToLog('<br><br>'.$cmd.'<br><br>');
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            $error[]="<p>***Could not execute: " . $cmd . "***<p>";
            return;
        }
    }







    public function getStreamNetwork($workingDir, $critarea, $demcellsize)
    {
        $utmSliceFile = $workingDir . "/gislayers/utmSlice.tif";
        $demfelFile = $workingDir . "/taudemlayers/demfel.tif";

        // Run pitremove
        if (file_exists($demfelFile)) {unlink($demfelFile);}
        $this->runPitRemove($utmSliceFile, $demfelFile); 
        
        $pFlowDirFile =  $workingDir . "/taudemlayers/pFlowDir.tif";
        $sd8SlopeFile =  $workingDir . "/taudemlayers/sd8Slope.tif";
        $ad8ContAreaFile =  $workingDir . "/taudemlayers/ad8ContArea.tif";
        $srcStrNetFile =  $workingDir . "/taudemlayers/srcStrNet.tif";
        if (file_exists($pFlowDirFile)) {unlink($pFlowDirFile);}
        if (file_exists($sd8SlopeFile)) {unlink($sd8SlopeFile);}
        if (file_exists($ad8ContAreaFile)) {unlink($ad8ContAreaFile);}
        if (file_exists($srcStrNetFile)) {unlink($srcStrNetFile);}

        $this->runD8FlowDir($demfelFile,$pFlowDirFile, $sd8SlopeFile);
        $this->runAreaD8($pFlowDirFile,$ad8ContAreaFile);
        $this->runThreshold($ad8ContAreaFile, $srcStrNetFile, $critarea, $demcellsize);

        return TRUE;
    }

    
    public function getWSforExtent($workingDir,$zone)
    {
        // The difference of this function and the with outlet
        // one was that, user started with a field boundary.
        // The steps include:
        // 1. Get the extent of the field boundary.
        // 2. Update the session variable extent to include enough
        // buffer, that the whole field can be covered by subareas.
        // 3. Then, extract DEM
        // 4. Reproject dem
        // 5. Generate stream network
        // 6. Generate watersheds and subareas
        // 7. Determine subareas covered by the field boundary.
        // 8. Determine the subareas and watershed relationships
        // 9. Update map files to display all watersheds
        addToLog("<br><br>Running TauDEM: gridnet Without outlet <br><br>");
        // pFlowDir was generated in former steps.
        $pFlowDir =  $workingDir . "/taudemlayers/pFlowDir.tif";

        $plen =  $workingDir . "/taudemlayers/plen.tif";
        $tlen =  $workingDir . "/taudemlayers/tlen.tif";
        $gord =  $workingDir . "/taudemlayers/gord.tif";
    
        if (file_exists($plen)) {unlink($plen);}
        if (file_exists($tlen)) {unlink($tlen);}
        if (file_exists($gord)) {unlink($gord);}

        $this->runGridNetFLD($pFlowDir,$plen, $tlen, $gord);

        addToLog("Running TauDEM: StreamNet without outlet");
        $ad8ContArea =  $workingDir . "/taudemlayers/ad8ContArea.tif";
        $srcStrNet =  $workingDir . "/taudemlayers/srcStrNet.tif";

        $demfel = $workingDir . "/taudemlayers/demfel.tif";
        $ord =  $workingDir . "/taudemlayers/ord.tif";
        $tree =  $workingDir . "/taudemlayers/tree.txt";
        $coord =  $workingDir . "/taudemlayers/coord.tif";
        $strmshp =  $workingDir . "/taudemlayers/stream.shp";
        $demw =  $workingDir . "/taudemlayers/demw.tif";

        if (file_exists($ord)) {unlink($ord);}
        if (file_exists($tree)) {unlink($tree);}
        if (file_exists($coord)) {unlink($coord);}
        if (file_exists($strmshp)) {unlink($strmshp);}
        if (file_exists($demw)) {unlink($demw);}

        $this->runStreamNetFLD($demfel, $pFlowDir, $ad8ContArea, $srcStrNet, $ord, $tree, $coord, $strmshp, $demw);

        return True;
    }





    public function getWSfromOutlet($workingDir)
    { 
        // Step 1: move outlet to streams
        addToLog("Running TauDEM: MoveOutletstoStreams");

        $fnoltjson = $workingDir.'/taudemlayers/outletpnt.json';
        $pFlowDir =  $workingDir . "/taudemlayers/pFlowDir.tif";
        $fnmvolt = $workingDir.'/taudemlayers/mvoutlet.json';
        $srcStrNet =  $workingDir . "/taudemlayers/srcStrNet.tif";
        if (file_exists($fnmvolt))
                {unlink($fnmvolt);}

        $this->runMoveOutletsToStreams($pFlowDir,$srcStrNet, $fnoltjson, $fnmvolt);

        addToLog("Finished running TauDEM: MoveOutletstoStreams");
    
        // Step 2: run aread8 with outlet
        addToLog("Running TauDEM: AREAD8 With outlet");
        $ad8ContArea =  $workingDir . '/taudemlayers/ad8ContAreaOlt.tif';
        if (file_exists($ad8ContArea))
        {unlink($ad8ContArea);}
        $this->runAreaD8OLT($pFlowDir, $fnmvolt, $ad8ContArea);
        addToLog("Finished Running TauDEM: AREAD8 With outlet");

        // Step 3: Running grid net with outlet
        addToLog("Running TauDEM: gridnet With outlet");
        $plen =  $workingDir . "/taudemlayers/plen.tif";
        $tlen =  $workingDir . "/taudemlayers/tlen.tif";
        $gord =  $workingDir . "/taudemlayers/gord.tif";

        if (file_exists($plen)) {unlink($plen);}
        if (file_exists($tlen)) {unlink($tlen);}
        if (file_exists($gord)) {unlink($gord);}

        $this-> runGridNetOLT($pFlowDir,$fnmvolt,$plen, $tlen, $gord);
        addToLog("Finished Running TauDEM: gridnet With outlet");


        // Step 4: Running Threshold again
        addToLog("Running TauDEM: Threshold");
        $srcStrNetOlt =  $workingDir . "/taudemlayers/srcStrNetOlt.tif";
        if (file_exists($srcStrNetOlt)) {unlink($srcStrNetOlt);}
        $this->runThreshold($ad8ContArea, $srcStrNetOlt, $_SESSION["SSVAR"]["criticalarea"], $_SESSION["SSVAR"]["demcellsize"]);

        addToLog("Finished Running TauDEM: Threshold");


        // Step 5: Running streamnet with outlet 
        addToLog("Running TauDEM: StreamNet with outlet");
        $demfel = $workingDir . "/taudemlayers/demfel.tif";
        $ord =  $workingDir . "/taudemlayers/ord.tif";
        $tree =  $workingDir . "/taudemlayers/tree.txt";
        $coord =  $workingDir . "/taudemlayers/coord.tif";
        $strmshp =  $workingDir . "/taudemlayers/stream.shp";
        $demw =  $workingDir . "/taudemlayers/demw.tif";

        if (file_exists($ord)) {unlink($ord);}
        if (file_exists($tree)) {unlink($tree);}
        if (file_exists($coord)) {unlink($coord);}
        if (file_exists($strmshp)) {unlink($strmshp);}
        if (file_exists($demw)) {unlink($demw);}

        $this->runStreamNetOLT($demfel, $pFlowDir, $ad8ContArea, $srcStrNetOlt,$fnmvolt, $ord, $tree, $coord, $strmshp, $demw);

        addToLog("Finished Running TauDEM: StreamNet with outlet");

        return TRUE;
    }






























}


?>
