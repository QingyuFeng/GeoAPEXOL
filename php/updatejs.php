<?php

class updatejs 
{


    public function UpdateSOLJSON($Connection,
        $solmk,
        $jsonupdate_sol)
    {
        $jsonupdate_sol[$solmk]["solmukey"]= $solmk;
        $sqlstmt = "SELECT * from ssurgo2apex where mukey = '" .$solmk. "' order by mukey"; 
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
        for ($Row=0; $Row < pg_numrows($Result); $Row++) {          
        $jsonupdate_sol[$solmk]["line1"]["soilname"]= pg_result($Result,$Row,"muname");
        $jsonupdate_sol[$solmk]["line2"]["abledo_salb"]= pg_result($Result,$Row,"albedodry_r");
        $jsonupdate_sol[$solmk]["line2"]["hydrologicgroup_hsg"]= pg_result($Result,$Row,"hydgrpdcd");
        $jsonupdate_sol[$solmk]["line2"]["initialwatercontent_ffc"]= "0.00";
        $jsonupdate_sol[$solmk]["line2"]["minwatertabledep_wtmn"]= pg_result($Result,$Row,"wtdepannmin");
        $jsonupdate_sol[$solmk]["line2"]["maxwatertabledep_wtmx"]= "0.00";
        $jsonupdate_sol[$solmk]["line2"]["initialwatertable_wtbl"]= "0.00";
        $jsonupdate_sol[$solmk]["line2"]["groundwaterstorage_gwst"]= "0.00";
        $jsonupdate_sol[$solmk]["line2"]["max_groundwater_gwmx"]= "0.00";
        $jsonupdate_sol[$solmk]["line2"]["gw_residenttime_rftt"]= "0.00";
        $jsonupdate_sol[$solmk]["line2"]["return_overtotalflow_rfpk"] = "0.00";

        $jsonupdate_sol[$solmk]["line3"]["min_layerdepth_tsla"]= "10.00";
        $jsonupdate_sol[$solmk]["line3"]["weatheringcode_xids"]= "0.00";
        $jsonupdate_sol[$solmk]["line3"]["cultivationyears_rtn1"]= "50.00";
        $jsonupdate_sol[$solmk]["line3"]["grouping_xidk"]= "2.00";
        $jsonupdate_sol[$solmk]["line3"]["min_maxlayerthick_zqt"]= "0.01";
        $jsonupdate_sol[$solmk]["line3"]["minprofilethick_zf"]= "0.05";
        $jsonupdate_sol[$solmk]["line3"]["minlayerthick_ztk"]= "0.05";
        $jsonupdate_sol[$solmk]["line3"]["org_c_biomass_fbm"]= "0.03";
        $jsonupdate_sol[$solmk]["line3"]["org_c_passive_fhp"]= "0.30";

        $jsonupdate_sol[$solmk]["line4_layerdepth"]["z1"]= pg_result($Result,$Row,"l1_layerdepth");
        $jsonupdate_sol[$solmk]["line4_layerdepth"]["z2"]= pg_result($Result,$Row,"l2_layerdepth");
        $jsonupdate_sol[$solmk]["line4_layerdepth"]["z3"]= pg_result($Result,$Row,"l3_layerdepth");
        $jsonupdate_sol[$solmk]["line4_layerdepth"]["z4"]= pg_result($Result,$Row,"l4_layerdepth");
        $jsonupdate_sol[$solmk]["line4_layerdepth"]["z5"]= pg_result($Result,$Row,"l5_layerdepth");
        $jsonupdate_sol[$solmk]["line4_layerdepth"]["z6"]= pg_result($Result,$Row,"l6_layerdepth");
        $jsonupdate_sol[$solmk]["line4_layerdepth"]["z7"]= pg_result($Result,$Row,"l7_layerdepth");
        $jsonupdate_sol[$solmk]["line4_layerdepth"]["z8"]= pg_result($Result,$Row,"l8_layerdepth");
        $jsonupdate_sol[$solmk]["line4_layerdepth"]["z9"]= pg_result($Result,$Row,"l9_layerdepth");
        $jsonupdate_sol[$solmk]["line4_layerdepth"]["z10"]= pg_result($Result,$Row,"l10_layerdepth");

        $jsonupdate_sol[$solmk]["line5_moistbulkdensity"]["z1"]= pg_result($Result,$Row,"l1_bulkdensity");
        $jsonupdate_sol[$solmk]["line5_moistbulkdensity"]["z2"]= pg_result($Result,$Row,"l2_bulkdensity");
        $jsonupdate_sol[$solmk]["line5_moistbulkdensity"]["z3"]= pg_result($Result,$Row,"l3_bulkdensity");
        $jsonupdate_sol[$solmk]["line5_moistbulkdensity"]["z4"]= pg_result($Result,$Row,"l4_bulkdensity");
        $jsonupdate_sol[$solmk]["line5_moistbulkdensity"]["z5"]= pg_result($Result,$Row,"l5_bulkdensity");
        $jsonupdate_sol[$solmk]["line5_moistbulkdensity"]["z6"]= pg_result($Result,$Row,"l6_bulkdensity");
        $jsonupdate_sol[$solmk]["line5_moistbulkdensity"]["z7"]= pg_result($Result,$Row,"l7_bulkdensity");
        $jsonupdate_sol[$solmk]["line5_moistbulkdensity"]["z8"]= pg_result($Result,$Row,"l8_bulkdensity");
        $jsonupdate_sol[$solmk]["line5_moistbulkdensity"]["z9"]= pg_result($Result,$Row,"l9_bulkdensity");
        $jsonupdate_sol[$solmk]["line5_moistbulkdensity"]["z10"]= pg_result($Result,$Row,"l10_bulkdensity");

        $jsonupdate_sol[$solmk]["line6_wiltingpoint"]["z1"]= pg_result($Result,$Row,"l1_wiltingpoint");
        $jsonupdate_sol[$solmk]["line6_wiltingpoint"]["z2"]= pg_result($Result,$Row,"l2_wiltingpoint");
        $jsonupdate_sol[$solmk]["line6_wiltingpoint"]["z3"]= pg_result($Result,$Row,"l3_wiltingpoint");
        $jsonupdate_sol[$solmk]["line6_wiltingpoint"]["z4"]= pg_result($Result,$Row,"l4_wiltingpoint");
        $jsonupdate_sol[$solmk]["line6_wiltingpoint"]["z5"]= pg_result($Result,$Row,"l5_wiltingpoint");
        $jsonupdate_sol[$solmk]["line6_wiltingpoint"]["z6"]= pg_result($Result,$Row,"l6_wiltingpoint");
        $jsonupdate_sol[$solmk]["line6_wiltingpoint"]["z7"]= pg_result($Result,$Row,"l7_wiltingpoint");
        $jsonupdate_sol[$solmk]["line6_wiltingpoint"]["z8"]= pg_result($Result,$Row,"l8_wiltingpoint");
        $jsonupdate_sol[$solmk]["line6_wiltingpoint"]["z9"]= pg_result($Result,$Row,"l9_wiltingpoint");
        $jsonupdate_sol[$solmk]["line6_wiltingpoint"]["z10"]= pg_result($Result,$Row,"l10_wiltingpoint");

        $jsonupdate_sol[$solmk]["line7_fieldcapacity"]["z1"]= pg_result($Result,$Row,"l1_fieldcapacity");
        $jsonupdate_sol[$solmk]["line7_fieldcapacity"]["z2"]= pg_result($Result,$Row,"l2_fieldcapacity");
        $jsonupdate_sol[$solmk]["line7_fieldcapacity"]["z3"]= pg_result($Result,$Row,"l3_fieldcapacity");
        $jsonupdate_sol[$solmk]["line7_fieldcapacity"]["z4"]= pg_result($Result,$Row,"l4_fieldcapacity");
        $jsonupdate_sol[$solmk]["line7_fieldcapacity"]["z5"]= pg_result($Result,$Row,"l5_fieldcapacity");
        $jsonupdate_sol[$solmk]["line7_fieldcapacity"]["z6"]= pg_result($Result,$Row,"l6_fieldcapacity");
        $jsonupdate_sol[$solmk]["line7_fieldcapacity"]["z7"]= pg_result($Result,$Row,"l7_fieldcapacity");
        $jsonupdate_sol[$solmk]["line7_fieldcapacity"]["z8"]= pg_result($Result,$Row,"l8_fieldcapacity");
        $jsonupdate_sol[$solmk]["line7_fieldcapacity"]["z9"]= pg_result($Result,$Row,"l9_fieldcapacity");
        $jsonupdate_sol[$solmk]["line7_fieldcapacity"]["z10"]= pg_result($Result,$Row,"l10_fieldcapacity");

        $jsonupdate_sol[$solmk]["line8_sand"]["z1"]= pg_result($Result,$Row,"l1_sandtotal");
        $jsonupdate_sol[$solmk]["line8_sand"]["z2"]= pg_result($Result,$Row,"l2_sandtotal");
        $jsonupdate_sol[$solmk]["line8_sand"]["z3"]= pg_result($Result,$Row,"l3_sandtotal");
        $jsonupdate_sol[$solmk]["line8_sand"]["z4"]= pg_result($Result,$Row,"l4_sandtotal");
        $jsonupdate_sol[$solmk]["line8_sand"]["z5"]= pg_result($Result,$Row,"l5_sandtotal");
        $jsonupdate_sol[$solmk]["line8_sand"]["z6"]= pg_result($Result,$Row,"l6_sandtotal");
        $jsonupdate_sol[$solmk]["line8_sand"]["z7"]= pg_result($Result,$Row,"l7_sandtotal");
        $jsonupdate_sol[$solmk]["line8_sand"]["z8"]= pg_result($Result,$Row,"l8_sandtotal");
        $jsonupdate_sol[$solmk]["line8_sand"]["z9"]= pg_result($Result,$Row,"l9_sandtotal");
        $jsonupdate_sol[$solmk]["line8_sand"]["z10"]= pg_result($Result,$Row,"l10_sandtotal");

        $jsonupdate_sol[$solmk]["line9_silt"]["z1"]= pg_result($Result,$Row,"l1_silttotal");
        $jsonupdate_sol[$solmk]["line9_silt"]["z2"]= pg_result($Result,$Row,"l2_silttotal");
        $jsonupdate_sol[$solmk]["line9_silt"]["z3"]= pg_result($Result,$Row,"l3_silttotal");
        $jsonupdate_sol[$solmk]["line9_silt"]["z4"]= pg_result($Result,$Row,"l4_silttotal");
        $jsonupdate_sol[$solmk]["line9_silt"]["z5"]= pg_result($Result,$Row,"l5_silttotal");
        $jsonupdate_sol[$solmk]["line9_silt"]["z6"]= pg_result($Result,$Row,"l6_silttotal");
        $jsonupdate_sol[$solmk]["line9_silt"]["z7"]= pg_result($Result,$Row,"l7_silttotal");
        $jsonupdate_sol[$solmk]["line9_silt"]["z8"]= pg_result($Result,$Row,"l8_silttotal");
        $jsonupdate_sol[$solmk]["line9_silt"]["z9"]= pg_result($Result,$Row,"l9_silttotal");
        $jsonupdate_sol[$solmk]["line9_silt"]["z10"]= pg_result($Result,$Row,"l10_silttotal");


        $jsonupdate_sol[$solmk]["line11_ph"]["z1"]= pg_result($Result,$Row,"l1_ph");
        $jsonupdate_sol[$solmk]["line11_ph"]["z2"]= pg_result($Result,$Row,"l2_ph");
        $jsonupdate_sol[$solmk]["line11_ph"]["z3"]= pg_result($Result,$Row,"l3_ph");
        $jsonupdate_sol[$solmk]["line11_ph"]["z4"]= pg_result($Result,$Row,"l4_ph");
        $jsonupdate_sol[$solmk]["line11_ph"]["z5"]= pg_result($Result,$Row,"l5_ph");
        $jsonupdate_sol[$solmk]["line11_ph"]["z6"]= pg_result($Result,$Row,"l6_ph");
        $jsonupdate_sol[$solmk]["line11_ph"]["z7"]= pg_result($Result,$Row,"l7_ph");
        $jsonupdate_sol[$solmk]["line11_ph"]["z8"]= pg_result($Result,$Row,"l8_ph");
        $jsonupdate_sol[$solmk]["line11_ph"]["z9"]= pg_result($Result,$Row,"l9_ph");
        $jsonupdate_sol[$solmk]["line11_ph"]["z10"]= pg_result($Result,$Row,"l10_ph");

        $jsonupdate_sol[$solmk]["line12_sumofbase_smb"]["z1"]= pg_result($Result,$Row,"l1_sumofbases");
        $jsonupdate_sol[$solmk]["line12_sumofbase_smb"]["z2"]= pg_result($Result,$Row,"l2_sumofbases");
        $jsonupdate_sol[$solmk]["line12_sumofbase_smb"]["z3"]= pg_result($Result,$Row,"l3_sumofbases");
        $jsonupdate_sol[$solmk]["line12_sumofbase_smb"]["z4"]= pg_result($Result,$Row,"l4_sumofbases");
        $jsonupdate_sol[$solmk]["line12_sumofbase_smb"]["z5"]= pg_result($Result,$Row,"l5_sumofbases");
        $jsonupdate_sol[$solmk]["line12_sumofbase_smb"]["z6"]= pg_result($Result,$Row,"l6_sumofbases");
        $jsonupdate_sol[$solmk]["line12_sumofbase_smb"]["z7"]= pg_result($Result,$Row,"l7_sumofbases");
        $jsonupdate_sol[$solmk]["line12_sumofbase_smb"]["z8"]= pg_result($Result,$Row,"l8_sumofbases");
        $jsonupdate_sol[$solmk]["line12_sumofbase_smb"]["z9"]= pg_result($Result,$Row,"l9_sumofbases");
        $jsonupdate_sol[$solmk]["line12_sumofbase_smb"]["z10"]= pg_result($Result,$Row,"l10_sumofbases");

        $jsonupdate_sol[$solmk]["line13_orgc_conc_woc"]["z1"]= pg_result($Result,$Row,"l1_organicmatter");
        $jsonupdate_sol[$solmk]["line13_orgc_conc_woc"]["z2"]= pg_result($Result,$Row,"l2_organicmatter");
        $jsonupdate_sol[$solmk]["line13_orgc_conc_woc"]["z3"]= pg_result($Result,$Row,"l3_organicmatter");
        $jsonupdate_sol[$solmk]["line13_orgc_conc_woc"]["z4"]= pg_result($Result,$Row,"l4_organicmatter");
        $jsonupdate_sol[$solmk]["line13_orgc_conc_woc"]["z5"]= pg_result($Result,$Row,"l5_organicmatter");
        $jsonupdate_sol[$solmk]["line13_orgc_conc_woc"]["z6"]= pg_result($Result,$Row,"l6_organicmatter");
        $jsonupdate_sol[$solmk]["line13_orgc_conc_woc"]["z7"]= pg_result($Result,$Row,"l7_organicmatter");
        $jsonupdate_sol[$solmk]["line13_orgc_conc_woc"]["z8"]= pg_result($Result,$Row,"l8_organicmatter");
        $jsonupdate_sol[$solmk]["line13_orgc_conc_woc"]["z9"]= pg_result($Result,$Row,"l9_organicmatter");
        $jsonupdate_sol[$solmk]["line13_orgc_conc_woc"]["z10"]= pg_result($Result,$Row,"l10_organicmatter");

        $jsonupdate_sol[$solmk]["line14_caco3_cac"]["z1"]= pg_result($Result,$Row,"l1_caco3");
        $jsonupdate_sol[$solmk]["line14_caco3_cac"]["z2"]= pg_result($Result,$Row,"l2_caco3");
        $jsonupdate_sol[$solmk]["line14_caco3_cac"]["z3"]= pg_result($Result,$Row,"l3_caco3");
        $jsonupdate_sol[$solmk]["line14_caco3_cac"]["z4"]= pg_result($Result,$Row,"l4_caco3");
        $jsonupdate_sol[$solmk]["line14_caco3_cac"]["z5"]= pg_result($Result,$Row,"l5_caco3");
        $jsonupdate_sol[$solmk]["line14_caco3_cac"]["z6"]= pg_result($Result,$Row,"l6_caco3");
        $jsonupdate_sol[$solmk]["line14_caco3_cac"]["z7"]= pg_result($Result,$Row,"l7_caco3");
        $jsonupdate_sol[$solmk]["line14_caco3_cac"]["z8"]= pg_result($Result,$Row,"l8_caco3");
        $jsonupdate_sol[$solmk]["line14_caco3_cac"]["z9"]= pg_result($Result,$Row,"l9_caco3");
        $jsonupdate_sol[$solmk]["line14_caco3_cac"]["z10"]= pg_result($Result,$Row,"l10_caco3");

        $jsonupdate_sol[$solmk]["line15_cec"]["z1"]= pg_result($Result,$Row,"l1_cec");
        $jsonupdate_sol[$solmk]["line15_cec"]["z2"]= pg_result($Result,$Row,"l2_cec");
        $jsonupdate_sol[$solmk]["line15_cec"]["z3"]= pg_result($Result,$Row,"l3_cec");
        $jsonupdate_sol[$solmk]["line15_cec"]["z4"]= pg_result($Result,$Row,"l4_cec");
        $jsonupdate_sol[$solmk]["line15_cec"]["z5"]= pg_result($Result,$Row,"l5_cec");
        $jsonupdate_sol[$solmk]["line15_cec"]["z6"]= pg_result($Result,$Row,"l6_cec");
        $jsonupdate_sol[$solmk]["line15_cec"]["z7"]= pg_result($Result,$Row,"l7_cec");
        $jsonupdate_sol[$solmk]["line15_cec"]["z8"]= pg_result($Result,$Row,"l8_cec");
        $jsonupdate_sol[$solmk]["line15_cec"]["z9"]= pg_result($Result,$Row,"l9_cec");
        $jsonupdate_sol[$solmk]["line15_cec"]["z10"]= pg_result($Result,$Row,"l10_cec");

        $jsonupdate_sol[$solmk]["line16_rock_rok"]["z1"]= pg_result($Result,$Row,"l1_croasefragment");
			$jsonupdate_sol[$solmk]["line16_rock_rok"]["z2"]= pg_result($Result,$Row,"l2_croasefragment");
			$jsonupdate_sol[$solmk]["line16_rock_rok"]["z3"]= pg_result($Result,$Row,"l3_croasefragment");
			$jsonupdate_sol[$solmk]["line16_rock_rok"]["z4"]= pg_result($Result,$Row,"l4_croasefragment");
			$jsonupdate_sol[$solmk]["line16_rock_rok"]["z5"]= pg_result($Result,$Row,"l5_croasefragment");
			$jsonupdate_sol[$solmk]["line16_rock_rok"]["z6"]= pg_result($Result,$Row,"l6_croasefragment");
			$jsonupdate_sol[$solmk]["line16_rock_rok"]["z7"]= pg_result($Result,$Row,"l7_croasefragment");
			$jsonupdate_sol[$solmk]["line16_rock_rok"]["z8"]= pg_result($Result,$Row,"l8_croasefragment");
			$jsonupdate_sol[$solmk]["line16_rock_rok"]["z9"]= pg_result($Result,$Row,"l9_croasefragment");
			$jsonupdate_sol[$solmk]["line16_rock_rok"]["z10"]= pg_result($Result,$Row,"l10_croasefragment");

			$jsonupdate_sol[$solmk]["line17_inisolnconc_cnds"]["z1"]= 0.00;
			$jsonupdate_sol[$solmk]["line17_inisolnconc_cnds"]["z2"]= 0.00;
			$jsonupdate_sol[$solmk]["line17_inisolnconc_cnds"]["z3"]= 0.00;
			$jsonupdate_sol[$solmk]["line17_inisolnconc_cnds"]["z4"]= 0.00;
			$jsonupdate_sol[$solmk]["line17_inisolnconc_cnds"]["z5"]= 0.00;
			$jsonupdate_sol[$solmk]["line17_inisolnconc_cnds"]["z6"]= 0.00;
			$jsonupdate_sol[$solmk]["line17_inisolnconc_cnds"]["z7"]= 0.00;
			$jsonupdate_sol[$solmk]["line17_inisolnconc_cnds"]["z8"]= 0.00;
			$jsonupdate_sol[$solmk]["line17_inisolnconc_cnds"]["z9"]= 0.00;
			$jsonupdate_sol[$solmk]["line17_inisolnconc_cnds"]["z10"]= 0.00;

			$jsonupdate_sol[$solmk]["line18_soilp_ssf"]["z1"]= pg_result($Result,$Row,"l1_ph2osoluble_r");
			$jsonupdate_sol[$solmk]["line18_soilp_ssf"]["z2"]= pg_result($Result,$Row,"l2_ph2osoluble_r");
			$jsonupdate_sol[$solmk]["line18_soilp_ssf"]["z3"]= pg_result($Result,$Row,"l3_ph2osoluble_r");
			$jsonupdate_sol[$solmk]["line18_soilp_ssf"]["z4"]= pg_result($Result,$Row,"l4_ph2osoluble_r");
			$jsonupdate_sol[$solmk]["line18_soilp_ssf"]["z5"]= pg_result($Result,$Row,"l5_ph2osoluble_r");
			$jsonupdate_sol[$solmk]["line18_soilp_ssf"]["z6"]= pg_result($Result,$Row,"l6_ph2osoluble_r");
			$jsonupdate_sol[$solmk]["line18_soilp_ssf"]["z7"]= pg_result($Result,$Row,"l7_ph2osoluble_r");
			$jsonupdate_sol[$solmk]["line18_soilp_ssf"]["z8"]= pg_result($Result,$Row,"l8_ph2osoluble_r");
			$jsonupdate_sol[$solmk]["line18_soilp_ssf"]["z9"]= pg_result($Result,$Row,"l9_ph2osoluble_r");
			$jsonupdate_sol[$solmk]["line18_soilp_ssf"]["z10"]= pg_result($Result,$Row,"l10_ph2osoluble_r");

			$jsonupdate_sol[$solmk]["line20_drybd_bdd"]["z1"]= pg_result($Result,$Row,"l1_drybulkdensity");
			$jsonupdate_sol[$solmk]["line20_drybd_bdd"]["z2"]= pg_result($Result,$Row,"l2_drybulkdensity");
			$jsonupdate_sol[$solmk]["line20_drybd_bdd"]["z3"]= pg_result($Result,$Row,"l3_drybulkdensity");
			$jsonupdate_sol[$solmk]["line20_drybd_bdd"]["z4"]= pg_result($Result,$Row,"l4_drybulkdensity");
			$jsonupdate_sol[$solmk]["line20_drybd_bdd"]["z5"]= pg_result($Result,$Row,"l5_drybulkdensity");
			$jsonupdate_sol[$solmk]["line20_drybd_bdd"]["z6"]= pg_result($Result,$Row,"l6_drybulkdensity");
			$jsonupdate_sol[$solmk]["line20_drybd_bdd"]["z7"]= pg_result($Result,$Row,"l7_drybulkdensity");
			$jsonupdate_sol[$solmk]["line20_drybd_bdd"]["z8"]= pg_result($Result,$Row,"l8_drybulkdensity");
			$jsonupdate_sol[$solmk]["line20_drybd_bdd"]["z9"]= pg_result($Result,$Row,"l9_drybulkdensity");
			$jsonupdate_sol[$solmk]["line20_drybd_bdd"]["z10"]= pg_result($Result,$Row,"l10_drybulkdensity");

			$jsonupdate_sol[$solmk]["line22_ksat"]["z1"]= pg_result($Result,$Row,"l1_ksat");
			$jsonupdate_sol[$solmk]["line22_ksat"]["z2"]= pg_result($Result,$Row,"l2_ksat");
			$jsonupdate_sol[$solmk]["line22_ksat"]["z3"]= pg_result($Result,$Row,"l3_ksat");
			$jsonupdate_sol[$solmk]["line22_ksat"]["z4"]= pg_result($Result,$Row,"l4_ksat");
			$jsonupdate_sol[$solmk]["line22_ksat"]["z5"]= pg_result($Result,$Row,"l5_ksat");
			$jsonupdate_sol[$solmk]["line22_ksat"]["z6"]= pg_result($Result,$Row,"l6_ksat");
			$jsonupdate_sol[$solmk]["line22_ksat"]["z7"]= pg_result($Result,$Row,"l7_ksat");
			$jsonupdate_sol[$solmk]["line22_ksat"]["z8"]= pg_result($Result,$Row,"l8_ksat");
			$jsonupdate_sol[$solmk]["line22_ksat"]["z9"]= pg_result($Result,$Row,"l9_ksat");
			$jsonupdate_sol[$solmk]["line22_ksat"]["z10"]= pg_result($Result,$Row,"l10_ksat");



			$jsonupdate_sol[$solmk]["line24_orgp_wpo"]["z1"]= pg_result($Result,$Row,"l1_ptotal") - pg_result($Result,$Row,"l1_ph2osoluble_r");
			$jsonupdate_sol[$solmk]["line24_orgp_wpo"]["z2"]= pg_result($Result,$Row,"l2_ptotal") - pg_result($Result,$Row,"l2_ph2osoluble_r");
			$jsonupdate_sol[$solmk]["line24_orgp_wpo"]["z3"]= pg_result($Result,$Row,"l3_ptotal") - pg_result($Result,$Row,"l3_ph2osoluble_r");
			$jsonupdate_sol[$solmk]["line24_orgp_wpo"]["z4"]= pg_result($Result,$Row,"l4_ptotal") - pg_result($Result,$Row,"l4_ph2osoluble_r");
			$jsonupdate_sol[$solmk]["line24_orgp_wpo"]["z5"]= pg_result($Result,$Row,"l5_ptotal") - pg_result($Result,$Row,"l5_ph2osoluble_r");
			$jsonupdate_sol[$solmk]["line24_orgp_wpo"]["z6"]= pg_result($Result,$Row,"l6_ptotal") - pg_result($Result,$Row,"l6_ph2osoluble_r");
			$jsonupdate_sol[$solmk]["line24_orgp_wpo"]["z7"]= pg_result($Result,$Row,"l7_ptotal") - pg_result($Result,$Row,"l7_ph2osoluble_r");
			$jsonupdate_sol[$solmk]["line24_orgp_wpo"]["z8"]= pg_result($Result,$Row,"l8_ptotal") - pg_result($Result,$Row,"l8_ph2osoluble_r");
			$jsonupdate_sol[$solmk]["line24_orgp_wpo"]["z9"]= pg_result($Result,$Row,"l9_ptotal") - pg_result($Result,$Row,"l9_ph2osoluble_r");
			$jsonupdate_sol[$solmk]["line24_orgp_wpo"]["z10"]= pg_result($Result,$Row,"l10_ptotal") - pg_result($Result,$Row,"l10_ph2osoluble_r");

			$jsonupdate_sol[$solmk]["line26_electricalcond_ec"]["z1"]= pg_result($Result,$Row,"l1_ec");
			$jsonupdate_sol[$solmk]["line26_electricalcond_ec"]["z2"]= pg_result($Result,$Row,"l2_ec");
			$jsonupdate_sol[$solmk]["line26_electricalcond_ec"]["z3"]= pg_result($Result,$Row,"l3_ec");
			$jsonupdate_sol[$solmk]["line26_electricalcond_ec"]["z4"]= pg_result($Result,$Row,"l4_ec");
			$jsonupdate_sol[$solmk]["line26_electricalcond_ec"]["z5"]= pg_result($Result,$Row,"l5_ec");
			$jsonupdate_sol[$solmk]["line26_electricalcond_ec"]["z6"]= pg_result($Result,$Row,"l6_ec");
			$jsonupdate_sol[$solmk]["line26_electricalcond_ec"]["z7"]= pg_result($Result,$Row,"l7_ec");
			$jsonupdate_sol[$solmk]["line26_electricalcond_ec"]["z8"]= pg_result($Result,$Row,"l8_ec");
			$jsonupdate_sol[$solmk]["line26_electricalcond_ec"]["z9"]= pg_result($Result,$Row,"l9_ec");
			$jsonupdate_sol[$solmk]["line26_electricalcond_ec"]["z10"]= pg_result($Result,$Row,"l10_ec");

			$jsonupdate_sol[$solmk]["layerid"]["z1"]= pg_result($Result,$Row,"l1_layerid");
			$jsonupdate_sol[$solmk]["layerid"]["z2"]= pg_result($Result,$Row,"l2_layerid");
			$jsonupdate_sol[$solmk]["layerid"]["z3"]= pg_result($Result,$Row,"l3_layerid");
			$jsonupdate_sol[$solmk]["layerid"]["z4"]= pg_result($Result,$Row,"l4_layerid");
			$jsonupdate_sol[$solmk]["layerid"]["z5"]= pg_result($Result,$Row,"l5_layerid");
			$jsonupdate_sol[$solmk]["layerid"]["z6"]= pg_result($Result,$Row,"l6_layerid");
			$jsonupdate_sol[$solmk]["layerid"]["z7"]= pg_result($Result,$Row,"l7_layerid");
			$jsonupdate_sol[$solmk]["layerid"]["z8"]= pg_result($Result,$Row,"l8_layerid");
			$jsonupdate_sol[$solmk]["layerid"]["z9"]= pg_result($Result,$Row,"l9_layerid");
			$jsonupdate_sol[$solmk]["layerid"]["z10"]= pg_result($Result,$Row,"l10_layerid");

        
        }
        pg_freeresult($Result);
        return $jsonupdate_sol;
}



}

?>
