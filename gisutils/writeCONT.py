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

#fddata = 'devfld'

fdapexrun = os.path.join(
    fddata,
    'apexruns'
    )


fin_contjson = os.path.join(
    fdapexrun,
    'runcont.json'
    ) 



#######################################################
# Defining classes
#######################################################
class apexfuncs():
    
	def write_apexcont(self, contjs, usr_runfd):
		
		# I will use a distionary for cont lines
		# It will be initiated first as the template for stop.

		# Write the APEXCONT file
		outfid_cont = 0
		outfid_cont = open(r"%s/APEXCONT.DAT" %(usr_runfd), "w")
		# APEXCONT is read with free format in APEX.exe
		# Line 1
		outfid_cont.writelines(u"%5s%5s%5s%5s%5s%5s%5s%5s%5s%5s%5s%5s%5s%5s%5s%5s%5s%5s%5s%5s\n" %(\
			contjs["model_setup"]["yearstorun_nbyr"],\
			contjs["model_setup"]["begin_year_iyr"],\
			contjs["model_setup"]["begin_month_imo"],\
			contjs["model_setup"]["begin_day_ida"],\
			contjs["model_setup"]["output_freq_ipd"],\
			contjs["weather"]["weather_in_var_ngn"],\
			contjs["weather"]["random_seeds_ign"],\
			contjs["weather"]["date_weather_duplicate_igsd"],\
			contjs["model_setup"]["leap_year_lpyr"],\
			contjs["weather"]["pet_method_iet"],\
			contjs["runoff_sim"]["stochastic_cn_code_iscn"],\
			contjs["runoff_sim"]["peak_rate_method_ityp"],\
			contjs["water_erosion"]["static_soil_code_ista"],\
			contjs["management"]["automatic_hu_schedule_ihus"],\
			contjs["runoff_sim"]["non_varying_cn_nvcn"],\
			contjs["runoff_sim"]["runoff_method_infl"],\
			contjs["nutrient_loss"]["pesticide_mass_conc_masp"],\
			contjs["nutrient_loss"]["enrichment_ratio_iert"],\
			contjs["nutrient_loss"]["soluble_p_estimate_lbp"],\
			contjs["nutrient_loss"]["n_p_uptake_curve_nupc"]\
				))
		# Line 2
		outfid_cont.writelines(u"%5s%5s%5s%5s%5s%5s%5s%5s%5s%5s%5s%5s%5s%5s%5s%5s%5s%5s%5s%5s%5s%5s%5s\n" %(\
			contjs["management"]["manure_application_mnul"],\
			contjs["management"]["lagoon_pumping_lpd"],\
			contjs["management"]["solid_manure_mscp"],\
			contjs["water_erosion"]["slope_length_steep_islf"],\
			contjs["air_quality"]["air_quality_code_naq"],\
			contjs["flood_routing"]["flood_routing_ihy"],\
			contjs["air_quality"]["co2_ico2"],\
			contjs["runoff_sim"]["field_capacity_wilting_isw"],\
			contjs["weather"]["number_generator_seeds_igmx"],\
			contjs["model_setup"]["data_dir_idir"],\
			contjs["management"]["minimum_interval_automow_imw"],\
			contjs["air_quality"]["o2_function_iox"],\
			contjs["nutrient_loss"]["denitrification_idnt"],\
			contjs["geography"]["latitude_source_iazm"],\
			contjs["management"]["auto_p_ipat"],\
			contjs["management"]["grazing_mode_ihrd"],\
			contjs["runoff_sim"]["atecedent_period_iwtb"],\
			contjs["model_setup"]["real_time_nstp"],\
			contjs["model_setup"]["output_subareanum_isap"],\
			"0", "0", "0", "0"\
			))                      
		# Line 3                    
		outfid_cont.writelines(u"%8s%8s%8s%8s%8s%8s%8s%8s%8s%8s\n" %(\
			contjs["nutrient_loss"]["avg_conc_n_rainfall_rfn"],\
			contjs["air_quality"]["co2_conc_atom_co2"],\
			contjs["nutrient_loss"]["no3n_conc_irrig_cqn"],\
			contjs["management"]["pest_damage_scaling_pstx"],\
			contjs["weather"]["yrs_max_mon_rainfall_ywi"],\
			contjs["weather"]["wetdry_prob_bta"],\
			contjs["weather"]["param_exp_rainfall_dist_expk"],\
			contjs["runoff_sim"]["channel_capacity_flow_qg"],\
			contjs["runoff_sim"]["exp_watershed_area_flowrate_qcf"],\
			contjs["geography"]["average_upland_slope_chso"]\
			))                                    
		# Line 4                   
		outfid_cont.writelines(u"%8s%8s%8s%8s%8s%8s%8s%8s%8s%8s\n" %(\
			contjs["geography"]["channel_bottom_woverd_bwd"],\
			contjs["flood_routing"]["floodplain_over_channel_fcw"],\
			contjs["flood_routing"]["floodplain_ksat_fpsc"],\
			contjs["geography"]["max_groundwater_storage_gwso"],\
			contjs["geography"]["groundwater_resident_rfto"],\
			contjs["runoff_sim"]["returnflow_ratio_rfpo"],\
			contjs["runoff_sim"]["ksat_adj_sato"],\
			contjs["wind_erosion"]["field_length_fl"],\
			contjs["wind_erosion"]["field_width_fw"],\
			contjs["wind_erosion"]["field_length_angle_ang"]\
			))
		# Line 5        
		outfid_cont.writelines(u"%8s%8s%8s%8s%8s%8s%8s%8s%8s%8s\n" %(\
			contjs["wind_erosion"]["windspeed_distribution_uxp"],\
			contjs["wind_erosion"]["soil_partical_diameter_diam"],\
			contjs["wind_erosion"]["wind_erosion_adj_acw"],\
			contjs["management"]["grazing_limit_gzl0"],\
			contjs["management"]["cultivation_start_year_rtn0"],\
			contjs["weather"]["coef_rainfalldiretow_bxct"],\
			contjs["weather"]["coef_rainfalldirston_byct"],\
			contjs["flood_routing"]["interval_floodrouting_dthy"],\
			contjs["flood_routing"]["routing_threshold_vsc_qth"],\
			contjs["flood_routing"]["vsc_threshold_stnd"]\
			))      
		# Line 6
		outfid_cont.writelines(u"%8s%8s%8s%8s%8s%8s%8s%8s\n" %(\
			contjs["water_erosion"]["water_erosion_equation_drv"],\
			contjs["geography"]["fraction_ponds_pco0"],\
			contjs["water_erosion"]["usle_c_channel_rcc0"],\
			contjs["nutrient_loss"]["salt_conc_irrig_cslt"],\
			contjs["water_erosion"]["msi_input_1"],\
			contjs["water_erosion"]["msi_input_2"],\
			contjs["water_erosion"]["msi_input_3"],\
			contjs["water_erosion"]["msi_input_4"]\
			))                                                 
		outfid_cont.close()

    
    
    
    


class apexinputs():

    def __init__(self):
        """Constructor."""
        
        # Read in the json files into dict
        self.contjson = {}
        
        self.contjson = self.read_json(fin_contjson)        
        
        
        apexfuncs.write_apexcont(self.contjson, fdapexrun)


    def read_json(self, jsonfn):

        contjs = None
        
        with open(jsonfn) as json_file:    
            contjs = json.loads(json_file.read())

        json_file.close()
        
        return contjs

            

#######################################################
# Call Functions
#######################################################

start_time = time.time()

apexfuncs = apexfuncs()
apexinputs = apexinputs()











print("--- %s seconds ---" % (time.time() - start_time))











#######################################################


