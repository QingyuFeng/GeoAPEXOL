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
scefdname = sys.argv[2]

#fddata = 'devfld'

fdapexrun = os.path.join(
    fddata,
    'apexruns',
    scefdname
    )

fin_sitejson = os.path.join(
    fdapexrun,
    'runsite.json'
    )



#######################################################
# Defining classes
#######################################################
class apexfuncs():
    
    
    #######################################################
    def initsitecom(self, runfolder):

        # Remove the file if it exist
        outfn_sitcom = "SITECOM.DAT"
        fn_sitcom = r"%s/%s" %(runfolder,
                               outfn_sitcom)
        
        if os.path.isfile(fn_sitcom):
            os.remove(fn_sitcom)
            
        outfid_sitcom = 0
        outfid_sitcom = open(fn_sitcom, "w")
        return outfid_sitcom

    
    #######################################################
    def writesitcomline(self, fidsitcom):

        fidsitcom.writelines("%5i\tSIT%s.SIT\n" %(1,1))
    
    
    #######################################################
    def closecomfiles(self, fid):
        
        fid.close()
    
        

    def writefile_sit(self, inf_usrjson, runfd):
        
        outfn_sit = "SIT1.SIT"
        # Write the Site file
        outfid_sit = open(r"%s/%s" %(runfd,
                                     outfn_sit), "w")
        # APEXRUN is read with free format in APEX.exe
        # Write line 1:
        outfid_sit.writelines("%s\n".rjust(74, " ") %(outfn_sit[:-4]))
        # Write line 2:
        outfid_sit.writelines("%s\n".rjust(70, " ") %(outfn_sit))
        # Write line 3:
        outfid_sit.writelines("Outlet 1\n".rjust(74, " "))
        # Write line 4
        outfid_sit.writelines(u"%8.3f%8.3f%8.2f%8s%8s%8s%8s%8s%8s%8s\n" %(\
                            float(inf_usrjson["geographic"]["latitude_ylat"]),\
                            float(inf_usrjson["geographic"]["longitude_xlog"]),\
                            float(inf_usrjson["geographic"]["elevation_elev"]),\
                            inf_usrjson["runoff"]["peakrunoffrate_apm"],\
                            inf_usrjson["co2"]["co2conc_atmos_co2x"],\
                            inf_usrjson["nitrogen"]["no3n_irrigation_cqnx"],\
                            inf_usrjson["nitrogen"]["nitrogen_conc_rainfall_rfnx"],\
                            inf_usrjson["manure"]["manure_p_app_upr"],\
                            inf_usrjson["manure"]["manure_n_app_unr"],\
                            inf_usrjson["irrigation"]["auto_irrig_adj_fir0"]\
                            ))
        # Write Line 5
        outfid_sit.writelines(u"%8s%8s%8s%8s%8s%8s%8s%8s%8s%8s\n" %(\
                            0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00,\
                            inf_usrjson["channel"]["basin_channel_length_bchl"],\
                            inf_usrjson["channel"]["basin_chalnel_slp_bchs"]\
                            ))
        # Write Line 6
        outfid_sit.writelines("\n")
        # Write Line 7
        outfid_sit.writelines("%8i%8i%8i%8i%8i%8i%8i%8i%8i%8i\n" %(\
                            0, 0, 0, 0, 0, 0, 0, 0, 0, 0\
                            ))
        # Write Line 8
        outfid_sit.writelines("%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f\n" %(\
                            0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00,0.00, 0.00\
                            ))
        # Write Line 9
        outfid_sit.writelines("\n")     
        # Write Line 10
        outfid_sit.writelines("\n")  
        # Write Line 11
        outfid_sit.writelines("\n")       
         
        outfid_sit.close()






class apexsites():

    def __init__(self):
        """Constructor."""
        
        # Read in the json files into dict
        self.sitjson = {}
        self.sitjson = self.read_json(fin_sitejson)        
        
        # Initiate all variables
        self.fidsitcom = 0
        self.fidsitcom = apexfuncs.initsitecom(fdapexrun)

        # Write contents in the list files.
        apexfuncs.writesitcomline(self.fidsitcom)

        # Close files
        apexfuncs.closecomfiles(self.fidsitcom)


        apexfuncs.writefile_sit(self.sitjson, fdapexrun)

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
apexsites = apexsites()



    
    
    
