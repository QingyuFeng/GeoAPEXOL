#!/usr/bin/python3
# -*- coding: utf-8 -*-
"""
Created on Jan, 2019

This script was created to read the JSON file of watershed
and write the information into input files of the APEX model.

The steps include:
    1. read in the JSON file.
    2. write the APEX input files.
The files to be written include:
    1. Run file: runname, sitenumber, monthly wea station no,
       wind weather station no, subarea no, 0 normal soil, 
       subdaily weather file.
    2. Site file: APEX can only have one site file. Since we
       calculate the station based on latitude and longitude.
       The subfile can have one daily file each.
    3. Site list: one site, multiple watersheds.
    4. Sub files: one watersheds, one subfile
    5. Sub list: list of subfiles
    6. Soil files: use the ws sub list.
    7. Soil list: use the ws sub list: one number, one mukey.
    8. OPS list: same as soil list.
    9. Monthly weather file: only one, call climate list
    10. Monthly weather list: only one
    11. Monthly wind file: only one
    12. Monthly wind list: only one
    13. Daily weather file: we can also use one for simplicity.
    14. Daily weather list: one for each subarea.
    
    
All the information shall come from the wssubjson and the
wssubsoillulatlon file.




@author: qyfen
"""

#######################################################
# Environment setting
#######################################################
import sys,os
import time
import json


#######################################################
# Input and output file defining
#######################################################
fddata = sys.argv[1]
scenariofdname = sys.argv[2]
#fddata = 'devfld'

fdapexrun = os.path.join(
    fddata,
    'apexruns',
    scenariofdname
    )



fin_runsubjson = os.path.join(
    fdapexrun,
    'runsub.json'
    )





#######################################################
# Defining classes
#######################################################
class apexfuncs():

    #######################################################
    def initsubcom(self, runfolder):

        outfn_subcom = "SUBACOM.DAT"
        fn_subcom = r"%s/%s" %(runfolder,
                           outfn_subcom)
        
        if os.path.isfile(fn_subcom):
            os.remove(fn_subcom)
                                
        outfid_subcom = 0        
        outfid_subcom = open(fn_subcom, "w")
        
        return outfid_subcom

    
    #######################################################
    def writesubcomline(self, fidsubcom, subvars):

        for wsid in range(len(subvars.keys())):
            fidsubcom.writelines("%5i\tSUB%s.SUB\n" %(wsid+1,
                                              wsid+1))
        
    #######################################################
    def closecomfiles(self, fid):
        
        fid.close()
    


    def writefile_sub(self, wsid, subjson, runfd):
        
        outfn_sub = "SUB%s.SUB" %(wsid)
        # Write the Site file
        outfid_sub = open(r"%s/%s" %(runfd, 
                                     outfn_sub), "w")
        # APEXRUN is read with free format in APEX.exe
        # Write line 1:
        totalsubs = len(subjson['model_setup']['subid_snum'])
        for subid in range(totalsubs):
            outfid_sub.writelines("%8s%8s\n" %(
                    subjson['model_setup']['subid_snum'][subid],
                    subjson['model_setup']['description_title'][subid]))
            # Write line 2:
            outfid_sub.writelines(u"%8s%8s%8s%8s%8s%8s%8s%8s%8s%8s%8s%8s\n" %(\
                    str(subjson["soil"]["soilid"][subid]),\
                    str(subjson["management"]["opeartionid_iops"][subid]),\
                    str(subjson["model_setup"]["owner_id"]),\
                    str(subjson["grazing"]["feeding_area_ii"]),\
                    str(subjson["grazing"]["manure_app_area_iapl"]),\
                    '0',\
                    str(subjson["model_setup"]["nvcn"]),\
                    str(subjson["weather"]["daily_wea_stnid_iwth"]),\
                    str(subjson["point_source"]["point_source_ipts"]),\
                    str(subjson["model_setup"]["outflow_release_method_isao"]),\
                    str(subjson["land_use_type"]["land_useid_luns"][subid]),\
                    str(subjson["management"]["min_days_automow_imw"])\
                    ))
            # Write line 3:
            outfid_sub.writelines(u"%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f\n" %(\
                    float(subjson["weather"]["begin_water_in_snow_sno"]),\
                    float(subjson["land_use_type"]["standing_crop_residue_stdo"]),\
                    float(subjson["geographic"]["latitude_xct"][subid]),\
                    float(subjson["geographic"]["longitude_yct"][subid]),\
                    float(subjson["wind_erosion"]["azimuth_land_slope_azm"]),\
                    float(subjson["wind_erosion"]["field_lenthkm_fl"]),\
                    float(subjson["wind_erosion"]["field_widthkm"]),\
                    float(subjson["wind_erosion"]["angel_of_fieldlength_angl"])\
                    ))
            # Write line 4
            outfid_sub.writelines(u"%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f\n" %(\
                    float(subjson["geographic"]["wsa_ha"][subid]),\
                    float(subjson["geographic"]["channellength_chl"][subid]),\
                    float(subjson["geographic"]["channel_depth_chd"]),\
                    float(subjson["geographic"]["channelslope_chs"][subid]),\
                    float(subjson["geographic"]["channelmanningn_chn"][subid]),\
                    float(subjson["geographic"]["avg_upland_slp"][subid]),\
                    float(subjson["geographic"]["avg_upland_slplen_splg"][subid]),\
                    float(subjson["geographic"]["uplandmanningn_upn"][subid]),\
                    float(subjson["flood_plain"]["flood_plain_frac_ffpq"]),\
                    float(subjson["urban"]["urban_frac_urbf"])\
                    ))
            # Write Line 5
            outfid_sub.writelines(u"%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f\n" %(\
                    float(subjson["geographic"]["reach_length_rchl"][subid]),\
                    float(subjson["geographic"]["reach_depth_rchd"]),\
                    float(subjson["geographic"]["reach_bottom_width_rcbw"]),\
                    float(subjson["geographic"]["reach_top_width_rctw"]),\
                    float(subjson["geographic"]["reach_slope_rchs"][subid]),\
                    float(subjson["geographic"]["reach_manningsn_rchn"]),\
                    float(subjson["geographic"]["reach_uslec_rchc"]),\
                    float(subjson["geographic"]["reach_uslek_rchk"]),\
                    float(subjson["geographic"]["reach_floodplain_rfpw"]),\
                    float(subjson["geographic"]["reach_floodplain_length_rfpl"]),\
                    float(subjson["geographic"]["rch_ksat_adj_factor_sat1"]),\
                    float(subjson["flood_plain"]["fp_ksat_adj_factor_fps1"])\
                    ))
            # Write Line 6
            outfid_sub.writelines(u"%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f\n"%(\
                    float(subjson["reservoir"]["elev_emers_rsee"]),\
                    float(subjson["reservoir"]["res_area_emers_rsae"]),\
                    float(subjson["reservoir"]["runoff_emers_rsve"]),\
                    float(subjson["reservoir"]["elev_prins_rsep"]),\
                    float(subjson["reservoir"]["res_area_prins_rsap"]),\
                    float(subjson["reservoir"]["runoff_prins_rsvp"]),\
                    float(subjson["reservoir"]["ini_res_volume_rsv"]),\
                    float(subjson["reservoir"]["avg_prins_release_rate_rsrr"]),\
                    float(subjson["reservoir"]["ini_sed_res_rsys"]),\
                    float(subjson["reservoir"]["ini_nitro_res_rsyn"])\
                    ))
            # Write Line 7
            outfid_sub.writelines(u"%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f\n" %(\
                    float(subjson["reservoir"]["hydro_condt_res_bottom_rshc"]),\
                    float(subjson["reservoir"]["time_sedconc_tonormal_rsdp"]),\
                    float(subjson["reservoir"]["bd_sed_res_rsbd"]),\
                    float(subjson["pond"]["frac_pond_pcof"]),\
                    float(subjson["buffer"]["frac_buffer_bcof"]),\
                    float(subjson["buffer"]["buffer_flow_len_bffl"]),\
                    
                    float(subjson["water_table"]["min_watertable_wtmn"]),\
                    float(subjson["water_table"]["max_watertable_wtmx"]),\
                    float(subjson["water_table"]["initial_watertable_wtbl"]),\
                    float(subjson["water_table"]["ground_waterstorage_gwst"]),\
                    float(subjson["water_table"]["maxground_waterstorage_gwmx"]),\
                    float(subjson["water_table"]["groundwater_residencetime_rftt"]),\
                    float(subjson["water_table"]["returnflowfraction_rfpk"])\
                    ))
            # Write Line 8
            outfid_sub.writelines(u"%8s%8s%8s%8s%8s%8s%8s%8s%8s%8s%8s%8s%8s\n" %(\
                    str(subjson["irrigation"]["irrigation_irr"]),\
                    str(subjson["irrigation"]["min_days_btw_autoirr_iri"]),\
                    str(subjson["management"]["min_days_autonitro_ifa"]),\
                    str(subjson["management"]["liming_code_lm"]),\
                    str(subjson["management"]["furrow_dike_code_ifd"]),\
                    str(subjson["drainage"]["drainage_depth_idr"][subid]),\
                    str(subjson["management"]["autofert_lagoon_idf1"]),\
                    str(subjson["management"]["auto_manure_feedarea_idf2"]),\
                    str(subjson["management"]["auto_commercial_p_idf3"]),\
                    str(subjson["management"]["auto_commercial_n_idf4"]),\
                    str(subjson["management"]["auto_solid_manure_idf5"]),\
                    str(subjson["management"]["auto_commercial_k_idf6"]),\
                    str(subjson["irrigation"]["subareaid_irrreservior_irrs"])\
                    ))
            # Write Line 9
            outfid_sub.writelines(u"%8s%8s%8s%8s%8s%8s%8s%8s%8s%8s\n" %(\
                    str(subjson["irrigation"]["waterstress_triger_irr_bir"]),\
                    str(subjson["irrigation"]["irr_lost_runoff_efi"]),\
                    str(subjson["irrigation"]["max_annual_irri_vol_vimx"]),\
                    str(subjson["irrigation"]["min_single_irrvol_armn"]),\
                    str(subjson["irrigation"]["max_single_irrvol_armx"]),\
                    str(subjson["management"]["nstress_trigger_auton_bft"]),\
                    str(subjson["management"]["auton_rate_fnp4"]),\
                    str(subjson["management"]["max_annual_auton_fmx"]),\
                    str(subjson["drainage"]["drain_days_end_w_stress_drt"]),\
                    str(subjson["management"]["fd_water_store_fdsf"])\
                    )) 
            # Write Line 10
            outfid_sub.writelines(u"%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f\n" %(\
                    float(subjson["water_erosion"]["usle_p_pec"]),\
                    float(subjson["pond"]["frac_lagoon_dalg"]),\
                    float(subjson["pond"]["lagoon_vol_ratio_vlgn"]),\
                    float(subjson["pond"]["wash_water_to_lagoon_coww"]),\
                    float(subjson["pond"]["time_reduce_lgstorage_nom_ddlg"]),\
                    float(subjson["pond"]["ratio_liquid_manure_to_lg_solq"]),\
                    float(subjson["pond"]["frac_safety_lg_design_sflg"]),\
                    float(subjson["grazing"]["feedarea_pile_autosolidmanure_rate_fnp2"]),\
                    float(subjson["management"]["auton_manure_fnp5"]),\
                    float(subjson["irrigation"]["factor_adj_autoirr_firg"])\
                    ))                        
            # Write Line 11
            outfid_sub.writelines(u"%8s%8s%8s%8s%8s%8s%8s%8s%8s%8s\n" %(\
                    str(subjson["grazing"]["herds_eligible_forgrazing_ny1"]),\
                    str(subjson["grazing"]["herds_eligible_forgrazing_ny2"]),\
                    str(subjson["grazing"]["herds_eligible_forgrazing_ny3"]),\
                    str(subjson["grazing"]["herds_eligible_forgrazing_ny4"]),\
                    str(subjson["grazing"]["herds_eligible_forgrazing_ny5"]),\
                    str(subjson["grazing"]["herds_eligible_forgrazing_ny6"]),\
                    str(subjson["grazing"]["herds_eligible_forgrazing_ny7"]),\
                    str(subjson["grazing"]["herds_eligible_forgrazing_ny8"]),\
                    str(subjson["grazing"]["herds_eligible_forgrazing_ny9"]),\
                    str(subjson["grazing"]["herds_eligible_forgrazing_ny10"])\
                    ))                          
            # Write Line 12
            outfid_sub.writelines(u"%8s%8s%8s%8s%8s%8s%8s%8s%8s%8s\n" %(\
                    str(subjson["grazing"]["grazing_limit_herd_xtp1"]),\
                    str(subjson["grazing"]["grazing_limit_herd_xtp2"]),\
                    str(subjson["grazing"]["grazing_limit_herd_xtp3"]),\
                    str(subjson["grazing"]["grazing_limit_herd_xtp4"]),\
                    str(subjson["grazing"]["grazing_limit_herd_xtp5"]),\
                    str(subjson["grazing"]["grazing_limit_herd_xtp6"]),\
                    str(subjson["grazing"]["grazing_limit_herd_xtp7"]),\
                    str(subjson["grazing"]["grazing_limit_herd_xtp8"]),\
                    str(subjson["grazing"]["grazing_limit_herd_xtp9"]),\
                    str(subjson["grazing"]["grazing_limit_herd_xtp10"])\
                    ))                         
                
                            
                            
        outfid_sub.close()




class apexsubs():

    def __init__(self):
        """Constructor."""
        
        # Read in the json files into dict
        self.subjson = {}        
        self.subjson = self.read_json(fin_runsubjson)        

        
        # Initiate all variables
        self.fidsubcom = 0
        self.fidsubcom = apexfuncs.initsubcom(fdapexrun)
        apexfuncs.writesubcomline(self.fidsubcom, self.subjson)
 
        # Close files
        apexfuncs.closecomfiles(self.fidsubcom)

        # Update subjson for installing tile drainage
        self.writesubs(self.subjson)



    def writesubs(self, subjson):
        
        for key, value in subjson.items():
            wsid = key[9:]
            apexfuncs.writefile_sub(wsid, value, fdapexrun)
            
        
        
        


    def read_json(self, jsonname):

        inf_usrjson = None
        
        with open(jsonname) as json_file:    
            inf_usrjson = json.loads(json_file.read())
#        pprint.pprint(inf_usrjson)
        json_file.close()
        
        return inf_usrjson

            

    


#######################################################
# Call Functions
#######################################################

start_time = time.time()

apexfuncs = apexfuncs()
apexsubs = apexsubs()

